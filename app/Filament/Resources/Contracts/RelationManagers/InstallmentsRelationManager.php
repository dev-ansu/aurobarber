<?php

namespace App\Filament\Resources\Contracts\RelationManagers;

use App\Filament\Resources\Installments\Tables\InstallmentsTable;
use App\Models\Contract;
use App\Models\FinancialType;
use App\Models\Installment;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class InstallmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'installments';

    protected static ?string $title = 'Parcelas e Mensalidades';

    protected static ?string $modelLabel = 'parcela';

    protected static ?string $pluralModelLabel = 'parcelas';

    protected static string|BackedEnum|null $icon = 'heroicon-o-banknotes';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof Contract && ! $ownerRecord->isFree();
    }

    public function form(Schema $schema): Schema
    {
        /** @var Contract $contract */
        $contract = $this->getOwnerRecord();

        return $schema
            ->components([
                Grid::make(3)
                    ->schema([
                        Select::make('financial_type_id')
                            ->label('Tipo de Cobrança')
                            ->relationship('financialType', 'name')
                            ->options(fn () => FinancialType::where('is_active', true)->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(),

                        TextInput::make('installment_number')
                            ->label('Nº Parcela')
                            ->numeric()
                            ->default(function () use ($contract): int {
                                $max = $contract->installments()->max('installment_number');

                                return $max ? ((int) $max + 1) : 1;
                            })
                            ->required(),

                        TextInput::make('total_installments')
                            ->label('Total de Parcelas')
                            ->numeric()
                            ->default(function () use ($contract): int {
                                $max = $contract->installments()->max('total_installments');

                                return $max ? ((int) $max + 1) : 1;
                            })
                            ->required(),

                        TextInput::make('amount')
                            ->label('Valor Base')
                            ->numeric()
                            ->prefix('R$')
                            ->required(),

                        DatePicker::make('due_date')
                            ->label('Vencimento')
                            ->required(),

                        Select::make('status')
                            ->label('Situação')
                            ->options([
                                'pending' => 'Pendente',
                                'paid' => 'Paga',
                                'partial' => 'Parcial',
                                'canceled' => 'Cancelada',
                            ])
                            ->default('pending')
                            ->disabled(fn (?Installment $record): bool => $record?->status === 'paid'),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        /** @var Contract $contract */
        $contract = $this->getOwnerRecord();

        return $table
            ->defaultSort('installment_number', 'asc')
            ->columns([
                TextColumn::make('installment_number')
                    ->label('Parcela')
                    ->formatStateUsing(fn ($state, $record) => $state ? "{$state} / {$record->total_installments}" : 'Avulsa')
                    ->sortable(),

                TextColumn::make('financialType.name')
                    ->label('Tipo')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('amount')
                    ->label('Valor Base')
                    ->money('BRL')
                    ->sortable(),

                TextColumn::make('due_date')
                    ->label('Vencimento')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn (Installment $record): ?string => in_array($record->status, ['pending', 'partial']) && $record->due_date?->isPast() ? 'danger' : null),

                TextColumn::make('amount_paid')
                    ->label('Valor Pago')
                    ->money('BRL')
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('paid_at')
                    ->label('Data Pgto')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Situação')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'pending' => 'warning',
                        'partial' => 'info',
                        'canceled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'paid' => 'Paga',
                        'pending' => 'Pendente',
                        'partial' => 'Parcial',
                        'canceled' => 'Cancelada',
                        default => $state,
                    }),
            ])
            ->headerActions([
                Action::make('renumber')
                    ->label('Renumerar Parcelas (1 a N)')
                    ->icon('heroicon-m-arrows-up-down')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Renumerar todas as parcelas?')
                    ->modalDescription('Esta ação reorganiza a numeração de todas as parcelas deste contrato em ordem crescente de vencimento (1 a N).')
                    ->action(function () use ($contract): void {
                        $installments = $contract->installments()->orderBy('due_date', 'asc')->get();
                        $total = $installments->count();
                        $num = 1;
                        foreach ($installments as $inst) {
                            $inst->forceFill([
                                'installment_number' => $num,
                                'total_installments' => $total,
                            ])->saveQuietly();
                            $num++;
                        }
                        Notification::make()
                            ->success()
                            ->title('Parcelas Renumeradas')
                            ->body("As {$total} parcelas foram organizadas por data de vencimento e renumeradas de 1 a {$total}.")
                            ->send();
                    }),

                CreateAction::make()
                    ->label('Adicionar Cobrança Avulsa')
                    ->modalHeading('Nova Parcela / Cobrança Avulsa')
                    ->mutateFormDataUsing(function (array $data) use ($contract): array {
                        $data['school_id'] = $contract->school_id;
                        $data['status'] = 'pending';

                        return $data;
                    }),
            ])
            ->recordActions([
                InstallmentsTable::makePayAction(),
                InstallmentsTable::makeReceiptAction(),
                InstallmentsTable::makeWhatsappAction(),
                ActionGroup::make([
                    InstallmentsTable::makeAttachmentAction(),
                    InstallmentsTable::makeRefundAction(),
                    InstallmentsTable::makeCancelAction(),
                    ViewAction::make()
                        ->url(fn (Installment $record): string => route('filament.admin.resources.installments.view', [
                            'tenant' => filament()->getTenant(),
                            'record' => $record,
                        ])),
                    EditAction::make(),
                    DeleteAction::make()
                        ->visible(fn (Installment $record): bool => filament()->auth()->user()?->can('delete', $record) ?? false),
                ])->label('Mais Ações'),
            ]);
    }
}

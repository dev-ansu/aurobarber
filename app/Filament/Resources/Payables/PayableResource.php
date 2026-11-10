<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payables;

use App\Filament\Resources\Payables\Pages\ManagePayables;
use App\Models\Payable;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class PayableResource extends Resource
{
    protected static ?string $model = Payable::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static string|UnitEnum|null $navigationGroup = 'Financeiro';

    protected static ?string $navigationLabel = 'Contas a pagar';

    protected static ?string $modelLabel = 'conta a pagar';

    protected static ?string $pluralModelLabel = 'contas a pagar';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return Filament::getTenant() !== null
            && ! auth()->user()?->isRestrictedBarber();
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $tenantId = Filament::getTenant()?->getKey();

        return parent::getEloquentQuery()
            ->where('barbershop_id', $tenantId ?? 0);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Dados da despesa')
                ->schema([
                    TextInput::make('description')
                        ->label('Descrição')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    TextInput::make('supplier')
                        ->label('Fornecedor / Credor')
                        ->maxLength(255),
                    Select::make('category')
                        ->label('Categoria')
                        ->options([
                            'Aluguel' => 'Aluguel',
                            'Água e energia' => 'Água e energia',
                            'Produtos e insumos' => 'Produtos e insumos',
                            'Salários e comissões' => 'Salários e comissões',
                            'Impostos e taxas' => 'Impostos e taxas',
                            'Manutenção' => 'Manutenção',
                            'Outros' => 'Outros',
                        ])
                        ->searchable()
                        ->native(false),
                    TextInput::make('amount')
                        ->label('Valor')
                        ->numeric()
                        ->prefix('R$')
                        ->minValue(0.01)
                        ->required(),
                    DatePicker::make('due_date')
                        ->label('Vencimento')
                        ->default(today())
                        ->required(),
                    Textarea::make('notes')
                        ->label('Observações')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('due_date')
            ->columns([
                TextColumn::make('description')
                    ->label('Despesa')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('supplier')
                    ->label('Fornecedor')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('category')
                    ->label('Categoria')
                    ->badge()
                    ->placeholder('—'),
                TextColumn::make('amount')
                    ->label('Valor')
                    ->money('BRL')
                    ->sortable(),
                TextColumn::make('due_date')
                    ->label('Vencimento')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn (Payable $record): string => $record->isOverdue() ? 'danger' : 'gray'),
                TextColumn::make('status')
                    ->label('Situação')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'paid' => 'Paga',
                        'cancelled' => 'Cancelada',
                        default => 'Pendente',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'cancelled' => 'gray',
                        default => 'warning',
                    }),
                TextColumn::make('payment_date')
                    ->label('Data do pagamento')
                    ->date('d/m/Y')
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Situação')
                    ->options([
                        'pending' => 'Pendente',
                        'paid' => 'Paga',
                        'cancelled' => 'Cancelada',
                    ]),
            ])
            ->recordActions([
                Action::make('markPaid')
                    ->label('Registrar pagamento')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Payable $record): bool => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->form([
                        DatePicker::make('payment_date')
                            ->label('Data do pagamento')
                            ->default(today())
                            ->required(),
                        Select::make('payment_method')
                            ->label('Forma de pagamento')
                            ->options([
                                'dinheiro' => 'Dinheiro',
                                'pix' => 'Pix',
                                'debito' => 'Cartão de débito',
                                'credito' => 'Cartão de crédito',
                                'transferencia' => 'Transferência',
                                'outro' => 'Outro',
                            ])
                            ->native(false),
                    ])
                    ->action(function (Payable $record, array $data): void {
                        $record->markPaid(
                            \Illuminate\Support\Carbon::parse($data['payment_date']),
                            $data['payment_method'] ?? null,
                        );

                        Notification::make()
                            ->title('Pagamento registrado')
                            ->success()
                            ->send();
                    }),
                EditAction::make()
                    ->visible(fn (Payable $record): bool => $record->status === 'pending'),
                DeleteAction::make()
                    ->visible(fn (Payable $record): bool => $record->status === 'pending'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePayables::route('/'),
        ];
    }
}

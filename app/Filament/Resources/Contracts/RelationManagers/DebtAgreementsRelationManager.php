<?php

namespace App\Filament\Resources\Contracts\RelationManagers;

use App\Models\Contract;
use App\Models\DebtAgreement;
use App\Models\FinancialType;
use App\Models\PaymentMethod;
use App\Services\DebtAgreementService;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class DebtAgreementsRelationManager extends RelationManager
{
    protected static string $relationship = 'debtAgreements';

    protected static ?string $title = 'Acordos e Renegociações de Dívidas';

    protected static ?string $modelLabel = 'acordo';

    protected static ?string $pluralModelLabel = 'acordos';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof Contract;
    }

    public function table(Table $table): Table
    {
        /** @var Contract $contract */
        $contract = $this->getOwnerRecord();

        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('agreement_number')
                    ->label('Nº Acordo')
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Data do Acordo')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('total_original_amount')
                    ->label('Dívida Original')
                    ->money('BRL')
                    ->sortable(),

                TextColumn::make('discount_amount')
                    ->label('Desconto')
                    ->money('BRL'),

                TextColumn::make('total_agreed_amount')
                    ->label('Total Acordado')
                    ->money('BRL')
                    ->weight('bold')
                    ->color('success')
                    ->sortable(),

                TextColumn::make('down_payment_amount')
                    ->label('Entrada')
                    ->money('BRL')
                    ->description(fn (DebtAgreement $record): string => $record->down_payment_paid ? 'Paga no ato' : ($record->down_payment_amount > 0 ? 'Pendente' : 'Sem entrada')),

                TextColumn::make('installments_count')
                    ->label('Parcelas')
                    ->formatStateUsing(fn ($state): string => "{$state}x"),

                TextColumn::make('user.name')
                    ->label('Operador')
                    ->placeholder('Sistema / Usuário')
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Situação')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'completed' => 'info',
                        'canceled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Ativo',
                        'completed' => 'Cumprido',
                        'canceled' => 'Cancelado',
                        default => $state,
                    }),
            ])
            ->headerActions([
                Action::make('createAgreement')
                    ->label('Novo Acordo de Dívida')
                    ->icon('heroicon-m-banknotes')
                    ->color('warning')
                    ->visible(fn (): bool => ! in_array($contract->status, ['canceled', 'finished'])
                        && $contract->installments()->whereIn('status', ['pending', 'partial'])->exists())
                    ->modalHeading('Renegociação e Acordo de Dívidas')
                    ->modalDescription('Selecione as parcelas em atraso ou pendentes para cancelar, aplicar desconto, registrar entrada e reparcelar o saldo.')
                    ->modalSubmitActionLabel('Formalizar Acordo')
                    ->modalWidth('4xl')
                    ->schema([
                        Section::make('Parcelas a Renegociar')->schema([
                            CheckboxList::make('installment_ids')
                                ->label('Selecione as parcelas para o acordo')
                                ->options(function () use ($contract): array {
                                    return $contract->installments()
                                        ->whereIn('status', ['pending', 'partial'])
                                        ->orderBy('due_date')
                                        ->get()
                                        ->mapWithKeys(function ($inst): array {
                                            $num = $inst->installment_number ? "Parcela {$inst->installment_number}/{$inst->total_installments}" : 'Taxa Avulsa';
                                            $venc = $inst->due_date ? Carbon::parse($inst->due_date)->format('d/m/Y') : '-';
                                            $val = number_format($inst->amount, 2, ',', '.');
                                            $tipo = $inst->financialType?->name ?? 'Mensalidade';
                                            $atraso = $inst->due_date && Carbon::parse($inst->due_date)->isPast() ? ' (ATRASADA)' : '';

                                            return [$inst->id => "{$num} [{$tipo}] — R$ {$val} — Vencimento: {$venc}{$atraso}"];
                                        })
                                        ->all();
                                })
                                ->default(function () use ($contract): array {
                                    return $contract->installments()
                                        ->whereIn('status', ['pending', 'partial'])
                                        ->pluck('id')
                                        ->all();
                                })
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (Get $get, Set $set) use ($contract): void {
                                    $selectedIds = (array) ($get('installment_ids') ?? []);
                                    $total = $contract->installments()->whereIn('id', $selectedIds)->sum('amount');
                                    $set('total_original_amount', number_format($total, 2, '.', ''));
                                    $discount = floatval($get('discount_amount') ?? 0);
                                    $agreed = max(0, $total - $discount);
                                    $set('total_agreed_amount', number_format($agreed, 2, '.', ''));
                                }),
                        ]),

                        Section::make('Valores e Condições')->schema([
                            Grid::make(3)->schema([
                                TextInput::make('total_original_amount')
                                    ->label('Total Original')
                                    ->prefix('R$')
                                    ->readOnly()
                                    ->dehydrated()
                                    ->default(function () use ($contract): string {
                                        return number_format($contract->installments()->whereIn('status', ['pending', 'partial'])->sum('amount'), 2, '.', '');
                                    }),

                                TextInput::make('discount_amount')
                                    ->label('Desconto Concedido')
                                    ->prefix('R$')
                                    ->numeric()
                                    ->default('0.00')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get, Set $set): void {
                                        $total = floatval($get('total_original_amount') ?? 0);
                                        $discount = floatval($get('discount_amount') ?? 0);
                                        $agreed = max(0, $total - $discount);
                                        $set('total_agreed_amount', number_format($agreed, 2, '.', ''));
                                    }),

                                TextInput::make('total_agreed_amount')
                                    ->label('Total Líquido do Acordo')
                                    ->prefix('R$')
                                    ->readOnly()
                                    ->dehydrated()
                                    ->default(function () use ($contract): string {
                                        return number_format($contract->installments()->whereIn('status', ['pending', 'partial'])->sum('amount'), 2, '.', '');
                                    }),
                            ]),

                            Grid::make(3)->schema([
                                TextInput::make('down_payment_amount')
                                    ->label('Valor da Entrada')
                                    ->prefix('R$')
                                    ->numeric()
                                    ->default('0.00')
                                    ->live(onBlur: true),

                                Toggle::make('down_payment_paid')
                                    ->label('Entrada paga no ato')
                                    ->helperText('Registra recebimento no caixa aberto.')
                                    ->default(false)
                                    ->live(),

                                Select::make('payment_method_id')
                                    ->label('Forma de Pgto da Entrada')
                                    ->options(fn (): array => PaymentMethod::pluck('name', 'id')->all())
                                    ->visible(fn (Get $get): bool => (bool) $get('down_payment_paid') && floatval($get('down_payment_amount') ?? 0) > 0)
                                    ->required(fn (Get $get): bool => (bool) $get('down_payment_paid') && floatval($get('down_payment_amount') ?? 0) > 0),
                            ]),

                            Grid::make(3)->schema([
                                TextInput::make('installments_count')
                                    ->label('Qtd. Parcelas Saldo Restante')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(36)
                                    ->default(1)
                                    ->required(),

                                DatePicker::make('first_due_date')
                                    ->label('1º Vencimento do Saldo')
                                    ->default(now()->addMonth()->startOfDay())
                                    ->required(),

                                Select::make('financial_type_id')
                                    ->label('Classificação Financeira')
                                    ->options(fn (): array => FinancialType::pluck('name', 'id')->all())
                                    ->default(function (): ?int {
                                        $likeOp = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

                                        return FinancialType::query()->where('name', $likeOp, '%acordo%')->value('id') ?? FinancialType::first()?->id;
                                    }),
                            ]),

                            Textarea::make('notes')
                                ->label('Observações do Acordo')
                                ->placeholder('Motivo da renegociação, condições combinadas com o responsável...')
                                ->rows(2)
                                ->columnSpanFull(),
                        ]),
                    ])
                    ->action(function (array $data) use ($contract): void {
                        try {
                            $service = app(DebtAgreementService::class);
                            $agreement = $service->createAgreement($contract, $data, auth()->user());

                            Notification::make()
                                ->title('Acordo de Dívida formalizado!')
                                ->body("Acordo nº {$agreement->agreement_number} gerado com sucesso.")
                                ->success()
                                ->actions([
                                    Action::make('printTermo')
                                        ->label('Imprimir Termo de Confissão')
                                        ->icon('heroicon-m-printer')
                                        ->url(route('contracts.termo-acordo', [$contract, $agreement]), shouldOpenInNewTab: true),
                                ])
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Erro ao processar acordo')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->recordActions([
                Action::make('printTermo')
                    ->label('Imprimir Termo')
                    ->icon('heroicon-m-printer')
                    ->color('primary')
                    ->url(fn (DebtAgreement $record): string => route('contracts.termo-acordo', [$record->contract, $record]))
                    ->openUrlInNewTab(),
            ]);
    }
}

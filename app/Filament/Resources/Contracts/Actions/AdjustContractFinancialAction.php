<?php

namespace App\Filament\Resources\Contracts\Actions;

use App\Models\Contract;
use App\Models\FinancialType;
use App\Services\ContractFinancialAdjustmentService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Carbon;

class AdjustContractFinancialAction
{
    public static function make(string $name = 'adjustFinancial'): Action
    {
        return Action::make($name)
            ->label('Ajustar Financeiro')
            ->tooltip('Corrigir valor do contrato e reparcelar mensalidades pendentes')
            ->icon('heroicon-m-currency-dollar')
            ->color('warning')
            ->modalHeading(fn (Contract $record): string => "Ajustar Financeiro — Contrato #{$record->contract_number} ({$record->student?->name})")
            ->modalDescription('Corrija o valor total do contrato e reparcele as mensalidades pendentes em caso de erro de digitação.')
            ->modalWidth('2xl')
            ->modalSubmitActionLabel('Salvar Ajuste Financeiro')
            ->fillForm(function (Contract $record): array {
                $pendingCount = $record->installments()->where('status', '!=', 'paid')->count();
                $firstPendingDate = $record->installments()->where('status', '!=', 'paid')->orderBy('due_date')->value('due_date');

                return [
                    'mode' => 'recalculate',
                    'calculation_type' => 'divide',
                    'new_total_value' => (float) $record->total_value,
                    'qty_installments' => $pendingCount > 0 ? $pendingCount : 1,
                    'first_due_date' => $firstPendingDate ? Carbon::parse($firstPendingDate)->toDateString() : today()->toDateString(),
                    'financial_type_id' => FinancialType::where('slug', 'mensalidade')->value('id') ?? FinancialType::where('slug', 'tuition')->value('id') ?? FinancialType::value('id'),
                    'reason' => 'Correção de valor do contrato e parcelamento.',
                ];
            })
            ->form([
                Section::make('Resumo Atual do Contrato')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('current_total')
                                ->label('Valor Atual no Contrato')
                                ->disabled()
                                ->dehydrated(false)
                                ->default(fn (Contract $record): string => 'R$ ' . number_format((float) $record->total_value, 2, ',', '.')),
                            TextInput::make('paid_summary')
                                ->label('Parcelas Pagas')
                                ->disabled()
                                ->dehydrated(false)
                                ->default(function (Contract $record): string {
                                    $paid = $record->installments()->where('status', 'paid')->get();
                                    $sum = $paid->sum(fn ($i) => (float) ($i->amount_paid ?: $i->amount));
                                    return $paid->count() . ' paga(s) (R$ ' . number_format($sum, 2, ',', '.') . ')';
                                }),
                            TextInput::make('pending_summary')
                                ->label('Parcelas Pendentes')
                                ->disabled()
                                ->dehydrated(false)
                                ->default(function (Contract $record): string {
                                    $pending = $record->installments()->where('status', '!=', 'paid')->get();
                                    $sum = $pending->sum(fn ($i) => (float) $i->amount);
                                    return $pending->count() . ' pendente(s) (R$ ' . number_format($sum, 2, ',', '.') . ')';
                                }),
                        ]),
                    ])->compact(),

                Radio::make('mode')
                    ->label('Tipo de Ajuste')
                    ->options([
                        'recalculate' => 'Reparcelar saldo pendente (Recomendado para corrigir erro de lançamento)',
                        'adjust_total_only' => 'Apenas alterar o valor total do contrato (sem mexer nas parcelas)',
                    ])
                    ->default('recalculate')
                    ->live(),

                Radio::make('calculation_type')
                    ->label('Modo de Definição do Valor')
                    ->options([
                        'divide' => 'Dividir: Informar o Novo Valor Total do Contrato e dividir pelas parcelas',
                        'multiply' => 'Multiplicar: Informar o Valor de Cada Parcela e a quantidade',
                    ])
                    ->default('divide')
                    ->visible(fn (Get $get): bool => $get('mode') === 'recalculate')
                    ->live(),

                Grid::make(2)->schema([
                    TextInput::make('new_total_value')
                        ->label('Novo Valor Total do Contrato')
                        ->numeric()
                        ->prefix('R$')
                        ->required(fn (Get $get): bool => $get('calculation_type') === 'divide' || $get('mode') === 'adjust_total_only')
                        ->visible(fn (Get $get): bool => $get('calculation_type') === 'divide' || $get('mode') === 'adjust_total_only')
                        ->helperText('Informe o valor total correto do contrato.'),

                    TextInput::make('installment_value')
                        ->label('Valor de Cada Nova Parcela')
                        ->numeric()
                        ->prefix('R$')
                        ->required(fn (Get $get): bool => $get('mode') === 'recalculate' && $get('calculation_type') === 'multiply')
                        ->visible(fn (Get $get): bool => $get('mode') === 'recalculate' && $get('calculation_type') === 'multiply')
                        ->helperText('Ex: R$ 108,33 por mês.'),

                    TextInput::make('qty_installments')
                        ->label('Quantidade de Parcelas a Gerar')
                        ->numeric()
                        ->minValue(1)
                        ->default(12)
                        ->required(fn (Get $get): bool => $get('mode') === 'recalculate')
                        ->visible(fn (Get $get): bool => $get('mode') === 'recalculate'),

                    DatePicker::make('first_due_date')
                        ->label('Vencimento da 1ª Parcela')
                        ->default(today())
                        ->required(fn (Get $get): bool => $get('mode') === 'recalculate')
                        ->visible(fn (Get $get): bool => $get('mode') === 'recalculate'),

                    Select::make('financial_type_id')
                        ->label('Tipo de Cobrança')
                        ->options(fn () => FinancialType::where('is_active', true)->pluck('name', 'id'))
                        ->default(fn () => FinancialType::where('slug', 'mensalidade')->value('id') ?? FinancialType::value('id'))
                        ->required(fn (Get $get): bool => $get('mode') === 'recalculate')
                        ->visible(fn (Get $get): bool => $get('mode') === 'recalculate'),
                ]),

                Textarea::make('reason')
                    ->label('Motivo / Justificativa do Ajuste')
                    ->placeholder('Ex: O contrato era de R$ 1.300 em 12x, mas foi digitado por engano R$ 130 em 12x.')
                    ->required()
                    ->rows(2),
            ])
            ->action(function (Contract $record, array $data): void {
                try {
                    $result = app(ContractFinancialAdjustmentService::class)->adjust($record, $data);
                    $newFmt = number_format($result['new_total'], 2, ',', '.');
                    $msg = "Valor total do contrato atualizado para R$ {$newFmt}.";
                    if ($result['mode'] === 'recalculate') {
                        $msg .= " Foram geradas {$result['generated_count']} nova(s) parcela(s) pendente(s).";
                    }

                    Notification::make()
                        ->title('Financeiro ajustado com sucesso!')
                        ->body($msg)
                        ->success()
                        ->send();
                } catch (\Throwable $e) {
                    Notification::make()
                        ->title('Erro ao ajustar financeiro')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            })
            ->visible(fn (Contract $record): bool => ! in_array($record->status, ['canceled', 'finished']))
            ->authorize(fn (Contract $record): bool => auth()->user()?->can('adjustFinancial', $record) || auth()->user()?->can('Update:Contract') || auth()->user()?->hasRole('super_admin'));
    }
}


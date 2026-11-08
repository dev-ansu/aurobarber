<?php

declare(strict_types=1);

namespace App\Filament\Resources\Contracts\Actions;

use App\Models\Contract;
use App\Models\FinancialType;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class CancelContractWithFeeAction
{
    public static function make(string $name = 'cancelContractWithFee'): Action
    {
        return Action::make($name)
            ->label('Rescindir e Aplicar Multa')
            ->color('danger')
            ->icon('heroicon-m-x-circle')
            ->requiresConfirmation()
            ->modalHeading(fn (Contract $record): string => "Rescisão de Contrato #{$record->contract_number}")
            ->modalDescription('Esta ação cancela as parcelas pendentes futuras, aplica a taxa de rescisão e gera automaticamente o Termo de Rescisão para impressão.')
            ->modalSubmitActionLabel('Confirmar Rescisão e Emitir Termo')
            ->form([
                TextInput::make('fee_amount')
                    ->label('Valor da Taxa de Rescisão / Multa')
                    ->numeric()
                    ->prefix('R$')
                    ->default(0)
                    ->helperText('Informe 0 caso a rescisão seja sem custo de multa.')
                    ->required(),

                Select::make('financial_type_id')
                    ->label('Tipo de Cobrança da Multa')
                    ->options(fn () => FinancialType::where('is_active', true)->where('is_synthetic', false)->pluck('name', 'id'))
                    ->default(fn () => FinancialType::where('slug', 'multa_de_rescisao')->value('id') ?? FinancialType::where('slug', 'multa_rescisao')->value('id') ?? FinancialType::where('code', '1.2.1')->value('id') ?? FinancialType::value('id'))
                    ->required(),

                Textarea::make('cancellation_reason')
                    ->label('Motivo do Cancelamento')
                    ->placeholder('Ex: Mudança de cidade, dificuldades financeiras, incompatibilidade de horários...')
                    ->rows(2)
                    ->required(),
            ])
            ->action(function (Contract $record, array $data, $livewire): void {
                DB::transaction(function () use ($record, $data) {
                    $reason = $data['cancellation_reason'] ?? 'Rescisão contratual com aplicação de taxa';
                    $record->changeStatus('canceled', $reason);

                    // Remove apenas as parcelas pendentes que não possuem transações vinculadas
                    $record->installments()
                        ->where('status', 'pending')
                        ->whereDoesntHave('transactions')
                        ->get()
                        ->each->delete();

                    // Se houver valor de multa informado (> 0), cria a parcela da multa
                    $fee = (float) ($data['fee_amount'] ?? 0);
                    if ($fee > 0) {
                        $record->installments()->create([
                            'school_id' => $record->school_id,
                            'financial_type_id' => $data['financial_type_id'],
                            'installment_number' => 1,
                            'total_installments' => 1,
                            'amount' => $fee,
                            'due_date' => now()->addDays(5),
                            'status' => 'pending',
                        ]);
                    }
                });

                $termoUrl = route('contracts.termo-cancelamento', $record);

                Notification::make()
                    ->title('Contrato rescindido com sucesso!')
                    ->body('O status do contrato foi alterado para cancelado.')
                    ->success()
                    ->actions([
                        Action::make('printTermo')
                            ->label('Imprimir Termo de Rescisão')
                            ->icon('heroicon-m-printer')
                            ->url($termoUrl, shouldOpenInNewTab: true),
                    ])
                    ->send();

                // Abre automaticamente o termo em nova aba
                $livewire->js('window.open(' . json_encode($termoUrl) . ", '_blank');");
            })
            ->visible(fn (Contract $record): bool => ! in_array($record->status, ['canceled', 'finished'], true));
    }
}


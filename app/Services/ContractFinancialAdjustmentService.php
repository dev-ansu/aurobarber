<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\FinancialType;
use App\Models\Installment;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ContractFinancialAdjustmentService
{
    /**
     * Executa o ajuste de valor e reparcelamento do contrato com segurança contábil.
     *
     * @param Contract $contract
     * @param array $data
     * @return array Resumo do que foi alterado
     */
    public function adjust(Contract $contract, array $data): array
    {
        return DB::transaction(function () use ($contract, $data): array {
            $mode = $data['mode'] ?? 'recalculate';
            $calculationType = $data['calculation_type'] ?? 'divide';
            $qty = max(1, (int) ($data['qty_installments'] ?? 1));

            // Calcula o novo valor total conforme o modo de cálculo
            if ($mode === 'recalculate' && $calculationType === 'multiply') {
                $installmentValue = (float) ($data['installment_value'] ?? 0);
                $newTotal = round($installmentValue * $qty, 2);
            } else {
                $newTotal = round((float) ($data['new_total_value'] ?? 0), 2);
            }

            if ($newTotal <= 0) {
                throw new DomainException('O valor total do contrato deve ser maior que zero.');
            }

            $reason = trim($data['reason'] ?? 'Ajuste financeiro do contrato');

            // 1. Identifica parcelas já pagas (não podem ser alteradas ou excluídas)
            $paidInstallments = $contract->installments()
                ->where('status', 'paid')
                ->orderBy('installment_number')
                ->get();

            $totalPaid = (float) $paidInstallments->sum(fn (Installment $i): float => (float) ($i->amount_paid ?: $i->amount));

            if ($newTotal < $totalPaid) {
                $paidFmt = number_format($totalPaid, 2, ',', '.');
                $newFmt = number_format($newTotal, 2, ',', '.');
                throw new DomainException("O novo valor do contrato (R$ {$newFmt}) não pode ser inferior ao valor que já foi pago pelo aluno (R$ {$paidFmt}).");
            }

            $generatedInstallmentsCount = 0;
            $deletedInstallmentsCount = 0;

            if ($mode === 'recalculate') {
                $remainingToInstall = round($newTotal - $totalPaid, 2);

                $firstDueDate = Carbon::parse($data['first_due_date'] ?? today());
                $financialTypeId = $data['financial_type_id']
                    ?? FinancialType::where('slug', 'mensalidade')->value('id')
                    ?? FinancialType::where('slug', 'tuition')->value('id')
                    ?? FinancialType::value('id');

                // 2. Remove parcelas pendentes que NÃO possuem movimentação financeira no caixa
                $pendingQuery = $contract->installments()
                    ->where('status', '!=', 'paid')
                    ->whereDoesntHave('transactions');

                $deletedInstallmentsCount = $pendingQuery->count();
                $pendingQuery->delete();

                // 3. Gera as novas parcelas com cálculo exato de centavos
                if ($remainingToInstall > 0 && $qty > 0) {
                    $baseAmount = floor(($remainingToInstall / $qty) * 100) / 100;
                    $centsDiff = round($remainingToInstall - ($baseAmount * $qty), 2);
                    $totalInstallmentsCount = $paidInstallments->count() + $qty;

                    for ($i = 1; $i <= $qty; $i++) {
                        $installmentNumber = $paidInstallments->count() + $i;
                        $amount = ($i === 1) ? round($baseAmount + $centsDiff, 2) : $baseAmount;
                        $dueDate = $firstDueDate->copy()->addMonthsNoOverflow($i - 1);

                        $contract->installments()->create([
                            'financial_type_id' => $financialTypeId,
                            'installment_number' => $installmentNumber,
                            'total_installments' => $totalInstallmentsCount,
                            'amount' => $amount,
                            'due_date' => $dueDate->toDateString(),
                            'status' => 'pending',
                            'type' => 'tuition',
                        ]);

                        $generatedInstallmentsCount++;
                    }

                    // Atualiza total_installments das já pagas para consistência
                    if ($paidInstallments->isNotEmpty()) {
                        $contract->installments()
                            ->where('status', 'paid')
                            ->update(['total_installments' => $totalInstallmentsCount]);
                    }
                }
            }

            // 4. Atualiza o valor total no contrato
            $oldTotal = (float) $contract->total_value;
            $contract->update([
                'total_value' => $newTotal,
            ]);

            // 5. Registra no histórico do contrato
            $oldFmt = number_format($oldTotal, 2, ',', '.');
            $newFmt = number_format($newTotal, 2, ',', '.');
            $historyReason = "Ajuste financeiro do contrato: valor alterado de R$ {$oldFmt} para R$ {$newFmt}. Motivo: {$reason}";

            $contract->statusHistories()->create([
                'user_id' => Auth::id(),
                'from_status' => $contract->status,
                'to_status' => $contract->status,
                'reason' => $historyReason,
            ]);

            if (! empty($reason)) {
                $prefix = $contract->observations ? $contract->observations . "\n" : '';
                $contract->update([
                    'observations' => trim($prefix . '[' . now()->format('d/m/Y H:i') . '] ' . $historyReason),
                ]);
            }

            return [
                'old_total' => $oldTotal,
                'new_total' => $newTotal,
                'mode' => $mode,
                'paid_count' => $paidInstallments->count(),
                'paid_amount' => $totalPaid,
                'deleted_count' => $deletedInstallmentsCount,
                'generated_count' => $generatedInstallmentsCount,
            ];
        });
    }
}


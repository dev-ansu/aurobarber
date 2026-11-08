<?php

namespace App\Services;

use App\Models\CashRegister;
use App\Models\CashTransaction;
use App\Models\Contract;
use App\Models\ContractHistoryType;
use App\Models\DebtAgreement;
use App\Models\FinancialType;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DebtAgreementService
{
    /**
     * Cria um novo acordo de renegociação de dívida para o contrato.
     *
     * @param  array{
     *     installment_ids: array<int>,
     *     discount_amount?: float|string|null,
     *     down_payment_amount?: float|string|null,
     *     down_payment_paid?: bool|null,
     *     payment_method_id?: int|null,
     *     installments_count?: int|null,
     *     first_due_date?: string|Carbon|null,
     *     financial_type_id?: int|null,
     *     notes?: string|null
     * }  $data
     */
    public function createAgreement(Contract $contract, array $data, ?User $user = null): DebtAgreement
    {
        return DB::transaction(function () use ($contract, $data, $user) {
            $selectedIds = array_map('intval', (array) ($data['installment_ids'] ?? []));
            if (empty($selectedIds)) {
                throw new InvalidArgumentException('Selecione ao menos uma parcela para renegociação.');
            }

            $originalInstallments = $contract->installments()
                ->whereIn('id', $selectedIds)
                ->whereIn('status', ['pending', 'partial'])
                ->get();

            if ($originalInstallments->isEmpty()) {
                throw new InvalidArgumentException('Nenhuma parcela pendente válida encontrada entre as selecionadas.');
            }

            $totalOriginal = (float) $originalInstallments->sum('amount');
            $discount = max(0, (float) ($data['discount_amount'] ?? 0));

            if ($discount > $totalOriginal) {
                throw new InvalidArgumentException('O desconto não pode ser maior que o valor total original.');
            }

            $totalAgreed = max(0, $totalOriginal - $discount);
            $downPayment = max(0, (float) ($data['down_payment_amount'] ?? 0));

            if ($downPayment > $totalAgreed) {
                throw new InvalidArgumentException('O valor da entrada não pode ser superior ao valor total acordado.');
            }

            $remainingBalance = max(0, round($totalAgreed - $downPayment, 2));
            $installmentsCount = max(1, (int) ($data['installments_count'] ?? 1));
            $firstDueDate = Carbon::parse($data['first_due_date'] ?? now()->addMonth());

            $likeOp = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

            // Identifica o tipo financeiro padrão
            $financialTypeId = $data['financial_type_id']
                ?? $originalInstallments->first()?->financial_type_id
                ?? FinancialType::query()->where('name', $likeOp, '%acordo%')->value('id')
                ?? FinancialType::first()?->id;

            // 1. Cancela as parcelas originais renegociadas
            foreach ($originalInstallments as $orig) {
                $orig->update([
                    'status' => 'canceled',
                ]);
            }

            $newInstallmentIds = [];
            $totalNewCount = ($downPayment > 0 ? 1 : 0) + ($remainingBalance > 0 ? $installmentsCount : 0);
            $currentNum = 1;

            // 2. Se houver entrada, gera a parcela correspondente
            if ($downPayment > 0) {
                $downPaymentPaid = (bool) ($data['down_payment_paid'] ?? false);
                $paymentMethodId = $data['payment_method_id'] ?? null;

                $dpInstallment = $contract->installments()->create([
                    'financial_type_id' => $financialTypeId,
                    'payment_method_id' => $paymentMethodId,
                    'installment_number' => $currentNum,
                    'total_installments' => $totalNewCount,
                    'amount' => $downPayment,
                    'discount' => 0.00,
                    'fine' => 0.00,
                    'amount_paid' => $downPaymentPaid ? $downPayment : null,
                    'due_date' => now()->toDateString(),
                    'paid_at' => $downPaymentPaid ? now()->toDateString() : null,
                    'status' => $downPaymentPaid ? 'paid' : 'pending',
                    'type' => 'tuition',
                ]);

                $newInstallmentIds[] = $dpInstallment->id;
                $currentNum++;

                // Se a entrada foi paga no ato e o operador tem caixa aberto, registra a transação
                if ($downPaymentPaid && $user) {
                    $cashRegister = CashRegister::where('user_id', $user->id)
                        ->where('status', 'open')
                        ->when($contract->school_id, fn ($q, $sid) => $q->where('school_id', $sid))
                        ->first();

                    if ($cashRegister) {
                        $numContrato = $contract->contract_number ?? "#{$contract->id}";
                        $nomeAluno = $contract->student?->name ?? 'Aluno';
                        CashTransaction::create([
                            'cash_register_id' => $cashRegister->id,
                            'user_id' => $user->id,
                            'financial_type_id' => $financialTypeId,
                            'installment_id' => $dpInstallment->id,
                            'type' => 'input',
                            'amount' => $downPayment,
                            'payment_method_id' => $paymentMethodId,
                            'description' => "Entrada de Acordo | Contrato: {$numContrato} | Aluno: {$nomeAluno}",
                        ]);
                        $cashRegister->increment('current_balance', $downPayment);
                    }
                }
            }

            // 3. Se houver saldo restante, parcela em N vezes
            if ($remainingBalance > 0) {
                $baseAmount = floor(($remainingBalance / $installmentsCount) * 100) / 100;
                $centsDifference = round($remainingBalance - ($baseAmount * $installmentsCount), 2);

                for ($i = 0; $i < $installmentsCount; $i++) {
                    $installmentAmount = $i === 0 ? ($baseAmount + $centsDifference) : $baseAmount;
                    $dueDate = $firstDueDate->copy()->addMonthsNoOverflow($i);

                    $newInst = $contract->installments()->create([
                        'financial_type_id' => $financialTypeId,
                        'installment_number' => $currentNum,
                        'total_installments' => $totalNewCount,
                        'amount' => $installmentAmount,
                        'discount' => 0.00,
                        'fine' => 0.00,
                        'amount_paid' => null,
                        'due_date' => $dueDate->toDateString(),
                        'status' => 'pending',
                        'type' => 'tuition',
                    ]);

                    $newInstallmentIds[] = $newInst->id;
                    $currentNum++;
                }
            }

            // 4. Cria o registro do DebtAgreement
            $agreementCount = DebtAgreement::where('contract_id', $contract->id)->count() + 1;
            $agreementNumber = sprintf('AC-%s-%03d', $contract->contract_number ?: $contract->id, $agreementCount);

            $debtAgreement = DebtAgreement::create([
                'contract_id' => $contract->id,
                'agreement_number' => $agreementNumber,
                'user_id' => $user?->id,
                'total_original_amount' => $totalOriginal,
                'discount_amount' => $discount,
                'total_agreed_amount' => $totalAgreed,
                'down_payment_amount' => $downPayment,
                'down_payment_paid' => (bool) ($data['down_payment_paid'] ?? false),
                'installments_count' => $installmentsCount,
                'renegotiated_installment_ids' => $selectedIds,
                'new_installment_ids' => $newInstallmentIds,
                'notes' => $data['notes'] ?? null,
                'status' => 'active',
            ]);

            // 5. Registra no histórico de acompanhamento do contrato
            if (method_exists($contract, 'histories')) {
                $typeSlug = 'financial';
                if (! ContractHistoryType::where('slug', $typeSlug)->exists()) {
                    $typeSlug = ContractHistoryType::value('slug') ?? 'general';
                }

                $contract->histories()->create([
                    'user_id' => $user?->id,
                    'type' => $typeSlug,
                    'occurred_at' => now(),
                    'description' => "Termo de Acordo {$agreementNumber} formalizado. Dívida original: R$ ".number_format($totalOriginal, 2, ',', '.').', Desconto: R$ '.number_format($discount, 2, ',', '.').', Acordo: R$ '.number_format($totalAgreed, 2, ',', '.').', Entrada: R$ '.number_format($downPayment, 2, ',', '.')." em {$installmentsCount} parcela(s).",
                ]);
            }

            return $debtAgreement;
        });
    }
}

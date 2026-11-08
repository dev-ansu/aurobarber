<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Installment;
use App\Models\School;
use Carbon\Carbon;
use Filament\Facades\Filament;

class FinancialCalculationService
{
    /**
     * Calcula encargos por atraso (multa moratória e juros diários) com base nas políticas da escola.
     *
     * @return array{
     *     is_overdue: bool,
     *     days_late: int,
     *     fine_amount: float,
     *     interest_amount: float,
     *     total_late_charges: float,
     *     explanation: string
     * }
     */
    public function calculateOverdueCharges(Installment $installment, ?Carbon $paymentDate = null, ?School $school = null): array
    {
        $school ??= $installment->school
            ?? $installment->contract?->school
            ?? (class_exists(Filament::class) ? Filament::getTenant() : null)
            ?? School::getActiveSchool()
            ?? School::query()->first();

        $policy = $school instanceof School
            ? $school->resolveFinancialPolicy()
            : [
                'late_fine_percentage' => (float) ($school?->late_fine_percentage ?? 2.00),
                'daily_interest_percentage' => (float) ($school?->daily_interest_percentage ?? 0.0333),
                'grace_days' => (int) ($school?->grace_days ?? 0),
            ];

        $paymentDate = ($paymentDate ? $paymentDate->copy() : Carbon::today())->startOfDay();
        $dueDate = Carbon::parse($installment->due_date)->startOfDay();

        $graceDays = (int) ($policy['grace_days'] ?? 0);
        $toleranceLimit = $dueDate->copy()->addDays($graceDays);

        $daysLate = 0;
        $fineAmount = 0.0;
        $interestAmount = 0.0;
        $isOverdue = false;
        $explanation = '';

        if ($paymentDate->greaterThan($toleranceLimit)) {
            $isOverdue = true;
            $daysLate = (int) $dueDate->diffInDays($paymentDate);
            $baseAmount = (float) $installment->amount;

            // Multa padrão (ex: 2.0%)
            $finePct = (float) ($policy['late_fine_percentage'] ?? 2.00);
            $fineAmount = round($baseAmount * ($finePct / 100), 2);

            // Juros de mora diário (ex: 0.0333% ao dia = ~1% ao mês)
            $dailyInterestPct = (float) ($policy['daily_interest_percentage'] ?? 0.0333);
            $interestAmount = round($baseAmount * ($dailyInterestPct / 100) * $daysLate, 2);

            $fineFormatted = number_format($fineAmount, 2, ',', '.');
            $interestFormatted = number_format($interestAmount, 2, ',', '.');
            $explanation = "Atraso de {$daysLate} dia(s). Multa ({$finePct}%): R$ {$fineFormatted} + Juros ({$daysLate}d): R$ {$interestFormatted}.";
        }

        $totalCharges = round($fineAmount + $interestAmount, 2);

        return [
            'is_overdue' => $isOverdue,
            'days_late' => $daysLate,
            'fine_amount' => $fineAmount,
            'interest_amount' => $interestAmount,
            'total_late_charges' => $totalCharges,
            'explanation' => $explanation,
        ];
    }

    /**
     * Calcula o desconto de pontualidade se o pagamento ocorrer até o vencimento / tolerância.
     *
     * @return array{
     *     is_enabled: bool,
     *     is_eligible: bool,
     *     discount_amount: float,
     *     limit_date: string,
     *     explanation: string
     * }
     */
    public function calculatePunctualityDiscount(Installment $installment, ?Carbon $paymentDate = null, ?School $school = null): array
    {
        $school ??= $installment->school
            ?? $installment->contract?->school
            ?? (class_exists(Filament::class) ? Filament::getTenant() : null)
            ?? School::getActiveSchool()
            ?? School::query()->first();

        $policy = $school instanceof School
            ? $school->resolveFinancialPolicy()
            : [
                'punctuality_discount_enabled' => (bool) ($school?->punctuality_discount_enabled ?? false),
                'punctuality_discount_days_tolerance' => (int) ($school?->punctuality_discount_days_tolerance ?? 0),
                'punctuality_discount_type' => $school?->punctuality_discount_type ?? 'fixed',
                'punctuality_discount_value' => (float) ($school?->punctuality_discount_value ?? 0.0),
            ];

        $paymentDate = ($paymentDate ? $paymentDate->copy() : Carbon::today())->startOfDay();
        $dueDate = Carbon::parse($installment->due_date)->startOfDay();

        $isEnabled = (bool) ($policy['punctuality_discount_enabled'] ?? false);
        $toleranceDays = (int) ($policy['punctuality_discount_days_tolerance'] ?? 0);
        $limitDate = $dueDate->copy()->addDays($toleranceDays);

        $isEligible = $isEnabled && $paymentDate->lessThanOrEqualTo($limitDate);
        $discountAmount = 0.0;
        $explanation = '';

        if ($isEnabled) {
            $baseAmount = (float) $installment->amount;
            $type = $policy['punctuality_discount_type'] ?? 'fixed';
            $val = (float) ($policy['punctuality_discount_value'] ?? 0.0);

            if ($type === 'percentage') {
                $calculated = round($baseAmount * ($val / 100), 2);
            } else {
                $calculated = min($val, $baseAmount);
            }

            if ($isEligible) {
                $discountAmount = $calculated;
                $discFormatted = number_format($discountAmount, 2, ',', '.');
                $limitFormatted = $limitDate->format('d/m/Y');
                $explanation = "Desconto pontualidade elegível: R$ {$discFormatted} para pagamento até {$limitFormatted}.";
            } else {
                $explanation = 'Data de pagamento ultrapassou o limite do desconto de pontualidade.';
            }
        }

        return [
            'is_enabled' => $isEnabled,
            'is_eligible' => $isEligible,
            'discount_amount' => $discountAmount,
            'limit_date' => $limitDate->toDateString(),
            'explanation' => $explanation,
        ];
    }

    /**
     * Localiza o melhor número de contato de WhatsApp para uma parcela (responsável ou aluno).
     */
    public function resolveContactPhone(Installment $installment): ?string
    {
        return $installment->contract ? $this->resolveContractContactPhone($installment->contract) : null;
    }

    /**
     * Localiza o melhor número de contato de WhatsApp para um contrato (responsável ou aluno).
     */
    public function resolveContractContactPhone(\App\Models\Contract $contract): ?string
    {
        // 1. Tentar primeiro o responsável financeiro se houver, depois o aluno
        $person = $contract->responsible?->person ?? ($contract->student instanceof \App\Models\Person ? $contract->student : $contract->student?->person);
        if (! $person) {
            return null;
        }

        $contacts = $person->contacts()->get();

        // Ordem de preferência: whatsapp, cellphone, phone
        $preferredTypes = ['whatsapp', 'cellphone', 'phone'];
        foreach ($preferredTypes as $targetType) {
            $contact = $contacts->first(function ($c) use ($targetType) {
                $valType = is_object($c->type) ? ($c->type->value ?? '') : (string) $c->type;

                return strtolower($valType) === $targetType && filled($c->value);
            });

            if ($contact) {
                return $this->formatInternationalPhone((string) $contact->value);
            }
        }

        // Se tiver qualquer contato preenchido
        $fallback = $contacts->first(fn ($c) => filled($c->value));
        if ($fallback) {
            return $this->formatInternationalPhone((string) $fallback->value);
        }

        return null;
    }

    /**
     * Formata um número de telefone com código DDI do Brasil (+55).
     */
    public function formatInternationalPhone(string $rawPhone): string
    {
        $digits = preg_replace('/\D/', '', $rawPhone) ?? '';

        if (str_starts_with($digits, '55') && strlen($digits) >= 12) {
            return $digits;
        }

        if (strlen($digits) === 10 || strlen($digits) === 11) {
            return '55'.$digits;
        }

        return $digits;
    }
}

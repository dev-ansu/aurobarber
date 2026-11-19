<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SaasPaymentReceipt;
use App\Models\SaasPaymentSubmission;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaasPaymentReviewService
{
    public function approve(
        SaasPaymentSubmission $submission,
        string $confirmedAmount,
        ?string $receivedOn = null,
        ?int $renewalDays = null,
    ): SaasPaymentSubmission {
        $confirmedAmount = trim($confirmedAmount);
        if (! preg_match('/^\d{1,10}(?:\.\d{1,2})?$/', $confirmedAmount) || (float) $confirmedAmount <= 0) {
            throw ValidationException::withMessages(['confirmed_amount' => 'Informe um valor recebido maior que zero.']);
        }

        $receivedOnValue = $receivedOn ?? now()->toDateString();
        try {
            if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $receivedOnValue)) {
                throw new \InvalidArgumentException;
            }

            $receivedDate = CarbonImmutable::parse($receivedOnValue)->startOfDay();
            if ($receivedDate->toDateString() !== $receivedOnValue) {
                throw new \InvalidArgumentException;
            }
        } catch (\Throwable) {
            throw ValidationException::withMessages(['received_on' => 'Informe uma data de recebimento válida.']);
        }

        if ($receivedDate->isAfter(now()->endOfDay())) {
            throw ValidationException::withMessages(['received_on' => 'A data do recebimento não pode estar no futuro.']);
        }

        return DB::transaction(function () use ($submission, $confirmedAmount, $receivedDate, $renewalDays): SaasPaymentSubmission {
            $locked = SaasPaymentSubmission::query()->lockForUpdate()->findOrFail($submission->id);

            if ($locked->status !== 'pending') {
                throw ValidationException::withMessages(['payment' => 'Este comprovante já foi analisado.']);
            }

            $barbershop = $locked->barbershop;
            if (! $barbershop || $barbershop->trashed()) {
                throw ValidationException::withMessages(['payment' => 'A barbearia vinculada não está disponível para renovação.']);
            }

            $days = $renewalDays && $renewalDays > 0
                ? $renewalDays
                : $barbershop->effectiveRenewalDays();

            $barbershop->renew($days);

            $locked->update([
                'status' => 'approved',
                'review_note' => null,
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
            ]);

            SaasPaymentReceipt::query()->firstOrCreate(
                ['payment_submission_id' => $locked->id],
                [
                    'barbershop_id' => $barbershop->id,
                    'amount' => $confirmedAmount,
                    'received_on' => $receivedDate->toDateString(),
                    'competence' => $receivedDate->startOfMonth()->toDateString(),
                    'recorded_by' => Auth::id(),
                ],
            );

            return $locked->fresh();
        });
    }

    public function reject(SaasPaymentSubmission $submission, string $reason): SaasPaymentSubmission
    {
        return DB::transaction(function () use ($submission, $reason): SaasPaymentSubmission {
            $locked = SaasPaymentSubmission::query()->lockForUpdate()->findOrFail($submission->id);

            if ($locked->status !== 'pending') {
                throw ValidationException::withMessages(['payment' => 'Este comprovante já foi analisado.']);
            }

            $locked->update([
                'status' => 'rejected',
                'review_note' => trim($reason),
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
            ]);

            return $locked->fresh();
        });
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Filament\Pages\ClientSubscriptionPage;
use App\Models\Barbershop;
use App\Models\SaasPaymentSubmission;
use App\Services\SaasPaymentProofStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TenantSaasPaymentController extends Controller
{
    public function store(Request $request, string $tenant): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && ClientSubscriptionPage::canAccess(), 403);

        $barbershop = Barbershop::query()->where('slug', $tenant)->firstOrFail();
        abort_unless($user->canAccessTenant($barbershop), 403);

        $withinPaymentWindow = $barbershop->license_expires_at !== null
            && Carbon::parse($barbershop->license_expires_at)->startOfDay()->lte(today()->addDays(5));
        $isBlockedOrGrace = ! $barbershop->isLicenseActive() || $barbershop->isInGracePeriod();
        $earlyPaymentWasRequested = (bool) $request->session()->get('saas_early_payment_authorized_'.$barbershop->id, false);
        $latestSubmissionWasRejected = SaasPaymentSubmission::query()
            ->where('barbershop_id', $barbershop->id)
            ->latest('id')
            ->value('status') === 'rejected';

        abort_unless(
            $withinPaymentWindow || $isBlockedOrGrace || $earlyPaymentWasRequested || $latestSubmissionWasRejected,
            403,
            'O pagamento fica disponível nos cinco dias anteriores ao vencimento. Você também pode antecipá-lo em Planos de licenciamento.',
        );

        $validated = $request->validate([
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
        ]);

        $hasPendingSubmission = SaasPaymentSubmission::query()
            ->where('barbershop_id', $barbershop->id)
            ->where('status', 'pending')
            ->exists();

        if ($hasPendingSubmission) {
            throw ValidationException::withMessages([
                'proof' => 'Já existe um comprovante aguardando validação para esta barbearia.',
            ]);
        }

        try {
            $path = app(SaasPaymentProofStorage::class)->store($validated['proof'], (int) $barbershop->id);
        } catch (\Throwable $exception) {
            report($exception);
            throw ValidationException::withMessages([
                'proof' => 'Não foi possível gravar o arquivo no armazenamento privado. Tente novamente ou entre em contato com o suporte.',
            ]);
        }

        try {
            DB::transaction(fn () => SaasPaymentSubmission::query()->create([
                'barbershop_id' => $barbershop->id,
                'saas_plan_id' => $barbershop->saas_plan_id,
                'submitted_by_name' => $user->name,
                'submitted_by_email' => $user->email,
                'amount' => $barbershop->effectiveMonthlyPrice(),
                'proof_path' => $path,
                'status' => 'pending',
            ]));
        } catch (\Throwable $exception) {
            app(SaasPaymentProofStorage::class)->delete($path);
            throw $exception;
        }

        $request->session()->forget('saas_early_payment_authorized_'.$barbershop->id);

        return back()->with('status', 'Comprovante enviado! A licença será renovada após a validação do pagamento pela equipe Aurosoft.');
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Barbershop;
use App\Models\SaasBillingSetting;
use App\Models\SaasPaymentSubmission;
use App\Models\SaasPlan;
use App\Models\SaasPlanChangeRequest;
use App\Services\PixService;
use App\Services\SaasSupportContact;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ClientSubscriptionPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static string|\UnitEnum|null $navigationGroup = 'Financeiro';

    protected static ?string $navigationLabel = 'Planos de licenciamento';

    protected static ?string $title = 'Planos de licenciamento';

    protected static ?string $slug = 'planos-de-licenciamento';

    protected static ?int $navigationSort = 90;

    protected string $view = 'filament.pages.client-subscription-page';

    public bool $earlyPaymentRequested = false;

    public string $activeTab = 'assinatura';

    /** @var array<string, mixed> */
    protected $queryString = [
        'activeTab' => ['except' => 'assinatura', 'as' => 'aba'],
    ];

    public static function canAccess(): bool
    {
        return auth()->check() && ! auth()->user()?->isRestrictedBarber();
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);

        $shop = $this->barbershop;
        if ($shop && session()->get($this->earlyPaymentSessionKey(), false)) {
            $this->earlyPaymentRequested = true;
        }
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::FiveExtraLarge;
    }

    public function getBarbershopProperty(): ?Barbershop
    {
        $tenant = filament()->getTenant();

        return $tenant instanceof Barbershop ? $tenant->loadMissing('saasPlan') : null;
    }

    public function getActivePlansProperty(): Collection
    {
        return SaasPlan::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function getBillingSettingsProperty(): ?SaasBillingSetting
    {
        $settings = SaasBillingSetting::query()->find(1);

        return $settings?->pix_enabled && filled($settings->pix_key) ? $settings : null;
    }

    public function getSupportPhoneProperty(): string
    {
        $billingWhatsapp = SaasBillingSetting::query()->find(1)?->support_whatsapp;
        if (filled($billingWhatsapp)) {
            return (string) $billingWhatsapp;
        }

        return app(SaasSupportContact::class)->phone() ?: '5585999728450';
    }

    public function getLatestPaymentSubmissionProperty(): ?SaasPaymentSubmission
    {
        $shop = $this->barbershop;
        if (! $shop) {
            return null;
        }

        return SaasPaymentSubmission::query()
            ->where('barbershop_id', $shop->id)
            ->latest('id')
            ->first();
    }

    public function getPaymentSubmissionsProperty(): Collection
    {
        $shop = $this->barbershop;
        if (! $shop) {
            return collect();
        }

        return SaasPaymentSubmission::query()
            ->where('barbershop_id', $shop->id)
            ->with('saasPlan')
            ->latest('id')
            ->limit(25)
            ->get();
    }

    public function getOpenPlanChangeRequestProperty(): ?SaasPlanChangeRequest
    {
        $shop = $this->barbershop;
        if (! $shop) {
            return null;
        }

        return SaasPlanChangeRequest::query()
            ->where('barbershop_id', $shop->id)
            ->whereIn('status', ['pending', 'contacted'])
            ->with('requestedPlan')
            ->latest('id')
            ->first();
    }

    public function getPaymentWindowOpenProperty(): bool
    {
        $shop = $this->barbershop;
        if (! $shop) {
            return false;
        }

        if (! $shop->isLicenseActive() || $shop->isInGracePeriod()) {
            return true;
        }

        $expiresAt = $shop->license_expires_at;

        return $expiresAt !== null
            && Carbon::parse($expiresAt)->startOfDay()->lte(today()->addDays(5));
    }

    public function getPaymentSectionVisibleProperty(): bool
    {
        return $this->paymentWindowOpen
            || $this->earlyPaymentRequested
            || in_array($this->latestPaymentSubmission?->status, ['pending', 'rejected'], true);
    }

    public function showEarlyPayment(): void
    {
        abort_unless(static::canAccess(), 403);

        $this->earlyPaymentRequested = true;
        if ($this->barbershop) {
            session()->put($this->earlyPaymentSessionKey(), true);
        }
    }

    public function earlyPaymentSessionKey(): string
    {
        return 'saas_early_payment_authorized_'.($this->barbershop?->id ?? 0);
    }

    /**
     * @return array{key: string, payload: string, qr_code_uri: string, amount: float}|null
     */
    public function getPixPaymentDetailsProperty(): ?array
    {
        $shop = $this->barbershop;
        $settings = $this->billingSettings;

        if (! $shop || ! $settings || ! $this->paymentSectionVisible) {
            return null;
        }

        $amount = $shop->effectiveMonthlyPrice();
        $txid = substr('AUROBARBER'.$shop->id.now()->format('ym'), 0, 25);
        $pix = app(PixService::class);
        $payload = $pix->generatePayload(
            pixKey: (string) $settings->pix_key,
            merchantName: $settings->merchant_name ?: 'AUROSOFT',
            merchantCity: $settings->merchant_city ?: 'FORTALEZA',
            amount: $amount,
            txid: $txid,
            description: 'Mensalidade AuroBarber',
        );

        return [
            'key' => (string) $settings->pix_key,
            'payload' => $payload,
            'qr_code_uri' => $pix->generateQrCodeDataUri($payload),
            'amount' => $amount,
        ];
    }

    public function requestPlanChange(int $planId): void
    {
        abort_unless(static::canAccess(), 403);

        $shop = $this->barbershop;
        if (! $shop) {
            return;
        }

        $plan = SaasPlan::query()->where('is_active', true)->findOrFail($planId);

        if ((int) $shop->saas_plan_id === (int) $plan->id) {
            Notification::make()
                ->title('Este já é o plano atual da barbearia')
                ->info()
                ->send();

            return;
        }

        $hasOpenRequest = SaasPlanChangeRequest::query()
            ->where('barbershop_id', $shop->id)
            ->where('requested_plan_id', $plan->id)
            ->whereIn('status', ['pending', 'contacted'])
            ->exists();

        if ($hasOpenRequest) {
            Notification::make()
                ->title('Solicitação já enviada')
                ->body("Nossa equipe já recebeu seu pedido para o {$plan->name} e entrará em contato em breve.")
                ->info()
                ->send();

            return;
        }

        $user = auth()->user();
        SaasPlanChangeRequest::query()->create([
            'barbershop_id' => $shop->id,
            'current_plan_id' => $shop->saas_plan_id,
            'requested_plan_id' => $plan->id,
            'requested_by_name' => $user?->name,
            'requested_by_email' => $user?->email,
            'status' => 'pending',
            'notes' => 'Solicitação enviada pelo painel da barbearia. Plano atual: '.($shop->saasPlan?->name ?: 'Personalizado').". Plano solicitado: {$plan->name}.",
        ]);

        Notification::make()
            ->title('Solicitação enviada para análise!')
            ->body("Recebemos seu pedido para o {$plan->name}. Seu plano atual permanece ativo até a confirmação com a equipe Aurosoft.")
            ->success()
            ->persistent()
            ->send();
    }
}

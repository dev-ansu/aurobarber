<?php

declare(strict_types=1);

namespace App\Filament\Saas\Pages;

use App\Models\License;
use App\Models\PortalTrafficEvent;
use App\Services\AuroDoctorService;
use App\Services\TenantService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard;

class SaasDashboard extends Dashboard
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?string $navigationLabel = 'Visão Geral SaaS';

    protected static ?string $title = 'Painel de Controle SaaS Master';

    protected static ?int $navigationSort = 1;

    protected static string $routePath = '/';

    protected string $view = 'filament.saas.pages.dashboard';

    public int $totalClients = 0;
    public int $activeLicenses = 0;
    public int $trialLicenses = 0;
    public int $suspendedLicenses = 0;
    public float $monthlyRecurringRevenue = 0.0;
    public int $provisionedSchemasCount = 0;

    /** @var array<string, mixed>|null */
    public ?array $doctorReport = null;

    public bool $isDiagnosing = false;

    /** @var array<string, mixed>|null */
    public ?array $demoTenantInfo = null;

    /** @var array<int, array<string, mixed>> */
    public array $allTenants = [];

    // Métricas Nativas de Tráfego & Captação da Landing Page Aurosoft
    public int $landingVisitors30d = 0;
    public int $landingVisitorsToday = 0;
    public int $landingDemoClicks = 0;
    public int $landingLeadsSubmitted = 0;
    public float $landingConversionRate = 0.0;
    /** @var array<int, array<string, mixed>> */
    public array $landingTopSources = [];
    /** @var array<string, int> */
    public array $landingDevices = [];

    public function mount(): void
    {
        $this->loadMetrics();
    }

    public function loadMetrics(): void
    {
        $this->totalClients = License::count();
        $this->activeLicenses = License::where('status', 'active')->count();
        $this->trialLicenses = License::where('status', 'trial')->count();
        $this->suspendedLicenses = License::whereIn('status', ['suspended', 'expired', 'cancelled'])->count();
        $this->monthlyRecurringRevenue = (float) License::where('status', 'active')->sum('monthly_price');

        $this->loadTenantsInfo();
        $this->loadDoctorReport();
        $this->loadLandingTrafficMetrics();
    }

    public function loadLandingTrafficMetrics(): void
    {
        try {
            $this->landingVisitors30d = PortalTrafficEvent::aurosoft()->lastDays(30)->distinct('visitor_hash')->count('visitor_hash');
            $this->landingVisitorsToday = PortalTrafficEvent::aurosoft()->today()->distinct('visitor_hash')->count('visitor_hash');
            $this->landingDemoClicks = PortalTrafficEvent::aurosoft()->lastDays(30)->where('event_type', 'cta_demo')->count();
            $this->landingLeadsSubmitted = PortalTrafficEvent::aurosoft()->lastDays(30)->where('event_type', 'lead_submit')->count();

            if ($this->landingVisitors30d > 0) {
                $this->landingConversionRate = round((($this->landingDemoClicks + $this->landingLeadsSubmitted) / $this->landingVisitors30d) * 100, 1);
            }

            $this->landingTopSources = PortalTrafficEvent::aurosoft()
                ->lastDays(30)
                ->select('utm_source', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
                ->groupBy('utm_source')
                ->orderByDesc('count')
                ->take(4)
                ->get()
                ->map(fn ($item) => ['source' => $item->utm_source ?: 'direto', 'count' => (int) $item->count])
                ->toArray();

            $deviceData = PortalTrafficEvent::aurosoft()
                ->lastDays(30)
                ->select('device_type', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
                ->groupBy('device_type')
                ->get();

            $this->landingDevices = [];
            foreach ($deviceData as $d) {
                $this->landingDevices[$d->device_type ?: 'desktop'] = (int) $d->count;
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::debug('Erro ao carregar métricas de tráfego SaaS: ' . $e->getMessage());
        }
    }

    public function loadDoctorReport(): void
    {
        $this->doctorReport = app(AuroDoctorService::class)->getLatestReport();
    }

    public function runDoctor(): void
    {
        $this->isDiagnosing = true;

        try {
            $doctorService = app(AuroDoctorService::class);
            $this->doctorReport = $doctorService->diagnose();

            $status = $this->doctorReport['status'] ?? 'healthy';
            $score = $this->doctorReport['health_score'] ?? 100;

            if ($status === 'healthy') {
                Notification::make()
                    ->title("AuroDoctor: Sistema 100% Saudável!")
                    ->body("Todas as {$this->doctorReport['total_checks']} checagens foram concluídas com sucesso em {$this->doctorReport['duration_seconds']}s.")
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title("AuroDoctor: Diagnóstico Concluído ({$score}% Saúde)")
                    ->body("Atenção: {$this->doctorReport['failed']} falha(s) detectada(s). Verifique o relatório clínico abaixo.")
                    ->warning()
                    ->send();
            }
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Erro ao executar AuroDoctor')
                ->body($e->getMessage())
                ->danger()
                ->send();
        } finally {
            $this->isDiagnosing = false;
        }
    }

    public function loadTenantsInfo(): void
    {
        try {
            $tenantService = app(TenantService::class);
            $this->allTenants = $tenantService->listTenants();
            $this->demoTenantInfo = collect($this->allTenants)->firstWhere('name', 'demo');
            $this->provisionedSchemasCount = count($this->allTenants);
        } catch (\Throwable) {
            $this->allTenants = [];
            $this->demoTenantInfo = null;
            $this->provisionedSchemasCount = 0;
        }
    }

    public function resetDemo(): void
    {
        try {
            $tenantService = app(TenantService::class);
            $result = $tenantService->resetDemoTenant();
            $this->loadMetrics();

            Notification::make()
                ->title('Ambiente Demo Resetado com Sucesso!')
                ->body("Schema 'demo' limpo e repovoado com dados novinhos em {$result['duration_ms']} ms.")
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Erro ao resetar ambiente Demo')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}

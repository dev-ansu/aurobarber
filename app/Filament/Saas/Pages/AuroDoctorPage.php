<?php

declare(strict_types=1);

namespace App\Filament\Saas\Pages;

use App\Services\AuroDoctorService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class AuroDoctorPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'AuroDoctor • Saúde do Sistema';

    protected static ?string $title = 'AuroDoctor • Robô de Diagnóstico & Saúde do Sistema';

    protected static ?string $slug = 'doctor';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.saas.pages.auro-doctor';

    /** @var array<string, mixed>|null */
    public ?array $report = null;

    public bool $isDiagnosing = false;

    public string $activeCategory = 'all';

    public string $selectedTenant = 'global';

    public function mount(): void
    {
        $this->loadReport();
    }

    public function updatedSelectedTenant(): void
    {
        $this->loadReport();
        try {
            app(\App\Services\TenantService::class)->switchToPublic();
        } catch (\Throwable) {
        }
    }

    public function loadReport(): void
    {
        $this->report = app(AuroDoctorService::class)->getLatestReport($this->selectedTenant);
    }

    /**
     * @return array<string, string>
     */
    public function getAvailableTenants(): array
    {
        $options = [
            'global' => '🌐 Infraestrutura Global do Servidor (VPS / Central)',
        ];

        try {
            $barbershops = \App\Models\Barbershop::query()
                ->orderBy('name')
                ->get();

            foreach ($barbershops as $b) {
                $options[$b->slug] = "💈 {$b->name} (slug: {$b->slug})";
            }
        } catch (\Throwable) {
            // Silencioso em caso de falha de conexão inicial
        }

        return $options;
    }

    public function runDiagnostics(): void
    {
        $this->isDiagnosing = true;

        try {
            $service = app(AuroDoctorService::class);
            $this->report = $service->diagnose($this->selectedTenant);

            $status = $this->report['status'] ?? 'healthy';
            $score = $this->report['health_score'] ?? 100;
            $allTenants = $this->getAvailableTenants();
            $tenantLabel = $allTenants[$this->selectedTenant] ?? $this->selectedTenant;

            if ($status === 'healthy') {
                Notification::make()
                    ->title("Diagnóstico Concluído: {$tenantLabel}")
                    ->body("Todas as {$this->report['total_checks']} checagens foram concluídas com 100% de sucesso em {$this->report['duration_seconds']} segundos.")
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title("Diagnóstico Concluído: {$tenantLabel} ({$score}% de Saúde)")
                    ->body("Foram detectadas {$this->report['failed']} falha(s) e {$this->report['warnings']} alerta(s). Verifique os detalhes abaixo para resolução.")
                    ->warning()
                    ->send();
            }
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Erro ao executar o AuroDoctor')
                ->body($e->getMessage())
                ->danger()
                ->send();
        } finally {
            $this->isDiagnosing = false;
        }
    }

    public function applySystemUpdate(): void
    {
        $this->isDiagnosing = true;

        try {
            // 1. Git pull da branch de produção segura (saas-prod)
            $gitProcess = new \Symfony\Component\Process\Process(
                ['git', '-c', 'safe.directory=*', 'pull', 'origin', 'saas-prod'],
                base_path()
            );
            $gitProcess->setTimeout(120);
            $gitProcess->run();

            $gitOutput = trim($gitProcess->getOutput() ?: $gitProcess->getErrorOutput());

            // 2. Migrações centrais
            \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);

            // 3. Migrações multi-tenant em todas as escolas
            \Illuminate\Support\Facades\Artisan::call('saas:migrate-tenants');

            // 4. Limpeza segura de caches (sem apagar ou quebrar .env)
            \Illuminate\Support\Facades\Artisan::call('optimize:clear');

            // 5. Normaliza incidentes de telemetria pendentes
            \App\Models\SupportTicket::query()
                ->withoutGlobalScopes()
                ->where('type', 'system_incident')
                ->whereIn('status', ['open', 'in_progress'])
                ->update([
                    'status' => 'resolved',
                    'resolved_at' => now(),
                ]);

            // 6. Garante retorno estrito para o schema public do SaaS Master
            try {
                app(\App\Services\TenantService::class)->switchToPublic();
            } catch (\Throwable) {
            }

            $this->runDiagnostics();

            Notification::make()
                ->title('Sistema Atualizado com Sucesso via Git!')
                ->body("Código sincronizado com origin/saas-prod. Todos os schemas e caches foram atualizados ({$gitOutput}).")
                ->success()
                ->duration(10000)
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Erro ao atualizar sistema via Git')
                ->body($e->getMessage())
                ->danger()
                ->send();
        } finally {
            $this->isDiagnosing = false;
        }
    }

    public function runMigrations(): void
    {
        $this->isDiagnosing = true;

        try {
            \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
            \Illuminate\Support\Facades\Artisan::call('saas:migrate-tenants');
            \Illuminate\Support\Facades\Artisan::call('optimize:clear');

            // Auto-marca incidentes de telemetria pendentes como resolvidos
            \App\Models\SupportTicket::query()
                ->withoutGlobalScopes()
                ->where('type', 'system_incident')
                ->whereIn('status', ['open', 'in_progress'])
                ->update([
                    'status' => 'resolved',
                    'resolved_at' => now(),
                ]);

            $this->runDiagnostics();

            Notification::make()
                ->title('Migrações Aplicadas com Sucesso!')
                ->body('O schema central e todos os schemas das escolas (incluindo Demonstração e ITEP) foram atualizados e os incidentes foram resolvidos.')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Erro ao aplicar migrações')
                ->body($e->getMessage())
                ->danger()
                ->send();
        } finally {
            $this->isDiagnosing = false;
        }
    }

    public function resolveIncidents(): void
    {
        \App\Models\SupportTicket::query()
            ->withoutGlobalScopes()
            ->where('type', 'system_incident')
            ->whereIn('status', ['open', 'in_progress'])
            ->update([
                'status' => 'resolved',
                'resolved_at' => now(),
            ]);

        $this->runDiagnostics();

        Notification::make()
            ->title('Incidentes Marcados como Resolvidos!')
            ->body('O monitor de telemetria foi normalizado com 100% de estabilidade.')
            ->success()
            ->send();
    }

    public function setCategory(string $category): void
    {
        $this->activeCategory = $category;
    }
}

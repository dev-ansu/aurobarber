<?php

declare(strict_types=1);

namespace App\Filament\Saas\Pages;

use App\Services\BarberDoctorService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;

class BarberDoctorPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'BarberDoctor • Saúde do Sistema';

    protected static ?string $title = 'BarberDoctor • Robô Clínico de Saúde & Diagnóstico';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'doctor';

    protected string $view = 'filament.saas.pages.barber-doctor';

    public ?array $report = null;

    public bool $isDiagnosing = false;

    public string $activeCategory = 'all';

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user && in_array($user->role, ['master_admin', 'super_admin', 'admin'], true);
    }

    public function mount(BarberDoctorService $doctor): void
    {
        abort_unless(static::canAccess(), 403);

        $this->report = $doctor->getCachedReport() ?? $doctor->diagnose();
    }

    public function runDiagnostics(BarberDoctorService $doctor): void
    {
        $this->isDiagnosing = true;

        try {
            $this->report = $doctor->diagnose();

            $status = $this->report['status'] ?? 'good';
            $score = $this->report['score'] ?? 0;

            if ($status === 'critical') {
                Notification::make()
                    ->title("Atenção Crítica: Score {$score}%")
                    ->body('Foram detectados itens críticos que exigem intervenção imediata.')
                    ->danger()
                    ->send();
            } else {
                Notification::make()
                    ->title("Check-up Concluído: Score {$score}%")
                    ->body("Diagnóstico executado em {$this->report['execution_time_ms']}ms.")
                    ->success()
                    ->send();
            }
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Erro ao Executar Diagnóstico')
                ->body($e->getMessage())
                ->danger()
                ->send();
        } finally {
            $this->isDiagnosing = false;
        }
    }

    public function clearCache(BarberDoctorService $doctor): void
    {
        try {
            Artisan::call('optimize:clear');

            Notification::make()
                ->title('Cache Otimizado')
                ->body('Caches de configuração, rotas e views foram limpos com sucesso.')
                ->success()
                ->send();

            $this->runDiagnostics($doctor);
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Erro ao Limpar Caches')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function setCategory(string $category): void
    {
        $this->activeCategory = $category;
    }

    public function getFilteredChecks(): array
    {
        if (! $this->report || empty($this->report['checks'])) {
            return [];
        }

        if ($this->activeCategory === 'all') {
            return $this->report['checks'];
        }

        return array_values(array_filter(
            $this->report['checks'],
            fn ($c) => ($c['category'] ?? '') === $this->activeCategory
        ));
    }
}

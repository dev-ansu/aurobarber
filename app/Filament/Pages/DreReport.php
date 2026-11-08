<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Services\DreService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Throwable;
use UnitEnum;

class DreReport extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Relatórios';

    protected static ?string $navigationParentItem = 'Financeiro';

    protected static ?string $navigationLabel = 'DRE Gerencial';

    protected static ?string $title = 'Demonstrativo de Resultado do Exercício (DRE)';

    protected static ?string $slug = 'dre-gerencial';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.dre-report';

    public string $periodStart = '';

    public string $periodEnd = '';

    public string $regime = 'caixa'; // 'caixa' | 'competencia'

    public array $report = [];

    public ?string $generatedAt = null;

    public function mount(): void
    {
        $this->periodStart = Carbon::now()->startOfMonth()->toDateString();
        $this->periodEnd = Carbon::now()->endOfMonth()->toDateString();
        $this->loadReport();
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        return $user->hasRole('super_admin')
            || $user->can('View:DreReport');
    }

    public function loadReport(): void
    {
        try {
            $start = Carbon::parse($this->periodStart);
            $end = Carbon::parse($this->periodEnd);

            if ($end->lt($start)) {
                Notification::make()->title('Data final inválida')->body('A data final deve ser posterior à data inicial.')->warning()->send();

                return;
            }

            $service = app(DreService::class);
            $this->report = $service->generate($start, $end, $this->regime);
            $this->generatedAt = Carbon::now()->format('d/m/Y H:i:s');
        } catch (Throwable $e) {
            report($e);
            Notification::make()->title('Erro ao calcular DRE')->body($e->getMessage())->danger()->send();
        }
    }

    public function setQuickPeriod(string $period): void
    {
        $now = Carbon::now();

        match ($period) {
            'this_month' => [
                $this->periodStart = $now->copy()->startOfMonth()->toDateString(),
                $this->periodEnd = $now->copy()->endOfMonth()->toDateString(),
            ],
            'last_month' => [
                $this->periodStart = $now->copy()->subMonth()->startOfMonth()->toDateString(),
                $this->periodEnd = $now->copy()->subMonth()->endOfMonth()->toDateString(),
            ],
            'this_year' => [
                $this->periodStart = $now->copy()->startOfYear()->toDateString(),
                $this->periodEnd = $now->copy()->endOfYear()->toDateString(),
            ],
            'last_30_days' => [
                $this->periodStart = $now->copy()->subDays(30)->toDateString(),
                $this->periodEnd = $now->toDateString(),
            ],
            'last_3_months' => [
                $this->periodStart = $now->copy()->subMonths(3)->startOfMonth()->toDateString(),
                $this->periodEnd = $now->copy()->endOfMonth()->toDateString(),
            ],
            default => null,
        };

        $this->loadReport();
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\User;
use App\Services\CashFlowForecastService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Throwable;
use UnitEnum;

class CashFlowForecast extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Financeiro';

    protected static ?string $navigationLabel = 'Fluxo de Caixa Projetado';

    protected static ?string $title = 'Fluxo de Caixa Projetado (Projeção Financeira)';

    protected static ?string $slug = 'fluxo-de-caixa-projetado';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.cash-flow-forecast';

    public string $periodStart = '';

    public string $periodEnd = '';

    public array $report = [];

    public ?string $generatedAt = null;

    public function getMaxContentWidth(): ?string
    {
        return 'full';
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        return $user->hasRole('super_admin')
            || $user->can('View:CashFlowForecast');
    }

    public function mount(): void
    {
        $this->periodStart = Carbon::today()->toDateString();
        $this->periodEnd = Carbon::today()->addDays(30)->toDateString();
        $this->loadReport();
    }

    public function loadReport(): void
    {
        try {
            $start = Carbon::parse($this->periodStart);
            $end = Carbon::parse($this->periodEnd);

            if ($end->lt($start)) {
                Notification::make()->title('Data final inválida')->body('A data final deve ser igual ou posterior à data inicial.')->warning()->send();

                return;
            }

            $service = app(CashFlowForecastService::class);
            $this->report = $service->generate($start, $end);
            $this->generatedAt = Carbon::now()->format('d/m/Y H:i:s');
        } catch (Throwable $e) {
            report($e);
            Notification::make()->title('Erro ao projetar fluxo de caixa')->body($e->getMessage())->danger()->send();
        }
    }

    public function setQuickPeriod(string $period): void
    {
        $now = Carbon::today();

        match ($period) {
            'next_15_days' => [
                $this->periodStart = $now->toDateString(),
                $this->periodEnd = $now->copy()->addDays(15)->toDateString(),
            ],
            'next_30_days' => [
                $this->periodStart = $now->toDateString(),
                $this->periodEnd = $now->copy()->addDays(30)->toDateString(),
            ],
            'next_60_days' => [
                $this->periodStart = $now->toDateString(),
                $this->periodEnd = $now->copy()->addDays(60)->toDateString(),
            ],
            'next_90_days' => [
                $this->periodStart = $now->toDateString(),
                $this->periodEnd = $now->copy()->addDays(90)->toDateString(),
            ],
            'this_month' => [
                $this->periodStart = $now->copy()->startOfMonth()->toDateString(),
                $this->periodEnd = $now->copy()->endOfMonth()->toDateString(),
            ],
            'next_month' => [
                $this->periodStart = $now->copy()->addMonth()->startOfMonth()->toDateString(),
                $this->periodEnd = $now->copy()->addMonth()->endOfMonth()->toDateString(),
            ],
            default => null,
        };

        $this->loadReport();
    }
}


<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\User;
use App\Services\CashFlowForecastService;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Throwable;
use UnitEnum;

class CashFlowForecast extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Financeiro';

    protected static ?string $navigationLabel = 'Fluxo de Caixa Projetado';

    protected static ?string $title = 'Fluxo de Caixa Projetado';

    protected static ?string $slug = 'fluxo-de-caixa-projetado';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.cash-flow-forecast';

    public string $periodStart = '';

    public string $periodEnd = '';

    public array $report = [];

    public ?string $generatedAt = null;

    public static function canAccess(): bool
    {
        return auth()->user() instanceof User
            && Filament::getTenant() !== null
            && ! auth()->user()->isRestrictedBarber();
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
                Notification::make()
                    ->title('Período inválido')
                    ->body('A data final deve ser igual ou posterior à inicial.')
                    ->warning()
                    ->send();

                return;
            }

            $this->report = app(CashFlowForecastService::class)->generate(
                $start,
                $end,
                (int) Filament::getTenant()->getKey(),
            );
            $this->generatedAt = Carbon::now()->format('d/m/Y H:i:s');
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('Não foi possível gerar a projeção')
                ->body('Verifique o período selecionado e tente novamente.')
                ->danger()
                ->send();
        }
    }

    public function setQuickPeriod(string $period): void
    {
        $today = Carbon::today();

        match ($period) {
            'next_15_days' => $this->setPeriod($today, $today->copy()->addDays(15)),
            'next_30_days' => $this->setPeriod($today, $today->copy()->addDays(30)),
            'next_60_days' => $this->setPeriod($today, $today->copy()->addDays(60)),
            'next_90_days' => $this->setPeriod($today, $today->copy()->addDays(90)),
            'this_month' => $this->setPeriod($today->copy()->startOfMonth(), $today->copy()->endOfMonth()),
            'next_month' => $this->setPeriod(
                $today->copy()->addMonthNoOverflow()->startOfMonth(),
                $today->copy()->addMonthNoOverflow()->endOfMonth(),
            ),
            default => null,
        };

        $this->loadReport();
    }

    private function setPeriod(Carbon $start, Carbon $end): void
    {
        $this->periodStart = $start->toDateString();
        $this->periodEnd = $end->toDateString();
    }
}

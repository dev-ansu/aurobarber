<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payables\Widgets;

use App\Filament\Resources\Payables\Pages\ManagePayables;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class PayablesStatsOverview extends StatsOverviewWidget
{
    use InteractsWithPageTable;

    protected ?string $pollingInterval = '30s';

    protected function getTablePage(): string
    {
        return ManagePayables::class;
    }

    protected function getStats(): array
    {
        $today = Carbon::today();
        $baseQuery = $this->getPageTableQuery();

        // 1. Total Geral / Previsto
        $totalQuery = clone $baseQuery;
        $totalValor = (float) $totalQuery->sum('amount');
        $totalCount = $totalQuery->count();

        // 2. Vencidas
        $vencidasQuery = (clone $baseQuery)
            ->where('status', 'pending')
            ->whereDate('due_date', '<', $today);
        $totalVencidasValor = (float) $vencidasQuery->sum('amount');
        $totalVencidasCount = $vencidasQuery->count();

        // 3. Vencem Hoje
        $hojeQuery = (clone $baseQuery)
            ->where('status', 'pending')
            ->whereDate('due_date', $today);
        $totalHojeValor = (float) $hojeQuery->sum('amount');
        $totalHojeCount = $hojeQuery->count();

        // 4. A Vencer (Futuras)
        $aVencerQuery = (clone $baseQuery)
            ->where('status', 'pending')
            ->whereDate('due_date', '>', $today);
        $totalAVencerValor = (float) $aVencerQuery->sum('amount');
        $totalAVencerCount = $aVencerQuery->count();

        // 5. Pagas
        $pagasQuery = (clone $baseQuery)
            ->where('status', 'paid');
        $totalPagasValor = (float) ($pagasQuery->sum('amount_paid') ?: $pagasQuery->sum('amount'));
        $totalPagasCount = $pagasQuery->count();

        return [
            Stat::make('Total Previsto', 'R$ '.number_format($totalValor, 2, ',', '.'))
                ->description("{$totalCount} despesa(s)")
                ->descriptionIcon('heroicon-m-calendar')
                ->color('gray'),

            Stat::make('Vencidas', 'R$ '.number_format($totalVencidasValor, 2, ',', '.'))
                ->description("{$totalVencidasCount} conta(s) em atraso")
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($totalVencidasCount > 0 ? 'danger' : 'gray'),

            Stat::make('Vencem Hoje', 'R$ '.number_format($totalHojeValor, 2, ',', '.'))
                ->description("{$totalHojeCount} conta(s) para hoje")
                ->descriptionIcon('heroicon-m-clock')
                ->color($totalHojeCount > 0 ? 'warning' : 'gray'),

            Stat::make('A Vencer', 'R$ '.number_format($totalAVencerValor, 2, ',', '.'))
                ->description("{$totalAVencerCount} conta(s) futuras")
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('info'),

            Stat::make('Pagas', 'R$ '.number_format($totalPagasValor, 2, ',', '.'))
                ->description("{$totalPagasCount} despesa(s) quitada(s)")
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),
        ];
    }
}

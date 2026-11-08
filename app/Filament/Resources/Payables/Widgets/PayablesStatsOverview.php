<?php

namespace App\Filament\Resources\Payables\Widgets;

use App\Filament\Resources\Payables\Pages\ManagePayables;
use App\Models\Payable;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class PayablesStatsOverview extends StatsOverviewWidget
{
    use InteractsWithPageTable;

    protected array|int|null $columns = 5;

    protected function getTablePage(): string
    {
        return ManagePayables::class;
    }

    public function getSectionContentComponent(): \Filament\Schemas\Components\Component
    {
        return parent::getSectionContentComponent()
            ->heading('Resumo Financeiro de Contas a Pagar')
            ->collapsible()
            ->compact();
    }

    protected function getStats(): array
    {
        $today = Carbon::today();
        $baseQuery = $this->getPageTableQuery();

        // 1. Total Geral / Previsto
        $totalQuery = clone $baseQuery;
        $totalValor = $totalQuery->sum('amount');
        $totalCount = $totalQuery->count();

        // 2. Vencidas (Pendentes com data de vencimento anterior a hoje)
        $vencidasQuery = (clone $baseQuery)
            ->where('status', 'pending')
            ->where('due_date', '<', $today);
        $totalVencidasValor = $vencidasQuery->sum('amount');
        $totalVencidasCount = $vencidasQuery->count();

        // 3. Vencem Hoje (Pendentes com vencimento hoje)
        $hojeQuery = (clone $baseQuery)
            ->where('status', 'pending')
            ->whereDate('due_date', $today);
        $totalHojeValor = $hojeQuery->sum('amount');
        $totalHojeCount = $hojeQuery->count();

        // 4. A Vencer (Pendentes com vencimento futuro)
        $aVencerQuery = (clone $baseQuery)
            ->where('status', 'pending')
            ->where('due_date', '>', $today);
        $totalAVencerValor = $aVencerQuery->sum('amount');
        $totalAVencerCount = $aVencerQuery->count();

        // 5. Pagas
        $pagasQuery = (clone $baseQuery)
            ->where('status', 'paid');
        $totalPagasValor = $pagasQuery->sum('amount_paid') ?: $pagasQuery->sum('amount');
        $totalPagasCount = $pagasQuery->count();

        return [
            Stat::make('Total Previsto', 'R$ '.number_format($totalValor, 2, ',', '.'))
                ->description("{$totalCount} despesas registradas")
                ->descriptionIcon('heroicon-m-calendar')
                ->color('gray'),

            Stat::make('Vencidas', 'R$ '.number_format($totalVencidasValor, 2, ',', '.'))
                ->description("{$totalVencidasCount} contas em atraso")
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),

            Stat::make('Vencem Hoje', 'R$ '.number_format($totalHojeValor, 2, ',', '.'))
                ->description("{$totalHojeCount} contas vencendo hoje")
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('A Vencer', 'R$ '.number_format($totalAVencerValor, 2, ',', '.'))
                ->description("{$totalAVencerCount} contas futuras")
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('info'),

            Stat::make('Pagas', 'R$ '.number_format($totalPagasValor, 2, ',', '.'))
                ->description("{$totalPagasCount} despesas quitadas")
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),
        ];
    }
}

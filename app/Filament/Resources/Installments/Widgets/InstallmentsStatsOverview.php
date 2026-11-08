<?php

namespace App\Filament\Resources\Installments\Widgets;

use App\Filament\Resources\Installments\Pages\ListInstallments;
use App\Models\Installment;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class InstallmentsStatsOverview extends StatsOverviewWidget
{
    use InteractsWithPageTable;

    protected array|int|null $columns = 5;

    protected function getTablePage(): string
    {
        return ListInstallments::class;
    }

    protected function getStats(): array
    {
        // Captura os filtros ativos na tabela da página
        $tableFilters = $this->tableFilters ?? [];

        // Extrai o intervalo de datas do filtro 'due_date_range' (padrão: mês atual se não informado)
        $fromDate = data_get($tableFilters, 'due_date_range.from') ?? Carbon::now()->startOfMonth()->format('Y-m-d');
        $untilDate = data_get($tableFilters, 'due_date_range.until') ?? Carbon::now()->endOfMonth()->format('Y-m-d');

        $startPeriod = Carbon::parse($fromDate)->startOfDay();
        $endPeriod = Carbon::parse($untilDate)->endOfDay();
        $today = Carbon::today();

        // Query base que já respeita os filtros aplicados na página (como tipo, meio de pagamento, etc.)
        $baseQuery = $this->getPageTableQuery();

        // 1. Total do Período
        $totalPeriodoQuery = clone $baseQuery;
        $totalPeriodoValor = $totalPeriodoQuery->sum('amount');
        $totalPeriodoCount = $totalPeriodoQuery->count();

        // 2. Vencidas (Pendente ou Parcial com data anterior a hoje)
        $vencidasQuery = (clone $baseQuery)
            ->whereIn('status', ['pending', 'partial'])
            ->where('due_date', '<', $today);
        $totalVencidasValor = $vencidasQuery->sum('amount');
        $totalVencidasCount = $vencidasQuery->count();

        // 3. Vencem Hoje
        $hojeQuery = (clone $baseQuery)
            ->whereIn('status', ['pending', 'partial'])
            ->whereDate('due_date', $today);
        $totalHojeValor = $hojeQuery->sum('amount');
        $totalHojeCount = $hojeQuery->count();

        // 4. A Vencer
        $aVencerQuery = (clone $baseQuery)
            ->whereIn('status', ['pending', 'partial'])
            ->where('due_date', '>', $today);
        $totalAVencerValor = $aVencerQuery->sum('amount');
        $totalAVencerCount = $aVencerQuery->count();

        // 5. Recebidas (Baseado na data de pagamento 'paid_at' dentro do período selecionado)
        $recebidasQuery = Installment::query()
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$startPeriod, $endPeriod]);

        // Reaplica os filtros adicionais da tabela se estiverem ativos
        $financialTypeId = data_get($tableFilters, 'financial_type_id.value');
        $paymentMethodId = data_get($tableFilters, 'payment_method_id.value');

        if ($financialTypeId) {
            $recebidasQuery->where('financial_type_id', $financialTypeId);
        }
        if ($paymentMethodId) {
            $recebidasQuery->where('payment_method_id', $paymentMethodId);
        }

        $totalRecebidasValor = $recebidasQuery->sum('amount');
        $totalRecebidasCount = $recebidasQuery->count();

        return [
            Stat::make('Total do Período', 'R$ '.number_format($totalPeriodoValor, 2, ',', '.'))
                ->description("{$totalPeriodoCount} parcelas previstas")
                ->descriptionIcon('heroicon-m-calendar')
                ->color('gray'),

            Stat::make('Vencidas', 'R$ '.number_format($totalVencidasValor, 2, ',', '.'))
                ->description("{$totalVencidasCount} títulos em atraso")
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),

            Stat::make('Vencem Hoje', 'R$ '.number_format($totalHojeValor, 2, ',', '.'))
                ->description("{$totalHojeCount} títulos hoje")
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('A Vencer', 'R$ '.number_format($totalAVencerValor, 2, ',', '.'))
                ->description("{$totalAVencerCount} títulos futuros")
                ->descriptionIcon('heroicon-m-arrow-right-circle')
                ->color('info'),

            Stat::make('Recebidas', 'R$ '.number_format($totalRecebidasValor, 2, ',', '.'))
                ->description("{$totalRecebidasCount} baixas realizadas")
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
        ];
    }
}

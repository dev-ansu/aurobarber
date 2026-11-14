<?php

namespace App\Filament\Widgets;

use App\Models\Payable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PayablesOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected ?string $pollingInterval = '30s';

    public static function canView(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return ! $user->isRestrictedBarber();
    }

    protected function getStats(): array
    {
        $tenant = filament()->getTenant();
        if (! $tenant) {
            return [];
        }

        $today = now()->format('Y-m-d');
        $in7Days = now()->addDays(7)->format('Y-m-d');
        $startOfMonth = now()->startOfMonth()->format('Y-m-d');
        $endOfMonth = now()->endOfMonth()->format('Y-m-d');

        // 1. Contas Vencidas (Em atraso)
        $overdueQuery = Payable::where('barbershop_id', $tenant->id)
            ->where('status', 'pending')
            ->whereDate('due_date', '<', $today);
        $overdueCount = $overdueQuery->count();
        $overdueTotal = (float) $overdueQuery->sum('amount');

        // 2. A Vencer nos Próximos 7 Dias (Hoje até +7 dias)
        $upcomingQuery = Payable::where('barbershop_id', $tenant->id)
            ->where('status', 'pending')
            ->whereDate('due_date', '>=', $today)
            ->whereDate('due_date', '<=', $in7Days);
        $upcomingCount = $upcomingQuery->count();
        $upcomingTotal = (float) $upcomingQuery->sum('amount');

        // 3. Total a Pagar no Mês Atual (Pendentes)
        $monthPendingQuery = Payable::where('barbershop_id', $tenant->id)
            ->where('status', 'pending')
            ->whereDate('due_date', '>=', $startOfMonth)
            ->whereDate('due_date', '<=', $endOfMonth);
        $monthPendingCount = $monthPendingQuery->count();
        $monthPendingTotal = (float) $monthPendingQuery->sum('amount');

        // 4. Contas Já Pagas no Mês Atual (Gastos Liquidados)
        $monthPaidQuery = Payable::where('barbershop_id', $tenant->id)
            ->where('status', 'paid')
            ->whereDate('payment_date', '>=', $startOfMonth)
            ->whereDate('payment_date', '<=', $endOfMonth);
        $monthPaidCount = $monthPaidQuery->count();
        $monthPaidTotal = (float) $monthPaidQuery->sum('amount');

        return [
            Stat::make('Contas Vencidas', 'R$ ' . number_format($overdueTotal, 2, ',', '.'))
                ->description($overdueCount > 0 ? "{$overdueCount} conta(s) em atraso" : 'Nenhuma conta atrasada')
                ->descriptionIcon($overdueCount > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($overdueCount > 0 ? 'danger' : 'success'),

            Stat::make('Vencendo em 7 Dias', 'R$ ' . number_format($upcomingTotal, 2, ',', '.'))
                ->description("{$upcomingCount} compromisso(s) para esta semana")
                ->descriptionIcon('heroicon-m-clock')
                ->color($upcomingCount > 0 ? 'warning' : 'gray'),

            Stat::make('A Pagar no Mês', 'R$ ' . number_format($monthPendingTotal, 2, ',', '.'))
                ->description("{$monthPendingCount} conta(s) para " . now()->translatedFormat('F'))
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info'),

            Stat::make('Contas Pagas no Mês', 'R$ ' . number_format($monthPaidTotal, 2, ',', '.'))
                ->description("{$monthPaidCount} despesa(s) quitada(s) em " . now()->translatedFormat('F'))
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),
        ];
    }
}

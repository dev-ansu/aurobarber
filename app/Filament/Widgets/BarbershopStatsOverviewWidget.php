<?php

namespace App\Filament\Widgets;

use App\Models\Appointment;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BarbershopStatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $tenant = filament()->getTenant();
        if (!$tenant) {
            return [];
        }

        $today = now()->format('Y-m-d');
        $startOfMonth = now()->startOfMonth()->format('Y-m-d');
        $endOfMonth = now()->endOfMonth()->format('Y-m-d');

        $user = auth()->user();
        if ($user && $user->isRestrictedBarber()) {
            $proId = $user->resolveProfessionalId($tenant->id) ?? 0;

            $todayAppts = Appointment::where('barbershop_id', $tenant->id)
                ->where('professional_id', $proId)
                ->whereDate('scheduled_at', $today)
                ->get();

            $todayCount = $todayAppts->count();
            $todayCompleted = $todayAppts->where('status', 'completed')->count();
            $todayInProgress = $todayAppts->where('status', 'in_progress')->count();
            $todayCommission = (float) $todayAppts->where('status', 'completed')->sum('commission_amount');

            $monthAppts = Appointment::where('barbershop_id', $tenant->id)
                ->where('professional_id', $proId)
                ->whereDate('scheduled_at', '>=', $startOfMonth)
                ->whereDate('scheduled_at', '<=', $endOfMonth)
                ->where('status', 'completed')
                ->get();

            $monthCommission = (float) $monthAppts->sum('commission_amount');
            $pendingCommission = (float) Appointment::where('barbershop_id', $tenant->id)
                ->where('professional_id', $proId)
                ->where('status', 'completed')
                ->where(fn ($q) => $q->whereNull('commission_status')->orWhere('commission_status', 'pending'))
                ->sum('commission_amount');

            return [
                Stat::make('Minha Comissão Hoje', 'R$ ' . number_format($todayCommission, 2, ',', '.'))
                    ->description('Sua comissão acumulada hoje')
                    ->descriptionIcon('heroicon-m-currency-dollar')
                    ->color('success'),

                Stat::make('Meus Atendimentos Hoje', $todayCount . ' cliente(s)')
                    ->description("{$todayCompleted} concluído(s) · {$todayInProgress} na cadeira")
                    ->descriptionIcon('heroicon-m-calendar-days')
                    ->color('primary'),

                Stat::make('Minha Comissão no Mês', 'R$ ' . number_format($monthCommission, 2, ',', '.'))
                    ->description('Mês atual (' . now()->translatedFormat('F') . ')')
                    ->descriptionIcon('heroicon-m-arrow-trending-up')
                    ->color('info'),

                Stat::make('Pendente a Receber', 'R$ ' . number_format($pendingCommission, 2, ',', '.'))
                    ->description('Comissões em aberto para acerto')
                    ->descriptionIcon('heroicon-m-banknotes')
                    ->color('warning'),
            ];
        }

        // Atendimentos de Hoje
        $todayAppts = Appointment::where('barbershop_id', $tenant->id)
            ->whereDate('scheduled_at', $today)
            ->get();

        $todayCount = $todayAppts->count();
        $todayCompleted = $todayAppts->where('status', 'completed')->count();
        $todayInProgress = $todayAppts->where('status', 'in_progress')->count();

        // Faturamento de Hoje
        $todayRevenue = (float) $todayAppts->where('status', 'completed')->sum('service_price');

        // Sparkline dos últimos 7 dias de receita
        $sparkline = [];
        for ($i = 6; $i >= 0; $i--) {
            $dayStr = now()->subDays($i)->format('Y-m-d');
            $dayRev = (float) Appointment::where('barbershop_id', $tenant->id)
                ->whereDate('scheduled_at', $dayStr)
                ->where('status', 'completed')
                ->sum('service_price');
            $sparkline[] = $dayRev;
        }

        // Faturamento do Mês
        $monthAppts = Appointment::where('barbershop_id', $tenant->id)
            ->whereDate('scheduled_at', '>=', $startOfMonth)
            ->whereDate('scheduled_at', '<=', $endOfMonth)
            ->where('status', 'completed')
            ->get();

        $monthRevenue = (float) $monthAppts->sum('service_price');
        $monthCount = $monthAppts->count();
        $avgTicket = $monthCount > 0 ? $monthRevenue / $monthCount : 0.00;

        return [
            Stat::make('Faturamento Hoje', 'R$ ' . number_format($todayRevenue, 2, ',', '.'))
                ->description("Recebido em cortes hoje")
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->chart($sparkline)
                ->color('success'),

            Stat::make('Atendimentos Hoje', $todayCount . ' cliente(s)')
                ->description("{$todayCompleted} concluído(s) · {$todayInProgress} na cadeira")
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary'),

            Stat::make('Faturamento no Mês', 'R$ ' . number_format($monthRevenue, 2, ',', '.'))
                ->description('Mês atual (' . now()->translatedFormat('F') . ')')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('info'),

            Stat::make('Ticket Médio', 'R$ ' . number_format($avgTicket, 2, ',', '.'))
                ->description('Média por cliente concluído')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning'),
        ];
    }
}

<?php

namespace App\Filament\Widgets;

use App\Models\CommercialLead;
use App\Models\CommercialVisit;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class CommercialOverview extends BaseWidget
{
    protected static bool $isDiscovered = false;

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected int|array|null $columns = [
        '@xl' => 4,
        '!@xl' => 2,
    ];

    public static function canView(): bool
    {
        $user = filament()->auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        return $user->hasRole('super_admin')
            || $user->can('View:CommercialOverview')
            || $user->can('ViewAny:CommercialLead');
    }

    protected function getStats(): array
    {
        $user = filament()->auth()->user();
        $canViewLeads = $user?->can('ViewAny:CommercialLead') ?? false;
        $canViewVisits = $user?->can('ViewAny:CommercialVisit') ?? false;
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();

        $openLeads = CommercialLead::query()
            ->whereNotIn('status', ['lost', 'enrolled'])
            ->count();

        $overdueFollowUps = CommercialLead::query()
            ->whereNotNull('next_follow_up_at')
            ->where('next_follow_up_at', '<', now())
            ->whereNotIn('status', ['lost', 'enrolled'])
            ->count();

        $visitsThisMonth = CommercialVisit::query()
            ->whereBetween('visited_at', [$monthStart, $monthEnd])
            ->count();

        $leadsThisMonth = CommercialLead::query()
            ->whereBetween('created_at', [$monthStart, $monthEnd]);
        $leadCount = (clone $leadsThisMonth)->count();
        $enrolledCount = (clone $leadsThisMonth)
            ->where('status', 'enrolled')
            ->whereNotNull('converted_at')
            ->count();
        $conversionRate = $leadCount > 0 ? round(($enrolledCount / $leadCount) * 100) : 0;

        return [
            Stat::make('Interessados em andamento', number_format($openLeads, 0, ',', '.'))
                ->description('Oportunidades ainda abertas')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary')
                ->url($canViewLeads ? route('filament.admin.resources.commercial-leads.index') : null),
            Stat::make('Retornos atrasados', number_format($overdueFollowUps, 0, ',', '.'))
                ->description('Interessados aguardando contato')
                ->descriptionIcon('heroicon-m-clock')
                ->color($overdueFollowUps > 0 ? 'danger' : 'success')
                ->url($canViewLeads ? route('filament.admin.resources.commercial-leads.index') : null),
            Stat::make('Visitas no mês', number_format($visitsThisMonth, 0, ',', '.'))
                ->description('Registros de visita à escola')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('info')
                ->url($canViewVisits ? route('filament.admin.resources.commercial-visits.index') : null),
            Stat::make('Conversão dos interessados do mês', "{$conversionRate}%")
                ->description("{$enrolledCount} matrícula(s) de {$leadCount} interessado(s) cadastrado(s)")
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('success')
                ->url($canViewLeads ? route('filament.admin.resources.commercial-leads.index') : null),
        ];
    }
}

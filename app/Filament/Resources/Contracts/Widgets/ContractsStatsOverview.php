<?php

namespace App\Filament\Resources\Contracts\Widgets;

use App\Filament\Resources\Contracts\Pages\ListContracts;
use App\Models\Contract;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ContractsStatsOverview extends StatsOverviewWidget
{
    use InteractsWithPageTable;

    protected array|int|null $columns = 4;

    public static function canView(): bool
    {
        return auth()->user()?->can('ViewAny:Contract') ?? false;
    }

    protected function getStats(): array
    {
        $canSeeFinancial = auth()->user()?->can('ViewAny:Installment')
            || (auth()->user() instanceof \App\Models\User && auth()->user()->hasRole('super_admin'));

        // 1. Total Geral
        $totalQuery = Contract::query();
        $totalCount = (clone $totalQuery)->count();
        $totalValue = $canSeeFinancial ? (clone $totalQuery)->sum('total_value') : null;

        // 2. Contratos Ativos
        $activeQuery = Contract::query()->where('status', 'active');
        $activeCount = (clone $activeQuery)->count();
        $activeValue = $canSeeFinancial ? (clone $activeQuery)->sum('total_value') : null;

        // 3. Aguardando Conclusão (Ativos com 100% das formações concluídas no LMS)
        $pendingCompletionQuery = Contract::query()
            ->where('status', 'active')
            ->whereHas('items')
            ->whereDoesntHave('items', fn ($q) => $q->whereNull('formation_completed_at'));
        $pendingCount = (clone $pendingCompletionQuery)->count();

        // 4. Finalizados pela Secretaria
        $finishedQuery = Contract::query()->where('status', 'finished');
        $finishedCount = (clone $finishedQuery)->count();
        $finishedValue = $canSeeFinancial ? (clone $finishedQuery)->sum('total_value') : null;

        return [
            Stat::make('Total de Contratos', number_format($totalCount, 0, ',', '.'))
                ->description($canSeeFinancial && $totalValue !== null
                    ? 'R$ ' . number_format($totalValue, 2, ',', '.') . ' contratados'
                    : 'Total de matrículas na base')
                ->descriptionIcon('heroicon-m-document-duplicate')
                ->color('gray'),

            Stat::make('Contratos Ativos', number_format($activeCount, 0, ',', '.'))
                ->description($canSeeFinancial && $activeValue !== null
                    ? 'R$ ' . number_format($activeValue, 2, ',', '.') . ' em curso'
                    : 'Alunos cursando atualmente')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Aguardando Conclusão', number_format($pendingCount, 0, ',', '.'))
                ->description('Formação 100% concluída no LMS')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color($pendingCount > 0 ? 'warning' : 'gray'),

            Stat::make('Finalizados', number_format($finishedCount, 0, ',', '.'))
                ->description($canSeeFinancial && $finishedValue !== null
                    ? 'R$ ' . number_format($finishedValue, 2, ',', '.') . ' concluídos'
                    : 'Baixas concluídas com certificado')
                ->descriptionIcon('heroicon-m-flag')
                ->color('info'),
        ];
    }
}


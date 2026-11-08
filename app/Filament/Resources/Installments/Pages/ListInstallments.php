<?php

namespace App\Filament\Resources\Installments\Pages;

use App\Filament\Resources\Installments\InstallmentsResource;
use App\Filament\Resources\Installments\Widgets\InstallmentsStatsOverview;
use Carbon\Carbon;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListInstallments extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = InstallmentsResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            InstallmentsStatsOverview::class,
        ];
    }

    // O método updatedTableFilters foi removido porque o Filament faz o vínculo nativo agora

    protected function getHeaderActions(): array
    {
        return [
            // CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        // Pega o estado atual do filtro de período de vencimento ('due_date_range') da tabela
        $filterState = $this->getTableFilterState('due_date_range') ?? [];
        $fromDate = array_key_exists('from', $filterState) ? $filterState['from'] : Carbon::now()->startOfMonth()->format('Y-m-d');
        $untilDate = array_key_exists('until', $filterState) ? $filterState['until'] : Carbon::now()->endOfMonth()->format('Y-m-d');

        // Função auxiliar para aplicar o filtro de data padrão da tabela nas queries das abas
        $applyDateFilter = function (Builder $query) use ($fromDate, $untilDate) {
            return $query
                ->when($fromDate, fn ($q, $date) => $q->whereDate('due_date', '>=', $date))
                ->when($untilDate, fn ($q, $date) => $q->whereDate('due_date', '<=', $date));
        };

        return [
            'all' => Tab::make('Todas')
                ->icon('heroicon-m-squares-2x2')
                ->modifyQueryUsing(fn (Builder $query) => $applyDateFilter($query))
                ->badge(fn () => $applyDateFilter($this->getModel()::query())->count()),

            'today' => Tab::make('Hoje')
                ->icon('heroicon-m-calendar')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->whereDate('due_date', Carbon::today())
                    ->where('status', 'pending')
                )
                ->badge(fn () => $this->getModel()::query()
                    ->whereDate('due_date', Carbon::today())
                    ->where('status', 'pending')
                    ->count()
                )
                ->badgeColor('warning'),

            'pending' => Tab::make('Pendentes')
                ->icon('heroicon-m-clock')
                ->modifyQueryUsing(fn (Builder $query) => $applyDateFilter($query)->where('status', 'pending'))
                ->badge(fn () => $applyDateFilter($this->getModel()::query())->where('status', 'pending')->count()),

            /* 'partial' => Tab::make('Parciais')
                ->icon('heroicon-m-adjustments-horizontal')
                ->modifyQueryUsing(fn (Builder $query) => $applyDateFilter($query)->where('status', 'partial'))
                ->badge(fn () => $applyDateFilter($this->getModel()::query())->where('status', 'partial')->count())
                ->badgeColor('warning'), */

            'overdue' => Tab::make('Atrasadas')
                ->icon('heroicon-m-exclamation-triangle')
                ->modifyQueryUsing(fn (Builder $query) => $applyDateFilter($query)
                    ->where('due_date', '<', Carbon::today())
                    ->whereIn('status', ['pending', 'partial'])
                )
                ->badge(fn () => $applyDateFilter($this->getModel()::query())
                    ->where('due_date', '<', Carbon::today())
                    ->whereIn('status', ['pending', 'partial'])
                    ->count()
                )
                ->badgeColor('danger'),

            'paid' => Tab::make('Pagas')
                ->icon('heroicon-m-check-circle')
                ->modifyQueryUsing(fn (Builder $query) => $applyDateFilter($query)->where('status', 'paid'))
                ->badge(fn () => $applyDateFilter($this->getModel()::query())->where('status', 'paid')->count())
                ->badgeColor('success'),
        ];
    }
}

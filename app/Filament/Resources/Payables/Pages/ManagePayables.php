<?php

namespace App\Filament\Resources\Payables\Pages;

use App\Filament\Resources\Payables\PayableResource;
use App\Filament\Resources\Payables\Widgets\PayablesStatsOverview;
use App\Models\Payable;
use Carbon\Carbon;
use Filament\Actions\CreateAction;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ManagePayables extends ManageRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = PayableResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            PayablesStatsOverview::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nova conta a pagar')
                ->after(function (Payable $record): void {
                    if ($record->is_recurring && ($record->recurrence_limit ?? 0) > 1) {
                        $record->generateRecurringOccurrences($record->recurrence_limit, $record->recurrence_period);
                    }
                }),
        ];
    }

    public function getTabs(): array
    {
        $filterState = $this->getTableFilterState('due_date_range') ?? [];
        $fromDate = array_key_exists('from', $filterState) ? $filterState['from'] : Carbon::now()->startOfMonth()->format('Y-m-d');
        $untilDate = array_key_exists('until', $filterState) ? $filterState['until'] : Carbon::now()->endOfMonth()->format('Y-m-d');

        $applyDateFilter = function (Builder $query) use ($fromDate, $untilDate) {
            return $query
                ->when($fromDate, fn ($q, $date) => $q->whereDate('due_date', '>=', $date))
                ->when($untilDate, fn ($q, $date) => $q->whereDate('due_date', '<=', $date));
        };

        $today = Carbon::today();

        return [
            'all' => Tab::make('Todas')
                ->icon('heroicon-m-squares-2x2')
                ->modifyQueryUsing(fn (Builder $query) => $applyDateFilter($query))
                ->badge(fn () => $applyDateFilter(Payable::query())->count()),

            'today' => Tab::make('Hoje')
                ->icon('heroicon-m-clock')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('status', 'pending')
                    ->whereDate('due_date', $today)
                )
                ->badge(fn () => Payable::query()
                    ->where('status', 'pending')
                    ->whereDate('due_date', $today)
                    ->count()
                )
                ->badgeColor('warning'),

            'overdue' => Tab::make('Vencidas')
                ->icon('heroicon-m-exclamation-triangle')
                ->modifyQueryUsing(fn (Builder $query) => $applyDateFilter($query)
                    ->where('status', 'pending')
                    ->where('due_date', '<', $today)
                )
                ->badge(fn () => $applyDateFilter(Payable::query())
                    ->where('status', 'pending')
                    ->where('due_date', '<', $today)
                    ->count()
                )
                ->badgeColor('danger'),

            'upcoming' => Tab::make('A Vencer')
                ->icon('heroicon-m-arrow-trending-up')
                ->modifyQueryUsing(fn (Builder $query) => $applyDateFilter($query)
                    ->where('status', 'pending')
                    ->where('due_date', '>', $today)
                )
                ->badge(fn () => $applyDateFilter(Payable::query())
                    ->where('status', 'pending')
                    ->where('due_date', '>', $today)
                    ->count()
                )
                ->badgeColor('info'),

            'paid' => Tab::make('Pagas')
                ->icon('heroicon-m-check-circle')
                ->modifyQueryUsing(fn (Builder $query) => $applyDateFilter($query)->where('status', 'paid'))
                ->badge(fn () => $applyDateFilter(Payable::query())->where('status', 'paid')->count())
                ->badgeColor('success'),
        ];
    }
}

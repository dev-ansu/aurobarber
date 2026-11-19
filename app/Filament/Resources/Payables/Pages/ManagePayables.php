<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payables\Pages;

use App\Filament\Resources\Payables\PayableResource;
use App\Filament\Resources\Payables\Widgets\PayablesStatsOverview;
use App\Models\Payable;
use Carbon\Carbon;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
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
                ->label('Nova Conta a Pagar')
                ->mutateFormDataUsing(function (array $data): array {
                    $data['barbershop_id'] = Filament::getTenant()?->getKey();

                    return $data;
                })
                ->after(function (Payable $record): void {
                    if ($record->is_recurring && ($record->recurrence_limit ?? 0) > 1) {
                        $record->generateRecurringOccurrences($record->recurrence_limit, $record->recurrence_period);
                    }
                }),
        ];
    }

    public function getTabs(): array
    {
        $tenantId = Filament::getTenant()?->getKey();
        $today = Carbon::today();

        $baseTenantQuery = fn () => Payable::query()->where('barbershop_id', $tenantId ?? 0);

        return [
            'all' => Tab::make('Todas')
                ->icon('heroicon-m-squares-2x2')
                ->badge(fn (): int => $baseTenantQuery()->count()),

            'today' => Tab::make('Hoje')
                ->icon('heroicon-m-clock')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->where('status', 'pending')
                    ->whereDate('due_date', $today)
                )
                ->badge(fn (): int => $baseTenantQuery()
                    ->where('status', 'pending')
                    ->whereDate('due_date', $today)
                    ->count()
                )
                ->badgeColor('warning'),

            'overdue' => Tab::make('Vencidas')
                ->icon('heroicon-m-exclamation-triangle')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->where('status', 'pending')
                    ->whereDate('due_date', '<', $today)
                )
                ->badge(fn (): int => $baseTenantQuery()
                    ->where('status', 'pending')
                    ->whereDate('due_date', '<', $today)
                    ->count()
                )
                ->badgeColor('danger'),

            'upcoming' => Tab::make('A Vencer')
                ->icon('heroicon-m-arrow-trending-up')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->where('status', 'pending')
                    ->whereDate('due_date', '>', $today)
                )
                ->badge(fn (): int => $baseTenantQuery()
                    ->where('status', 'pending')
                    ->whereDate('due_date', '>', $today)
                    ->count()
                )
                ->badgeColor('info'),

            'paid' => Tab::make('Pagas')
                ->icon('heroicon-m-check-circle')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'paid'))
                ->badge(fn (): int => $baseTenantQuery()->where('status', 'paid')->count())
                ->badgeColor('success'),
        ];
    }
}

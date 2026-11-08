<?php

namespace App\Filament\Widgets;

use App\Models\Contract;
use App\Models\User;
use Filament\Widgets\ChartWidget;

class ContractsByStatusChart extends ChartWidget
{
    protected static bool $isDiscovered = false;

    protected static ?int $sort = 4;

    protected ?string $heading = 'Contratos por situação';

    protected ?string $description = 'Distribuição dos contratos cadastrados.';

    public static function canView(): bool
    {
        $user = filament()->auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        return $user->hasRole('super_admin')
            || $user->can('View:ContractsByStatusChart')
            || $user->can('ViewAny:Contract');
    }

    protected function getData(): array
    {
        $counts = Contract::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $labels = [
            'draft' => 'Rascunho',
            'active' => 'Ativo',
            'suspended' => 'Suspenso',
            'finished' => 'Concluído',
            'canceled' => 'Cancelado',
        ];
        $statuses = $counts->keys()->all();

        return [
            'labels' => collect($statuses)->map(fn (string $status): string => $labels[$status] ?? $status)->all(),
            'datasets' => [[
                'label' => 'Contratos',
                'data' => collect($statuses)->map(fn (string $status): int => (int) $counts[$status])->all(),
                'backgroundColor' => ['#94a3b8', '#16a34a', '#f59e0b', '#2563eb', '#dc2626'],
            ]],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}

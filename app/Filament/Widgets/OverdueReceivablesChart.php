<?php

namespace App\Filament\Widgets;

use App\Models\Installment;
use App\Models\User;
use Filament\Widgets\ChartWidget;

class OverdueReceivablesChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'Parcelas vencidas por faixa';

    protected ?string $description = 'Saldo em aberto agrupado pelo tempo de atraso.';

    protected ?string $maxHeight = '320px';

    public static function canView(): bool
    {
        $user = filament()->auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        return $user->hasRole('super_admin')
            || $user->can('View:OverdueReceivablesChart')
            || $user->can('View:Stat:Inadimplencia');
    }

    protected function getData(): array
    {
        $today = today();
        $bands = [
            '1–7 dias' => [$today->copy()->subDays(7), $today->copy()->subDay()],
            '8–30 dias' => [$today->copy()->subDays(30), $today->copy()->subDays(8)],
            '31–60 dias' => [$today->copy()->subDays(60), $today->copy()->subDays(31)],
            '61–90 dias' => [$today->copy()->subDays(90), $today->copy()->subDays(61)],
        ];

        $labels = array_keys($bands);
        $amounts = [];

        foreach ($bands as [$start, $end]) {
            $amounts[] = $this->outstandingQuery()
                ->whereBetween('due_date', [$start->toDateString(), $end->toDateString()])
                ->selectRaw('COALESCE(SUM(amount - discount + fine - COALESCE(amount_paid, 0)), 0) as total')
                ->value('total');
        }

        $amounts[] = $this->outstandingQuery()
            ->whereDate('due_date', '<', $today->copy()->subDays(90)->toDateString())
            ->selectRaw('COALESCE(SUM(amount - discount + fine - COALESCE(amount_paid, 0)), 0) as total')
            ->value('total');
        $labels[] = 'Mais de 90 dias';

        return [
            'labels' => $labels,
            'datasets' => [[
                'label' => 'Saldo vencido (R$)',
                'data' => array_map('floatval', $amounts),
                'backgroundColor' => ['#fbbf24', '#fb923c', '#f97316', '#ef4444', '#b91c1c'],
            ]],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    private function outstandingQuery()
    {
        return Installment::query()
            ->forSchool()
            ->whereIn('status', ['pending', 'partial'])
            ->whereDate('due_date', '<', today())
            ->whereRaw('(amount - discount + fine - COALESCE(amount_paid, 0)) > 0');
    }
}

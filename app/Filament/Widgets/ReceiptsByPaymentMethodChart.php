<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Services\PaymentMethodReceiptReport;
use Filament\Widgets\ChartWidget;

class ReceiptsByPaymentMethodChart extends ChartWidget
{
    protected static bool $isDiscovered = false;

    protected static ?int $sort = 3;

    protected ?string $heading = 'Recebimentos por forma';

    protected ?string $description = 'Valores recebidos no período, considerando as formas cadastradas pela escola.';

    public ?string $filter = 'month';

    public static function canView(): bool
    {
        $user = filament()->auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        return $user->hasRole('super_admin')
            || $user->can('View:ReceiptsByPaymentMethodChart')
            || $user->can('View:Stat:RecebimentosMes')
            || $user->can('ViewAny:Installment');
    }

    protected function getFilters(): ?array
    {
        return ['month' => 'Este mês', 'last_month' => 'Mês passado', 'year' => 'Este ano'];
    }

    protected function getData(): array
    {
        [$start, $end] = match ($this->filter) {
            'last_month' => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()],
            'year' => [now()->startOfYear(), now()->endOfDay()],
            default => [now()->startOfMonth(), now()->endOfDay()],
        };

        $rows = app(PaymentMethodReceiptReport::class)->query($start, $end)
            ->orderByDesc('amount_total')
            ->get();

        return [
            'labels' => $rows->pluck('name')->all(),
            'datasets' => [[
                'label' => 'Valor recebido (R$)',
                'data' => $rows->map(fn ($row): float => (float) $row->amount_total)->all(),
                'backgroundColor' => ['#16a34a', '#2563eb', '#f59e0b', '#9333ea', '#0891b2', '#db2777', '#64748b'],
            ]],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}

<?php

namespace App\Filament\Widgets;

use App\Models\Appointment;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class RevenueTrendChartWidget extends ChartWidget
{
    protected ?string $heading = 'Faturamento dos Últimos 7 Dias (R$)';

    protected static ?int $sort = 2;

    protected ?string $maxHeight = '280px';

    protected ?string $pollingInterval = '60s';

    protected function getData(): array
    {
        $tenant = filament()->getTenant();
        if (!$tenant) {
            return ['datasets' => [], 'labels' => []];
        }

        $labels = [];
        $values = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dateStr = $date->format('Y-m-d');
            $label = $i === 0 ? 'Hoje' : $date->translatedFormat('D, d/m');

            $revenue = (float) Appointment::where('barbershop_id', $tenant->id)
                ->whereDate('scheduled_at', $dateStr)
                ->where('status', 'completed')
                ->sum('service_price');

            $labels[] = $label;
            $values[] = $revenue;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Faturamento (R$)',
                    'data' => $values,
                    'backgroundColor' => 'rgba(217, 119, 6, 0.25)',
                    'borderColor' => '#d97706',
                    'borderWidth' => 2,
                    'borderRadius' => 8,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}

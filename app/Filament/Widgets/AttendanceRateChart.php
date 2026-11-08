<?php

namespace App\Filament\Widgets;

use App\Models\StudentAttendance;
use App\Models\User;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class AttendanceRateChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'Presenças e faltas';

    protected ?string $description = 'Percentual de presenças e faltas nas aulas já realizadas.';

    protected ?string $maxHeight = '320px';

    public ?string $filter = '7';

    public static function canView(): bool
    {
        $user = filament()->auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        return $user->hasRole('super_admin')
            || $user->can('View:AttendanceRateChart')
            || $user->can('View:StudentAttendanceReport');
    }

    /**
     * @return array<string, string>
     */
    protected function getFilters(): ?array
    {
        return [
            '7' => 'Últimos 7 dias',
            '30' => 'Últimos 30 dias',
            '90' => 'Últimos 90 dias',
        ];
    }

    /**
     * @return array{
     *     datasets: array<int, array{label: string, data: array<int, float>, backgroundColor: string}>,
     *     labels: array<int, string>
     * }
     */
    protected function getData(): array
    {
        $days = match ($this->filter) {
            '30' => 30,
            '90' => 90,
            default => 7,
        };
        $startDate = Carbon::today()->subDays($days - 1);
        $endDate = Carbon::today();

        $dailyTotals = StudentAttendance::query()
            ->selectRaw('attendance_date, status, COUNT(*) as total')
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->whereIn('status', ['present', 'absent'])
            ->whereNotNull('marked_at')
            ->groupBy('attendance_date', 'status')
            ->get()
            ->groupBy(fn (StudentAttendance $attendance): string => Carbon::parse($attendance->attendance_date)->toDateString());

        $labels = [];
        $presentPercentages = [];
        $absentPercentages = [];

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dateKey = $date->toDateString();
            $dayRecords = $dailyTotals->get($dateKey, collect());
            $presentCount = (int) $dayRecords->firstWhere('status', 'present')?->total;
            $absentCount = (int) $dayRecords->firstWhere('status', 'absent')?->total;
            $totalCount = $presentCount + $absentCount;

            $labels[] = $date->format('d/m');
            $presentPercentages[] = $totalCount > 0 ? round(($presentCount / $totalCount) * 100, 1) : 0;
            $absentPercentages[] = $totalCount > 0 ? round(($absentCount / $totalCount) * 100, 1) : 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Presenças (%)',
                    'data' => $presentPercentages,
                    'backgroundColor' => '#16a34a',
                ],
                [
                    'label' => 'Faltas (%)',
                    'data' => $absentPercentages,
                    'backgroundColor' => '#dc2626',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'x' => [
                    'stacked' => true,
                ],
                'y' => [
                    'stacked' => true,
                    'min' => 0,
                    'max' => 100,
                    'ticks' => [
                        'stepSize' => 20,
                    ],
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}

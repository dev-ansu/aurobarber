<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

#[Table('school_goals')]
#[Fillable([
    'school_id',
    'month',
    'year',
    'target_enrollments',
    'target_sales_amount',
    'target_revenue_amount',
    'target_attendance_rate',
    'target_max_cancellations',
    'target_retention_interviews',
    'notes',
])]
class SchoolGoal extends Model
{
    use BelongsToSchool;

    protected function casts(): array
    {
        return [
            'month' => 'integer',
            'year' => 'integer',
            'target_enrollments' => 'integer',
            'target_sales_amount' => 'decimal:2',
            'target_revenue_amount' => 'decimal:2',
            'target_attendance_rate' => 'decimal:2',
            'target_max_cancellations' => 'integer',
            'target_retention_interviews' => 'integer',
        ];
    }

    public static function forCurrentMonth(): ?self
    {
        $now = Carbon::now();

        return static::query()
            ->where('month', $now->month)
            ->where('year', $now->year)
            ->first();
    }

    public static function forMonthAndYear(int $month, int $year): ?self
    {
        return static::query()
            ->where('month', $month)
            ->where('year', $year)
            ->first();
    }

    public function getMonthNameAttribute(): string
    {
        $months = [
            1 => 'Janeiro',
            2 => 'Fevereiro',
            3 => 'Março',
            4 => 'Abril',
            5 => 'Maio',
            6 => 'Junho',
            7 => 'Julho',
            8 => 'Agosto',
            9 => 'Setembro',
            10 => 'Outubro',
            11 => 'Novembro',
            12 => 'Dezembro',
        ];

        return $months[$this->month] ?? (string) $this->month;
    }

    public function getFormattedPeriodAttribute(): string
    {
        return "{$this->month_name} / {$this->year}";
    }
}

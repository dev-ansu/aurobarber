<?php

namespace App\Filament\Widgets;

use App\Models\CommercialLead;
use App\Models\CommercialVisit;
use App\Models\Contract;
use App\Models\Installment;
use App\Models\SchoolAppointment;
use App\Models\SchoolGoal;
use App\Models\StudentAttendance;
use App\Models\User;
use Filament\Widgets\Widget as BaseWidget;
use Illuminate\Support\Carbon;

class SchoolGoalsProgressWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.school-goals-progress-widget';

    public static function canView(): bool
    {
        $user = filament()->auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        return $user->hasRole('super_admin')
            || $user->can('View:SchoolGoalsWidget')
            || $user->can('ViewAny:SchoolGoal');
    }

    public function getViewData(): array
    {
        $now = Carbon::now();
        $month = (int) $now->month;
        $year = (int) $now->year;

        $goal = SchoolGoal::forMonthAndYear($month, $year);

        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        // 1. Matrículas realizadas no mês
        $actualEnrollments = Contract::query()
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->whereNotIn('status', ['canceled', 'draft'])
            ->count();

        // 2. Faturamento bruto em novas vendas no mês
        $actualSales = (float) Contract::query()
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->whereNotIn('status', ['canceled', 'draft'])
            ->sum('total_value');

        // 3. Arrecadação real recebida de parcelas no mês
        $actualRevenue = (float) Installment::query()
            ->forSchool()
            ->whereBetween('paid_at', [$startOfMonth, $endOfMonth])
            ->whereIn('status', ['paid', 'partial'])
            ->sum('amount_paid');

        // 4. Interessados (Leads) e Visitas comerciais no mês
        $leadsCount = CommercialLead::query()
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->count();

        $visitsCount = CommercialVisit::query()
            ->whereBetween('visited_at', [$startOfMonth, $endOfMonth])
            ->count();

        $conversionRate = $leadsCount > 0
            ? round(($actualEnrollments / $leadsCount) * 100, 1)
            : 0.0;

        // 5. Métricas Pedagógicas e Auditoria de Frequência
        $totalAttendances = StudentAttendance::query()
            ->whereBetween('attendance_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->count();

        $presentAttendances = StudentAttendance::query()
            ->whereBetween('attendance_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->where('status', 'present')
            ->count();

        $kioskPresences = StudentAttendance::query()
            ->whereBetween('attendance_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->where('status', 'present')
            ->where('marking_source', 'kiosk')
            ->count();

        $manualPresences = StudentAttendance::query()
            ->whereBetween('attendance_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->where('status', 'present')
            ->where(function ($q): void {
                $q->whereNull('marking_source')
                    ->orWhere('marking_source', '!=', 'kiosk');
            })
            ->count();

        $actualAttendanceRate = $totalAttendances > 0
            ? round(($presentAttendances / $totalAttendances) * 100, 1)
            : 0.0;

        $kioskAuditRate = $presentAttendances > 0
            ? round(($kioskPresences / $presentAttendances) * 100, 1)
            : 0.0;

        // 6. Evasão / Cancelamentos no mês
        $actualCancellations = Contract::query()
            ->whereBetween('updated_at', [$startOfMonth, $endOfMonth])
            ->where('status', 'canceled')
            ->count();

        // 7. Acompanhamentos e Entrevistas de Retenção Concluídas
        $actualRetentionInterviews = SchoolAppointment::query()
            ->whereBetween('date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->whereIn('type', ['cancelamento', 'reuniao_pais'])
            ->where('status', 'completed')
            ->count();

        // Metas alvo
        $targetEnrollments = $goal?->target_enrollments ?? 0;
        $targetSales = (float) ($goal?->target_sales_amount ?? 0.0);
        $targetRevenue = (float) ($goal?->target_revenue_amount ?? 0.0);
        $targetAttendanceRate = (float) ($goal?->target_attendance_rate ?? 85.0);
        $targetMaxCancellations = (int) ($goal?->target_max_cancellations ?? 3);
        $targetRetentionInterviews = (int) ($goal?->target_retention_interviews ?? 10);

        // Percentuais
        $enrollmentsPercent = $targetEnrollments > 0
            ? round(($actualEnrollments / $targetEnrollments) * 100, 1)
            : 0.0;

        $salesPercent = $targetSales > 0
            ? round(($actualSales / $targetSales) * 100, 1)
            : 0.0;

        $revenuePercent = $targetRevenue > 0
            ? round(($actualRevenue / $targetRevenue) * 100, 1)
            : 0.0;

        $retentionInterviewsPercent = $targetRetentionInterviews > 0
            ? round(($actualRetentionInterviews / $targetRetentionInterviews) * 100, 1)
            : 0.0;

        // Ritmo do mês
        $totalDays = $now->daysInMonth;
        $day = $now->day;
        $daysRemaining = max(0, $totalDays - $day);
        $monthElapsedPercent = round(($day / $totalDays) * 100, 1);

        $dailyEnrollmentsPace = $day > 0 ? ($actualEnrollments / $day) : 0;
        $projectedEnrollments = (int) round($actualEnrollments + ($dailyEnrollmentsPace * $daysRemaining));

        return [
            'hasGoal' => $goal !== null,
            'goal' => $goal,
            'monthName' => $goal?->month_name ?? $this->monthNameFromInt($month),
            'year' => $year,
            // Comercial & Financeiro
            'actualEnrollments' => $actualEnrollments,
            'targetEnrollments' => $targetEnrollments,
            'enrollmentsPercent' => $enrollmentsPercent,
            'remainingEnrollments' => max(0, $targetEnrollments - $actualEnrollments),
            'actualSales' => $actualSales,
            'targetSales' => $targetSales,
            'salesPercent' => $salesPercent,
            'remainingSales' => max(0.0, $targetSales - $actualSales),
            'actualRevenue' => $actualRevenue,
            'targetRevenue' => $targetRevenue,
            'revenuePercent' => $revenuePercent,
            'remainingRevenue' => max(0.0, $targetRevenue - $actualRevenue),
            'leadsCount' => $leadsCount,
            'visitsCount' => $visitsCount,
            'conversionRate' => $conversionRate,
            // Pedagógico & Retenção
            'totalAttendances' => $totalAttendances,
            'presentAttendances' => $presentAttendances,
            'actualAttendanceRate' => $actualAttendanceRate,
            'targetAttendanceRate' => $targetAttendanceRate,
            'attendanceGoalReached' => $actualAttendanceRate >= $targetAttendanceRate,
            'kioskPresences' => $kioskPresences,
            'manualPresences' => $manualPresences,
            'kioskAuditRate' => $kioskAuditRate,
            'actualCancellations' => $actualCancellations,
            'targetMaxCancellations' => $targetMaxCancellations,
            'cancellationsSafe' => $actualCancellations <= $targetMaxCancellations,
            'actualRetentionInterviews' => $actualRetentionInterviews,
            'targetRetentionInterviews' => $targetRetentionInterviews,
            'retentionInterviewsPercent' => $retentionInterviewsPercent,
            // Ritmo
            'totalDays' => $totalDays,
            'currentDay' => $day,
            'daysRemaining' => $daysRemaining,
            'monthElapsedPercent' => $monthElapsedPercent,
            'projectedEnrollments' => $projectedEnrollments,
        ];
    }

    private function monthNameFromInt(int $month): string
    {
        $months = [
            1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
            5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
            9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
        ];

        return $months[$month] ?? (string) $month;
    }
}

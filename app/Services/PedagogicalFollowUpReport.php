<?php

namespace App\Services;

use App\Models\StudentAttendance;
use Illuminate\Support\Carbon;

class PedagogicalFollowUpReport
{
    private const MINIMUM_SESSIONS_FOR_RISK = 3;

    public function generate(
        Carbon $from,
        Carbon $until,
        ?int $classroomId = null,
        ?int $formationId = null,
        ?int $weekday = null,
        ?string $startHour = null,
    ): array {
        $attendances = StudentAttendance::query()
            ->with([
                'studentSchedule.contractItem.contract.student',
                'studentSchedule.contractItem.formation',
                'session.classroom',
            ])
            ->whereBetween('attendance_date', [$from->toDateString(), $until->toDateString()])
            ->whereHas('studentSchedule.contractItem', fn ($items) => $items
                ->whereNull('deleted_at')
                ->whereNull('formation_completed_at')
                ->when($formationId, fn ($query) => $query->where('formation_id', $formationId))
                ->whereHas('contract', fn ($contracts) => $contracts->where('status', 'active')))
            ->when($classroomId || $weekday !== null || $startHour, fn ($query) => $query->whereHas('session', fn ($sessions) => $sessions
                ->when($classroomId, fn ($q) => $q->where('classroom_id', $classroomId))
                ->when($weekday !== null, fn ($q) => $q->where('weekday', $weekday))
                ->when($startHour, fn ($q) => $q->where('start_hour', $startHour))))
            ->orderBy('attendance_date')
            ->get();

        $students = [];
        $byClassroom = [];
        $byWeekday = [];
        $byTime = [];
        $schedules = [];
        $presentCount = 0;
        $absentCount = 0;
        $unmarkedCount = 0;

        foreach ($attendances as $attendance) {
            $schedule = $attendance->studentSchedule;
            $item = $schedule?->contractItem;
            $contract = $item?->contract;
            $student = $contract?->student;
            $session = $attendance->session;

            if (! $schedule || ! $student || ! $session) {
                continue;
            }

            $scheduleKey = (string) $schedule->getKey();
            $schedules[$scheduleKey] ??= [
                'student_id' => $student->getKey(),
                'statuses' => [],
            ];

            if ($attendance->marked_at === null) {
                $unmarkedCount++;

                if ($attendance->attendance_date->isBefore(today())) {
                    $schedules[$scheduleKey]['statuses'][] = [
                        'date' => $attendance->attendance_date->toDateString(),
                        'absent' => false,
                        'confirmed' => false,
                    ];
                }

                continue;
            }

            $isPresent = $attendance->status === 'present';
            $presentCount += $isPresent ? 1 : 0;
            $absentCount += $isPresent ? 0 : 1;
            $studentKey = (string) $student->getKey();
            $weekdayLabel = $schedule->weekdayLabel();
            $timeLabel = Carbon::parse($session->start_hour)->format('H:i').'–'.Carbon::parse($session->end_hour)->format('H:i');
            $classroom = $session->classroom?->name ?? 'Sala não informada';

            $students[$studentKey] ??= [
                'student_id' => $student->getKey(),
                'student' => $student->name,
                'contracts' => [],
                'formations' => [],
                'schedules' => [],
                'sessions' => 0,
                'present' => 0,
                'absent' => 0,
                'last_attended' => null,
            ];

            $students[$studentKey]['contracts'][$contract->getKey()] = $contract->contract_number;
            if ($item?->formation?->name) {
                $students[$studentKey]['formations'][$item->formation->getKey()] = $item->formation->name;
            }

            $students[$studentKey]['sessions']++;
            $students[$studentKey]['present'] += $isPresent ? 1 : 0;
            $students[$studentKey]['absent'] += $isPresent ? 0 : 1;
            if ($isPresent) {
                $students[$studentKey]['last_attended'] = $attendance->attendance_date->toDateString();
            }

            $students[$studentKey]['schedules'][$scheduleKey] = "{$weekdayLabel}, {$timeLabel} — {$classroom}";
            $schedules[$scheduleKey]['statuses'][] = [
                'date' => $attendance->attendance_date->toDateString(),
                'absent' => ! $isPresent,
                'confirmed' => true,
            ];

            $this->incrementBreakdown($byClassroom, (string) $session->classroom_id, $classroom, $student->getKey(), $isPresent);
            $this->incrementBreakdown($byWeekday, (string) $session->weekday, $weekdayLabel, $student->getKey(), $isPresent);
            $this->incrementBreakdown($byTime, $session->start_hour.'|'.$session->end_hour, $timeLabel, $student->getKey(), $isPresent);
        }

        $streakBySchedule = [];
        foreach ($schedules as $scheduleKey => $scheduleData) {
            usort($scheduleData['statuses'], fn (array $a, array $b): int => strcmp($a['date'], $b['date']));
            $streak = 0;
            $maximum = 0;

            foreach ($scheduleData['statuses'] as $status) {
                if (! $status['confirmed']) {
                    $streak = 0;

                    continue;
                }

                $streak = $status['absent'] ? $streak + 1 : 0;
                $maximum = max($maximum, $streak);
            }

            $streakBySchedule[$scheduleKey] = ['student_id' => (string) $scheduleData['student_id'], 'maximum' => $maximum];
        }

        foreach ($students as $studentKey => &$student) {
            $studentStreaks = array_filter($streakBySchedule, fn (array $schedule): bool => $schedule['student_id'] === (string) $studentKey);
            $student['consecutive_absences'] = $studentStreaks === [] ? 0 : max(array_column($studentStreaks, 'maximum'));
            $student['attendance_rate'] = $student['sessions'] > 0
                ? round(($student['present'] / $student['sessions']) * 100, 1)
                : 0;
            $student['absence_rate'] = $student['sessions'] > 0
                ? round(($student['absent'] / $student['sessions']) * 100, 1)
                : 0;
            $student['health'] = $this->health($student['sessions'], $student['attendance_rate'], $student['consecutive_absences']);
            $student['contracts'] = implode(', ', array_filter(array_unique($student['contracts']))) ?: '—';
            $student['formations'] = implode(', ', array_unique($student['formations'])) ?: '—';
            $student['schedules'] = implode('; ', array_unique($student['schedules']));
            $student['last_attended'] = $student['last_attended'] ? Carbon::parse($student['last_attended'])->format('d/m/Y') : 'Nenhuma presença no período';
        }
        unset($student);

        $healthOrder = ['high_risk' => 0, 'attention' => 1, 'healthy' => 2, 'insufficient_data' => 3];
        usort($students, fn (array $a, array $b): int => ($healthOrder[$a['health']['key']] <=> $healthOrder[$b['health']['key']])
            ?: ($a['attendance_rate'] <=> $b['attendance_rate'])
            ?: strcmp($a['student'], $b['student']));

        $studentsAtRisk = count(array_filter($students, fn (array $student): bool => in_array($student['health']['key'], ['attention', 'high_risk'], true)));
        $totalStudents = count($students);
        $totalSessions = $presentCount + $absentCount;

        return [
            'students_monitored' => $totalStudents,
            'students_at_risk' => $studentsAtRisk,
            'students_at_risk_rate' => $totalStudents > 0 ? round(($studentsAtRisk / $totalStudents) * 100, 1) : 0,
            'confirmed_sessions' => $totalSessions,
            'unmarked_sessions' => $unmarkedCount,
            'present_count' => $presentCount,
            'absent_count' => $absentCount,
            'attendance_rate' => $totalSessions > 0 ? round(($presentCount / $totalSessions) * 100, 1) : 0,
            'absence_rate' => $totalSessions > 0 ? round(($absentCount / $totalSessions) * 100, 1) : 0,
            'students' => array_values($students),
            'by_classroom' => $this->finishBreakdown($byClassroom),
            'by_weekday' => $this->finishBreakdown($byWeekday),
            'by_time' => $this->finishBreakdown($byTime),
        ];
    }

    private function health(int $sessions, float $attendanceRate, int $consecutiveAbsences): array
    {
        if ($consecutiveAbsences >= 3 || ($sessions >= self::MINIMUM_SESSIONS_FOR_RISK && $attendanceRate < 60)) {
            return ['key' => 'high_risk', 'label' => 'Risco alto'];
        }

        if ($consecutiveAbsences >= 2 || ($sessions >= self::MINIMUM_SESSIONS_FOR_RISK && $attendanceRate < 80)) {
            return ['key' => 'attention', 'label' => 'Atenção'];
        }

        if ($sessions < self::MINIMUM_SESSIONS_FOR_RISK) {
            return ['key' => 'insufficient_data', 'label' => 'Dados insuficientes'];
        }

        return ['key' => 'healthy', 'label' => 'Regular'];
    }

    private function incrementBreakdown(array &$breakdown, string $key, string $label, int $studentId, bool $present): void
    {
        $breakdown[$key] ??= ['label' => $label, 'students' => [], 'present' => 0, 'absent' => 0];
        $breakdown[$key]['students'][$studentId] = true;
        $breakdown[$key]['present'] += $present ? 1 : 0;
        $breakdown[$key]['absent'] += $present ? 0 : 1;
    }

    private function finishBreakdown(array $breakdown): array
    {
        $rows = array_map(function (array $row): array {
            $sessions = $row['present'] + $row['absent'];

            return [
                'label' => $row['label'],
                'students' => count($row['students']),
                'sessions' => $sessions,
                'present' => $row['present'],
                'absent' => $row['absent'],
                'attendance_rate' => $sessions > 0 ? round(($row['present'] / $sessions) * 100, 1) : 0,
                'absence_rate' => $sessions > 0 ? round(($row['absent'] / $sessions) * 100, 1) : 0,
            ];
        }, array_values($breakdown));

        usort($rows, fn (array $a, array $b): int => $b['sessions'] <=> $a['sessions'] ?: strcmp($a['label'], $b['label']));

        return $rows;
    }
}

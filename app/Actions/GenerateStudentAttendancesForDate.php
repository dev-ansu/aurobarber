<?php

namespace App\Actions;

use App\Models\StudentAttendance;
use App\Models\StudentSchedule;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class GenerateStudentAttendancesForDate
{
    public function execute(DateTimeInterface $date, ?int $schoolId = null, bool $ignoreTenantScope = false): int
    {
        $attendanceDate = Carbon::instance($date)->startOfDay();
        if ($attendanceDate->lessThan(Carbon::today())) {
            throw ValidationException::withMessages([
                'date' => 'Não é permitido gerar ou atualizar chamadas de datas passadas.',
            ]);
        }

        $schedulesQuery = $ignoreTenantScope
            ? StudentSchedule::withoutGlobalScopes()
            : StudentSchedule::query();

        $schedules = $schedulesQuery
            ->with([
                'classroom' => fn ($q) => $ignoreTenantScope ? $q->withoutGlobalScopes() : $q,
                'contractItem.contract' => fn ($q) => $ignoreTenantScope ? $q->withoutGlobalScopes() : $q,
            ])
            ->where('weekday', $attendanceDate->dayOfWeek)
            ->when($schoolId !== null, fn (Builder $query): Builder => $query->where(function (Builder $sq) use ($schoolId, $ignoreTenantScope): void {
                $sq->where('school_id', $schoolId)
                    ->orWhereHas('contractItem.contract', function (Builder $cq) use ($schoolId, $ignoreTenantScope): void {
                        if ($ignoreTenantScope) {
                            $cq->withoutGlobalScopes();
                        }
                        $cq->where('school_id', $schoolId);
                    });
            }))
            ->whereHas('contractItem', fn (Builder $query): Builder => $query
                ->whereNull('deleted_at')
                ->whereNull('formation_completed_at')
                ->whereHas('contract', function (Builder $contractQuery) use ($attendanceDate, $schoolId, $ignoreTenantScope): void {
                    if ($ignoreTenantScope) {
                        $contractQuery->withoutGlobalScopes();
                    }
                    $contractQuery
                        ->where('status', 'active')
                        ->whereDate('start_date', '<=', $attendanceDate)
                        ->when($schoolId !== null, fn (Builder $cq): Builder => $cq->where('school_id', $schoolId));
                }))
            ->get();
        $schedulesByClassSession = $schedules->groupBy(
            fn (StudentSchedule $schedule): string => implode('|', [
                $schedule->classroom_id,
                $schedule->start_hour,
                $schedule->end_hour,
            ]),
        );

        $createdCount = 0;

        foreach ($schedulesByClassSession as $group) {
            /** @var StudentSchedule $schedule */
            $schedule = $group->first();
            if (! $schedule?->classroom) {
                continue;
            }

            $session = $schedule->classroom->sessions()->firstOrCreate(
                [
                    'date' => $attendanceDate,
                    'start_hour' => $schedule->start_hour,
                    'end_hour' => $schedule->end_hour,
                ],
                ['weekday' => $attendanceDate->dayOfWeek],
            );

            foreach ($group as $schedule) {
                $resolvedSchoolId = $schedule->school_id
                    ?? $schedule->classroom?->school_id
                    ?? $schedule->contractItem?->contract?->school_id;

                $attendance = StudentAttendance::withoutGlobalScopes()->firstOrCreate(
                    [
                        'class_session_id' => $session->getKey(),
                        'student_schedule_id' => $schedule->getKey(),
                    ],
                    [
                        'school_id' => $resolvedSchoolId,
                        'attendance_date' => $attendanceDate,
                        'status' => 'absent',
                    ],
                );

                if ($attendance->school_id === null && $resolvedSchoolId !== null) {
                    $attendance->forceFill(['school_id' => $resolvedSchoolId])->saveQuietly();
                }

                if ($attendance->wasRecentlyCreated) {
                    $createdCount++;
                }
            }
        }

        return $createdCount;
    }
}

<?php

namespace App\Actions;

use App\Models\Contract;
use App\Models\StudentAttendance;
use App\Models\StudentSchedule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MarkStudentAttendanceAtKiosk
{
    public function execute(string $contractNumber, Carbon $at, ?int $schoolId = null): ?StudentAttendance
    {
        $this->ensureAttendancesGeneratedForDate($at, $schoolId, $contractNumber);

        return DB::transaction(function () use ($contractNumber, $at, $schoolId): ?StudentAttendance {
            $attendances = $this->baseAttendanceQuery($contractNumber, $at, $schoolId)
                ->lockForUpdate()
                ->get();

            $time = $at->format('H:i:s');
            $attendance = $attendances->first(fn (StudentAttendance $attendance): bool => (
                Carbon::parse($attendance->session->start_hour)->format('H:i:s') <= $time
                && Carbon::parse($attendance->session->end_hour)->format('H:i:s') >= $time
            ));

            if ($attendance === null) {
                return null;
            }

            $contractSchoolId = $attendance->studentSchedule?->contractItem?->contract?->school_id;
            if ($attendance->school_id === null && $contractSchoolId !== null) {
                $attendance->forceFill(['school_id' => $contractSchoolId])->saveQuietly();
            }

            $attendance->mark('present', null, 'kiosk');

            // Sincronização automática com a Ouro Moderno ao registrar presença no Totem
            $contract = $attendance->studentSchedule?->contractItem?->contract;
            if ($contract && $contract->hasActiveOuroModernoCourse()) {
                try {
                    app(\App\Services\OuroModernoProgressSyncService::class)->syncAllForContract($contract);
                } catch (\Throwable) {
                    // Silenciosamente ignora indisponibilidade da API externa para não atrasar o Totem
                }
            }

            return $attendance;
        });
    }

    public function findScheduledAttendance(string $contractNumber, Carbon $at, ?int $schoolId = null): ?StudentAttendance
    {
        $this->ensureAttendancesGeneratedForDate($at, $schoolId, $contractNumber);

        $attendances = $this->baseAttendanceQuery($contractNumber, $at, $schoolId)->get();

        $time = $at->format('H:i:s');

        return $attendances->first(fn (StudentAttendance $attendance): bool => (
            Carbon::parse($attendance->session->start_hour)->format('H:i:s') <= $time
            && Carbon::parse($attendance->session->end_hour)->format('H:i:s') >= $time
        ));
    }

    /**
     * @return Builder<StudentAttendance>
     */
    private function baseAttendanceQuery(string $contractNumber, Carbon $at, ?int $schoolId = null): Builder
    {
        return StudentAttendance::withoutGlobalScopes()
            ->whereDate('attendance_date', $at->toDateString())
            ->whereHas(
                'studentSchedule',
                fn (Builder $scheduleQuery): Builder => $scheduleQuery
                    ->withoutGlobalScopes()
                    ->whereHas(
                        'contractItem.contract',
                        fn (Builder $contractQuery): Builder => $this->applyContractFilter(
                            $contractQuery->withoutGlobalScopes()
                                ->when($schoolId !== null, fn (Builder $sq): Builder => $sq->where('school_id', $schoolId)),
                            $contractNumber
                        ),
                    ),
            )
            ->whereHas(
                'session',
                fn (Builder $query): Builder => $query->whereDate('date', $at->toDateString()),
            )
            ->with([
                'session',
                'studentSchedule' => fn ($sq) => $sq->withoutGlobalScopes()->with([
                    'contractItem.contract' => fn ($cq) => $cq->withoutGlobalScopes()->with(['student', 'school']),
                ]),
            ])
            ->orderBy('id');
    }

    public function applyContractFilter(Builder $query, string $identifier): Builder
    {
        $raw = trim($identifier);
        $upper = Str::upper($raw);
        $cleanDigits = preg_replace('/\D/', '', $raw);

        return $query->where(function (Builder $q) use ($raw, $upper, $cleanDigits): void {
            $q->where('contract_number', $upper)
                ->orWhere('contract_number', $raw)
                ->orWhereHas('student', function (Builder $sq) use ($raw, $cleanDigits): void {
                    $sq->whereRaw('LOWER(email) = ?', [mb_strtolower($raw)])
                        ->orWhere('ouro_moderno_usuario', $raw);

                    if (! empty($cleanDigits)) {
                        $sq->orWhereCpf($cleanDigits);
                    }
                });
        });
    }

    private function ensureAttendancesGeneratedForDate(Carbon $at, ?int $schoolId = null, ?string $identifier = null): void
    {
        if ($at->toDateString() < Carbon::today()->toDateString()) {
            return;
        }

        if ($identifier !== null && trim($identifier) !== '') {
            $matchingContracts = Contract::withoutGlobalScopes()
                ->where('status', 'active')
                ->whereDate('start_date', '<=', $at->toDateString())
                ->when($schoolId !== null, fn (Builder $q): Builder => $q->where('school_id', $schoolId))
                ->tap(fn (Builder $q) => $this->applyContractFilter($q, $identifier))
                ->get(['id', 'school_id']);

            if ($matchingContracts->isNotEmpty()) {
                $contractIds = $matchingContracts->pluck('id')->all();

                $missingScheduleForToday = StudentSchedule::withoutGlobalScopes()
                    ->where('weekday', $at->dayOfWeek)
                    ->whereHas('contractItem', fn (Builder $iq): Builder => $iq
                        ->whereIn('contract_id', $contractIds)
                        ->whereNull('deleted_at')
                        ->whereNull('formation_completed_at'))
                    ->whereDoesntHave(
                        'attendances',
                        fn (Builder $aq): Builder => $aq
                            ->withoutGlobalScopes()
                            ->whereDate('attendance_date', $at->toDateString())
                    )
                    ->exists();

                if ($missingScheduleForToday) {
                    $schoolIds = $matchingContracts->pluck('school_id')->unique()->values();
                    foreach ($schoolIds as $targetSchoolId) {
                        app(GenerateStudentAttendancesForDate::class)->execute(
                            $at,
                            $targetSchoolId !== null ? (int) $targetSchoolId : null,
                            ignoreTenantScope: true
                        );
                    }
                }

                return;
            }
        }

        $hasAttendances = StudentAttendance::withoutGlobalScopes()
            ->when($schoolId !== null, fn (Builder $q): Builder => $q->where('school_id', $schoolId))
            ->whereDate('attendance_date', $at->toDateString())
            ->exists();

        if (! $hasAttendances) {
            app(GenerateStudentAttendancesForDate::class)->execute($at, $schoolId, ignoreTenantScope: true);
        }
    }
}

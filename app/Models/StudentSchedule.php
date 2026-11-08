<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class StudentSchedule extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $fillable = [
        'school_id',
        'contract_item_id',
        'classroom_id',
        'weekday',
        'start_hour',
        'end_hour',
    ];

    protected $casts = [
        'weekday' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (StudentSchedule $schedule): void {
            if (
                $schedule->exists
                && $schedule->isDirty(['contract_item_id', 'classroom_id', 'weekday', 'start_hour', 'end_hour'])
                && $schedule->attendances()->exists()
            ) {
                throw ValidationException::withMessages([
                    'schedule' => 'Este horário já possui chamadas. Arquive-o e crie outro para preservar o histórico.',
                ]);
            }

            $schedule->validateAvailability();
        });

        static::saved(function (StudentSchedule $schedule): void {
            $schedule->contractItem->contract->recalculateEstimatedEndDate();
        });

        static::deleted(function (StudentSchedule $schedule): void {
            $schedule->contractItem->contract->recalculateEstimatedEndDate();
        });
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function contractItem(): BelongsTo
    {
        return $this->belongsTo(ContractItem::class)->withTrashed();
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(StudentAttendance::class);
    }

    public function weekdayLabel(): string
    {
        return [
            0 => 'Domingo',
            1 => 'Segunda-feira',
            2 => 'Terça-feira',
            3 => 'Quarta-feira',
            4 => 'Quinta-feira',
            5 => 'Sexta-feira',
            6 => 'Sábado',
        ][$this->weekday] ?? 'Dia inválido';
    }

    public function formattedStartHour(): string
    {
        return Carbon::parse($this->start_hour)->format('H:i');
    }

    public function formattedEndHour(): string
    {
        return Carbon::parse($this->end_hour)->format('H:i');
    }

    public function nextOccurrenceDate(): Carbon
    {
        $date = Carbon::today();

        if (
            $date->dayOfWeek !== $this->weekday
            || Carbon::now()->format('H:i:s') > $this->end_hour
        ) {
            $daysUntilOccurrence = ($this->weekday - $date->dayOfWeek + 7) % 7;
            $date->addDays($daysUntilOccurrence === 0 ? 7 : $daysUntilOccurrence);
        }

        return $date;
    }

    /**
     * @return array<int, array<string, string>>
     */
    public static function availableSlotsByClassroom(
        int $weekday,
        int $studentId,
        ?int $exceptScheduleId = null,
        ?int $schoolId = null,
    ): array {
        if ($weekday < 0 || $weekday > 6) {
            return [];
        }

        $classroomsQuery = Classroom::withoutGlobalScopes()
            ->where('active', true)
            ->when($schoolId !== null, fn (Builder $q) => $q->where(fn (Builder $sub) => $sub->where('school_id', $schoolId)->orWhereNull('school_id')))
            ->orderBy('name');

        $classrooms = $classroomsQuery
            ->get(['id', 'name', 'capacity', 'opening_time', 'closing_time', 'school_id']);

        if ($classrooms->isEmpty()) {
            return [];
        }

        $activeContractItem = fn (Builder $query): Builder => $query
            ->whereNull('deleted_at')
            ->whereNull('formation_completed_at')
            ->whereHas('contract', fn (Builder $contractQuery): Builder => $contractQuery
                ->withoutGlobalScopes()
                ->whereNull('deleted_at')
                ->whereIn('status', ['draft', 'active']));

        $roomSchedules = self::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->whereIn('classroom_id', $classrooms->modelKeys())
            ->where('weekday', $weekday)
            ->whereHas('contractItem', $activeContractItem)
            ->when($exceptScheduleId !== null, fn (Builder $query): Builder => $query
                ->where('id', '!=', $exceptScheduleId))
            ->get(['id', 'classroom_id', 'start_hour', 'end_hour']);

        $studentSchedules = self::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where('weekday', $weekday)
            ->whereHas('contractItem', fn (Builder $query): Builder => $query
                ->whereNull('deleted_at')
                ->whereNull('formation_completed_at')
                ->whereHas('contract', fn (Builder $contractQuery): Builder => $contractQuery
                    ->withoutGlobalScopes()
                    ->whereNull('deleted_at')
                    ->where('student_id', $studentId)
                    ->whereIn('status', ['draft', 'active'])))
            ->when($exceptScheduleId !== null, fn (Builder $query): Builder => $query
                ->where('id', '!=', $exceptScheduleId))
            ->get(['id', 'start_hour', 'end_hour']);

        $availableSlots = [];

        foreach ($classrooms as $classroom) {
            $openingHour = Carbon::parse($classroom->opening_time)->hour;
            $closingHour = Carbon::parse($classroom->closing_time)->hour;
            $roomScheduleSlots = $roomSchedules->where('classroom_id', $classroom->id);
            $availableSlots[$classroom->id] = [];

            for ($hour = $openingHour; $hour < $closingHour; $hour++) {
                $startTime = sprintf('%02d:00:00', $hour);
                $endTime = sprintf('%02d:00:00', $hour + 1);
                $studentHasConflict = $studentSchedules->contains(
                    fn (StudentSchedule $schedule): bool => $schedule->start_hour < $endTime
                        && $schedule->end_hour > $startTime,
                );

                if (
                    $studentHasConflict
                    || ! self::hasAvailableSeat($roomScheduleSlots, $startTime, $endTime, $classroom->capacity)
                ) {
                    continue;
                }

                $availableSlots[$classroom->id][$startTime] = sprintf(
                    '%s - %s',
                    Carbon::parse($startTime)->format('H:i'),
                    Carbon::parse($endTime)->format('H:i'),
                );
            }
        }

        return $availableSlots;
    }

    /**
     * @param  Collection<int, StudentSchedule>  $roomSchedules
     */
    private static function hasAvailableSeat(
        Collection $roomSchedules,
        string $startHour,
        string $endHour,
        int $capacity,
    ): bool {
        $startSeconds = self::timeInSeconds($startHour);
        $endSeconds = self::timeInSeconds($endHour);
        $occupancy = 1;
        $events = [];

        foreach ($roomSchedules as $schedule) {
            $schedStart = self::timeInSeconds($schedule->start_hour);
            $schedEnd = self::timeInSeconds($schedule->end_hour);

            if ($schedStart >= $endSeconds || $schedEnd <= $startSeconds) {
                continue;
            }

            $overlapStart = max($startSeconds, $schedStart);
            $overlapEnd = min($endSeconds, $schedEnd);

            if ($overlapStart === $startSeconds) {
                $occupancy++;
            } else {
                $events[] = ['time' => $overlapStart, 'change' => 1];
            }

            if ($overlapEnd < $endSeconds) {
                $events[] = ['time' => $overlapEnd, 'change' => -1];
            }
        }

        $peakOccupancy = $occupancy;
        usort($events, fn (array $left, array $right): int => (
            $left['time'] <=> $right['time']
            ?: $left['change'] <=> $right['change']
        ));

        foreach ($events as $event) {
            $occupancy += $event['change'];
            $peakOccupancy = max($peakOccupancy, $occupancy);
        }

        return $peakOccupancy <= $capacity;
    }

    private function validateAvailability(): void
    {
        $this->start_hour = Carbon::parse($this->start_hour)->format('H:i:s');
        $this->end_hour = Carbon::parse($this->end_hour)->format('H:i:s');

        if ($this->weekday < 0 || $this->weekday > 6) {
            throw ValidationException::withMessages([
                'weekday' => 'Selecione um dia da semana válido.',
            ]);
        }

        $startSeconds = self::timeInSeconds($this->start_hour);
        $endSeconds = self::timeInSeconds($this->end_hour);

        if ($startSeconds >= $endSeconds) {
            throw ValidationException::withMessages([
                'end_hour' => 'O horário de término deve ser posterior ao horário de início.',
            ]);
        }

        if (
            Carbon::parse($this->start_hour)->minute !== 0
            || Carbon::parse($this->start_hour)->second !== 0
            || Carbon::parse($this->end_hour)->minute !== 0
            || Carbon::parse($this->end_hour)->second !== 0
            || $endSeconds - $startSeconds !== 3600
        ) {
            throw ValidationException::withMessages([
                'end_hour' => 'Cada horário deve corresponder a um bloco iniciado em hora cheia e com duração de uma hora.',
            ]);
        }

        $classroom = Classroom::withoutGlobalScopes()->findOrFail($this->classroom_id);
        $openingSeconds = self::timeInSeconds($classroom->opening_time);
        $closingSeconds = self::timeInSeconds($classroom->closing_time);

        if ($startSeconds < $openingSeconds || $endSeconds > $closingSeconds) {
            throw ValidationException::withMessages([
                'classroom_id' => "O horário deve estar dentro do funcionamento da sala {$classroom->name}.",
            ]);
        }

        $contractItem = $this->contractItem()
            ->with(['contract' => fn ($q) => $q->withoutGlobalScopes()->select(['id', 'student_id', 'status', 'school_id'])])
            ->firstOrFail();

        if ($contractItem->trashed()) {
            throw ValidationException::withMessages([
                'contract_item_id' => 'Não é possível agendar uma formação arquivada.',
            ]);
        }

        $contract = $contractItem->contract;

        if (! $contract || ! in_array($contract->status, ['draft', 'active'], true)) {
            throw ValidationException::withMessages([
                'contract_item_id' => 'Só é possível agendar formações de contratos em rascunho ou ativos.',
            ]);
        }

        if ($this->school_id === null) {
            $this->school_id = $classroom->school_id ?? $contract->school_id;
        }

        $studentHasConflict = self::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where('weekday', $this->weekday)
            ->where('start_hour', '<', $this->end_hour)
            ->where('end_hour', '>', $this->start_hour)
            ->whereHas(
                'contractItem',
                fn ($query) => $query
                    ->whereNull('deleted_at')
                    ->whereNull('formation_completed_at')
                    ->whereHas(
                        'contract',
                        fn ($q) => $q
                            ->withoutGlobalScopes()
                            ->whereNull('deleted_at')
                            ->where('student_id', $contract->student_id)
                            ->whereIn('status', ['draft', 'active']),
                    ),
            )
            ->when($this->exists, fn ($query) => $query->where('id', '!=', $this->getKey()))
            ->exists();

        if ($studentHasConflict) {
            throw ValidationException::withMessages([
                'start_hour' => 'O aluno já possui outro curso ou formação agendado nesse horário.',
            ]);
        }

        $availableSlots = self::availableSlotsByClassroom(
            $this->weekday,
            $contract->student_id,
            $this->exists ? $this->getKey() : null,
            $classroom->school_id ?? $contract->school_id,
        );

        if (! array_key_exists($this->start_hour, $availableSlots[$classroom->id] ?? [])) {
            throw ValidationException::withMessages([
                'classroom_id' => "A sala {$classroom->name} não possui vaga disponível nesse horário.",
            ]);
        }
    }

    private static function timeInSeconds(string $time): int
    {
        $parsedTime = Carbon::parse($time);

        return ($parsedTime->hour * 3600) + ($parsedTime->minute * 60) + $parsedTime->second;
    }
}

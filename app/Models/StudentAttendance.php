<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'school_id',
    'student_schedule_id',
    'class_session_id',
    'attendance_date',
    'status',
    'note',
    'marked_by_user_id',
    'marked_at',
    'marking_source',
])]
class StudentAttendance extends Model
{
    use BelongsToSchool;

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'marked_at' => 'datetime',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function studentSchedule(): BelongsTo
    {
        return $this->belongsTo(StudentSchedule::class)->withTrashed();
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ClassSession::class, 'class_session_id');
    }

    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by_user_id');
    }

    public function mark(string $status, ?User $user, string $source, ?string $note = null): void
    {
        if (! in_array($status, ['absent', 'present'], true)) {
            throw ValidationException::withMessages([
                'status' => 'Selecione uma situação de presença válida.',
            ]);
        }

        if (! in_array($source, ['staff', 'student', 'kiosk'], true)) {
            throw ValidationException::withMessages([
                'marking_source' => 'Selecione uma origem válida para o registro de presença.',
            ]);
        }

        if ($source !== 'kiosk' && $user === null) {
            throw ValidationException::withMessages([
                'marked_by_user_id' => 'É necessário identificar o usuário que registrou a presença.',
            ]);
        }

        if (
            $this->status === $status
            && $this->note === $note
            && $this->marked_at !== null
        ) {
            return;
        }

        $this->forceFill([
            'status' => $status,
            'marked_by_user_id' => $user?->getKey(),
            'marked_at' => Carbon::now(),
            'marking_source' => $source,
            'note' => $note,
        ])->save();
    }

    public ?string $auditLogReason = null;

    public ?int $auditLogUserId = null;

    public function changeStatus(string $newStatus, User $user, string $reason): void
    {
        if (! in_array($newStatus, ['absent', 'present'], true)) {
            throw ValidationException::withMessages([
                'status' => 'Selecione uma situação de presença válida (Presença ou Falta).',
            ]);
        }

        $reason = trim($reason);
        if ($reason === '') {
            throw ValidationException::withMessages([
                'reason' => 'A justificativa é obrigatória para alterar a situação da chamada.',
            ]);
        }

        $this->auditLogReason = $reason;
        $this->auditLogUserId = (int) $user->getKey();

        $this->forceFill([
            'status' => $newStatus,
            'marked_by_user_id' => $user->getKey(),
            'marked_at' => Carbon::now(),
            'marking_source' => 'staff',
            'note' => $reason,
        ])->save();
    }
}

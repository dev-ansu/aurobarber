<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LmsStudentLessonProgress extends Model
{
    use HasFactory;

    protected $table = 'lms_student_lesson_progress';

    protected $fillable = [
        'contract_id',
        'student_id',
        'lesson_id',
        'current_step_id',
        'status',
        'score',
        'started_at',
        'completed_at',
        'session_date',
        'time_spent_seconds',
        'last_heartbeat_at',
        'exited_at',
        'help_requested',
        'help_requested_at',
        'is_in_focus',
        'focus_lost_at',
        'total_focus_lost_seconds',
        'educator_message',
        'educator_message_sender',
        'educator_message_at',
        'educator_message_read_at',
        'student_rating',
        'student_rating_comment',
        'student_rated_at',
        'typing_warmup_completed_at',
        'typing_time_seconds',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'session_date' => 'date',
            'time_spent_seconds' => 'integer',
            'last_heartbeat_at' => 'datetime',
            'exited_at' => 'datetime',
            'help_requested' => 'boolean',
            'help_requested_at' => 'datetime',
            'is_in_focus' => 'boolean',
            'focus_lost_at' => 'datetime',
            'total_focus_lost_seconds' => 'integer',
            'educator_message_at' => 'datetime',
            'educator_message_read_at' => 'datetime',
            'student_rating' => 'integer',
            'student_rated_at' => 'datetime',
            'typing_warmup_completed_at' => 'datetime',
            'typing_time_seconds' => 'integer',
        ];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'student_id');
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(LmsLesson::class, 'lesson_id');
    }

    public function currentStep(): BelongsTo
    {
        return $this->belongsTo(LmsLessonStep::class, 'current_step_id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(LmsStudentStepAttempt::class, 'progress_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(LmsStudentAssignment::class, 'progress_id');
    }
}

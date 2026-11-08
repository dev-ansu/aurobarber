<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LmsStudentAssignment extends Model
{
    use HasFactory;

    protected $table = 'lms_student_assignments';

    protected $fillable = [
        'progress_id',
        'step_id',
        'contract_id',
        'student_id',
        'file_path',
        'file_name',
        'student_notes',
        'status',
        'grade',
        'feedback',
        'graded_by_user_id',
        'graded_at',
    ];

    protected function casts(): array
    {
        return [
            'grade' => 'decimal:2',
            'graded_at' => 'datetime',
        ];
    }

    public function progress(): BelongsTo
    {
        return $this->belongsTo(LmsStudentLessonProgress::class, 'progress_id');
    }

    public function step(): BelongsTo
    {
        return $this->belongsTo(LmsLessonStep::class, 'step_id');
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'student_id');
    }

    public function gradedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by_user_id');
    }

    public function getOriginalNameAttribute(): string
    {
        return (string) ($this->file_name ?? '');
    }

    public function getScoreAttribute(): ?float
    {
        return $this->grade !== null ? (float) $this->grade : null;
    }

    public function getEducatorFeedbackAttribute(): ?string
    {
        return $this->feedback;
    }

    public function getLessonAttribute(): ?LmsLesson
    {
        return $this->step?->lesson ?? $this->progress?->lesson;
    }
}

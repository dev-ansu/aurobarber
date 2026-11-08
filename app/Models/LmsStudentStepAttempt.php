<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LmsStudentStepAttempt extends Model
{
    use HasFactory;

    protected $table = 'lms_student_step_attempts';

    protected $fillable = [
        'progress_id',
        'step_id',
        'attempt_number',
        'is_correct',
        'student_answer',
        'attempt_data',
    ];

    protected function casts(): array
    {
        return [
            'attempt_number' => 'integer',
            'is_correct' => 'boolean',
            'attempt_data' => 'array',
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
}

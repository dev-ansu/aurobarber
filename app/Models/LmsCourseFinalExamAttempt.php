<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LmsCourseFinalExamAttempt extends Model
{
    use HasFactory;

    protected $table = 'lms_course_final_exam_attempts';

    protected $fillable = [
        'contract_id',
        'course_id',
        'student_id',
        'score',
        'passed',
        'total_questions',
        'correct_answers',
        'answers_payload',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'passed' => 'boolean',
            'total_questions' => 'integer',
            'correct_answers' => 'integer',
            'answers_payload' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'student_id');
    }
}

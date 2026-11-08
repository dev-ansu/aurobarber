<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SatisfactionSurveyResponse extends Model
{
    protected $fillable = [
        'school_id',
        'satisfaction_survey_id',
        'contract_id',
        'student_id',
        'student_attendance_id',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function survey(): BelongsTo
    {
        return $this->belongsTo(SatisfactionSurvey::class, 'satisfaction_survey_id');
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'student_id');
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(StudentAttendance::class, 'student_attendance_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(SatisfactionSurveyAnswer::class);
    }
}

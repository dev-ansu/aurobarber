<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SatisfactionSurveyAnswer extends Model
{
    protected $fillable = [
        'satisfaction_survey_response_id',
        'satisfaction_survey_question_id',
        'rating_value',
        'text_value',
    ];

    protected function casts(): array
    {
        return [
            'rating_value' => 'integer',
        ];
    }

    public function response(): BelongsTo
    {
        return $this->belongsTo(SatisfactionSurveyResponse::class, 'satisfaction_survey_response_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(SatisfactionSurveyQuestion::class, 'satisfaction_survey_question_id');
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SatisfactionSurveyQuestion extends Model
{
    protected $fillable = [
        'satisfaction_survey_id',
        'question',
        'type',
        'options',
        'is_required',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'is_required' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function survey(): BelongsTo
    {
        return $this->belongsTo(SatisfactionSurvey::class, 'satisfaction_survey_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(SatisfactionSurveyAnswer::class);
    }
}

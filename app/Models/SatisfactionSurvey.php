<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SatisfactionSurvey extends Model
{
    protected $fillable = [
        'school_id',
        'title',
        'description',
        'is_active',
        'created_by_id',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function questions(): HasMany
    {
        return $this->hasMany(SatisfactionSurveyQuestion::class)->orderBy('sort_order')->orderBy('id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(SatisfactionSurveyResponse::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function scopeForSchool(Builder $query, ?int $schoolId): Builder
    {
        if ($schoolId === null) {
            return $query;
        }

        return $query->where(function (Builder $sub) use ($schoolId) {
            $sub->whereNull('school_id')
                ->orWhere('school_id', $schoolId);
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getAverageRatingAttribute(): ?float
    {
        $avg = SatisfactionSurveyAnswer::query()
            ->whereHas('response', fn ($q) => $q->where('satisfaction_survey_id', $this->id))
            ->whereNotNull('rating_value')
            ->avg('rating_value');

        return $avg !== null ? round((float) $avg, 2) : null;
    }
}

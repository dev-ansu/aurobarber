<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LmsLesson extends Model
{
    use HasFactory;

    protected $table = 'lms_lessons';

    protected $fillable = [
        'module_id',
        'title',
        'description',
        'estimated_duration_minutes',
        'passing_score',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'estimated_duration_minutes' => 'integer',
            'passing_score' => 'decimal:2',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(LmsModule::class, 'module_id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(LmsLessonStep::class, 'lesson_id')
            ->orderByRaw("CASE phase 
                WHEN 'praelectio' THEN 1 
                WHEN 'practice' THEN 2 
                WHEN 'repetitio' THEN 3 
                WHEN 'concertatio' THEN 4 
                WHEN 'examen' THEN 5 
                ELSE 6 END")
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(LmsStudentLessonProgress::class, 'lesson_id');
    }

    protected static function booted(): void
    {
        static::deleting(function (self $lesson): void {
            foreach ($lesson->steps as $step) {
                $step->delete();
            }
        });
    }
}


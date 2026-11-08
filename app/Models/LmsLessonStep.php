<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LmsLessonStep extends Model
{
    use HasFactory;

    protected $table = 'lms_lesson_steps';

    protected $fillable = [
        'lesson_id',
        'phase',
        'step_type',
        'title',
        'content',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'sort_order' => 'integer',
        ];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(LmsLesson::class, 'lesson_id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(LmsStudentStepAttempt::class, 'step_id');
    }

    protected static function booted(): void
    {
        static::deleting(function (self $step): void {
            $step->deleteAssociatedMedia();
        });
    }

    public function deleteAssociatedMedia(): void
    {
        $urls = [];
        if (! empty($this->content['media_url'])) {
            $urls[] = $this->content['media_url'];
        }
        if (! empty($this->content['document_url'])) {
            $urls[] = $this->content['document_url'];
        }

        foreach ($urls as $url) {
            if (is_string($url) && str_contains($url, 'lms-media')) {
                $path = 'lms-media/' . basename(parse_url($url, PHP_URL_PATH));
                if (\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($path);
                }
            }
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LmsModule extends Model
{
    use HasFactory;

    protected $table = 'lms_modules';

    protected $fillable = [
        'course_id',
        'title',
        'description',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(LmsLesson::class, 'module_id')->orderBy('sort_order');
    }

    protected static function booted(): void
    {
        static::deleting(function (self $module): void {
            foreach ($module->lessons as $lesson) {
                $lesson->delete();
            }
        });
    }
}

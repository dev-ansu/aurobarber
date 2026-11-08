<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class StudentAnnouncement extends Model
{
    protected $fillable = [
        'school_id',
        'title',
        'body',
        'image_path',
        'external_url',
        'expires_at',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::updated(function (self $announcement): void {
            if ($announcement->wasChanged('image_path') && $oldPath = $announcement->getOriginal('image_path')) {
                Storage::disk('public')->delete($oldPath);
            }
        });

        static::deleted(function (self $announcement): void {
            if ($announcement->image_path) {
                Storage::disk('public')->delete($announcement->image_path);
            }
        });
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

    public function scopeVisibleToStudents(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(fn (Builder $query) => $query->whereNull('expires_at')->orWhere('expires_at', '>=', now()))
            ->orderBy('sort_order')
            ->orderBy('id');
    }
}

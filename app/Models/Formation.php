<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('formations')]
#[Fillable([
    'name',
    'description',
    'short_description',
    'image_path',
    'workload_hours',
    'duration_months',
    'has_certificate',
    'highlight_badge',
    'show_in_showcase',
    'order',
    'active',
])]
class Formation extends Model
{
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'has_certificate' => 'boolean',
            'show_in_showcase' => 'boolean',
            'workload_hours' => 'integer',
            'duration_months' => 'integer',
            'order' => 'integer',
        ];
    }

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image_path) {
            return asset('images/formation-placeholder.svg');
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->url($this->image_path);
    }

    public function getTotalWorkloadAttribute(): int
    {
        if ($this->workload_hours && $this->workload_hours > 0) {
            return (int) $this->workload_hours;
        }

        return (int) $this->courses->sum('workload');
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'formation_course')
            ->withPivot('order')
            ->orderByPivot('order');
    }

    public function contractItems(): HasMany
    {
        return $this->hasMany(ContractItem::class);
    }
}

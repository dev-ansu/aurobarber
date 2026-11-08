<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Professional extends Model
{
    use HasFactory;

    protected $fillable = [
        'barbershop_id',
        'user_id',
        'name',
        'nickname',
        'phone',
        'photo_url',
        'bio',
        'commission_rate',
        'working_days',
        'work_start_time',
        'work_end_time',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'commission_rate' => 'decimal:2',
        'working_days' => 'array',
        'is_active' => 'boolean',
    ];

    public function barbershop(): BelongsTo
    {
        return $this->belongsTo(Barbershop::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->nickname ?: $this->name;
    }
}

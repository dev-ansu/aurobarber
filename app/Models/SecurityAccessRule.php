<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityAccessRule extends Model
{
    protected $table = 'security_access_rules';

    protected $fillable = [
        'type',
        'ip_address',
        'user_id',
        'device_fingerprint',
        'label',
        'user_agent',
        'is_active',
        'expires_at',
        'last_matched_at',
        'match_count',
        'notes',
        'created_by_user_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
        'last_matched_at' => 'datetime',
        'match_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(function (Builder $q): void {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeWhitelist(Builder $query): Builder
    {
        return $query->where('type', 'whitelist');
    }

    public function scopeBlacklist(Builder $query): Builder
    {
        return $query->where('type', 'blacklist');
    }
}

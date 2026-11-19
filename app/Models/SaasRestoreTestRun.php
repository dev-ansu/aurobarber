<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaasRestoreTestRun extends Model
{
    protected $table = 'saas_restore_test_runs';

    protected $fillable = [
        'created_by_id',
        'backup_file',
        'target_schema',
        'status',
        'progress',
        'phase',
        'message',
        'access_token_hash',
        'access_url',
        'expires_at',
        'automated_check_status',
        'automated_check_results',
        'human_validation_status',
        'human_validation_notes',
        'human_validated_at',
        'human_validated_by_user_id',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'progress' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'expires_at' => 'datetime',
            'human_validated_at' => 'datetime',
            'automated_check_results' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function humanValidatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'human_validated_by_user_id');
    }

    public function isReady(): bool
    {
        return $this->status === 'ready';
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isActive(): bool
    {
        return $this->isReady() && ! $this->isExpired();
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortalTrafficEvent extends Model
{
    use HasFactory;

    protected $table = 'portal_traffic_events';

    public $timestamps = false;

    protected $fillable = [
        'scope',
        'school_id',
        'formation_id',
        'event_type',
        'event_label',
        'visitor_hash',
        'device_type',
        'utm_source',
        'utm_campaign',
        'referrer',
        'page_url',
        'ip_address',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'school_id' => 'integer',
        'formation_id' => 'integer',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        // Garante que em PostgreSQL multi-tenant sempre leia do schema public centralizado
        $defaultDriver = config('database.default');
        if (config("database.connections.{$defaultDriver}.driver") === 'pgsql') {
            $this->setTable('public.portal_traffic_events');
        }
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function formation(): BelongsTo
    {
        return $this->belongsTo(Formation::class);
    }

    public function scopeAurosoft(Builder $query): Builder
    {
        return $query->where('scope', 'aurosoft_landing');
    }

    public function scopeSchool(Builder $query, int $schoolId): Builder
    {
        return $query->where('scope', 'school_showcase')->where('school_id', $schoolId);
    }

    public function scopeLastDays(Builder $query, int $days = 30): Builder
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays($days));
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->where('created_at', '>=', Carbon::today());
    }
}

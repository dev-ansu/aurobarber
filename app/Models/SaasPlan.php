<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\UsesPublicSchema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class SaasPlan extends Model
{
    use UsesPublicSchema;

    protected $table = 'saas_plans';

    protected $fillable = [
        'name',
        'slug',
        'badge',
        'description',
        'monthly_price',
        'billing_cycle_months',
        'renewal_days',
        'max_units',
        'features',
        'is_featured',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'monthly_price' => 'decimal:2',
        'billing_cycle_months' => 'integer',
        'renewal_days' => 'integer',
        'max_units' => 'integer',
        'features' => 'array',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function getTable(): string
    {
        return DB::connection()->getDriverName() === 'pgsql'
            ? 'public.saas_plans'
            : 'saas_plans';
    }

    public function barbershops(): HasMany
    {
        return $this->hasMany(Barbershop::class, 'saas_plan_id');
    }

    public function planChangeRequests(): HasMany
    {
        return $this->hasMany(SaasPlanChangeRequest::class, 'requested_plan_id');
    }

    public function getCycleTotalAttribute(): float
    {
        $months = max(1, (int) $this->billing_cycle_months);

        return round((float) $this->monthly_price * $months, 2);
    }

    public function getCycleLabelAttribute(): string
    {
        return match ((int) $this->billing_cycle_months) {
            1 => 'Mensal (30 dias)',
            3 => 'Trimestral',
            6 => 'Semestral (6 meses)',
            12 => 'Anual (12 meses)',
            default => "{$this->billing_cycle_months} meses",
        };
    }

    public function isDifferentFrom(Barbershop $barbershop): bool
    {
        return (int) $barbershop->saas_plan_id !== (int) $this->id;
    }
}

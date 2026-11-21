<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\UsesPublicSchema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class SaasPlanChangeRequest extends Model
{
    use UsesPublicSchema;

    protected $table = 'saas_plan_change_requests';

    protected $fillable = [
        'barbershop_id',
        'current_plan_id',
        'requested_plan_id',
        'requested_by_name',
        'requested_by_email',
        'status',
        'notes',
    ];

    public function getTable(): string
    {
        return DB::connection()->getDriverName() === 'pgsql'
            ? 'public.saas_plan_change_requests'
            : 'saas_plan_change_requests';
    }

    public function barbershop(): BelongsTo
    {
        return $this->belongsTo(Barbershop::class)->withTrashed();
    }

    public function currentPlan(): BelongsTo
    {
        return $this->belongsTo(SaasPlan::class, 'current_plan_id');
    }

    public function requestedPlan(): BelongsTo
    {
        return $this->belongsTo(SaasPlan::class, 'requested_plan_id');
    }
}

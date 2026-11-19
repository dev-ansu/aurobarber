<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class LandingLead extends Model
{
    use HasFactory;

    protected $table = 'landing_leads';

    public function getTable(): string
    {
        return DB::connection()->getDriverName() === 'pgsql'
            ? 'public.landing_leads'
            : 'landing_leads';
    }

    protected $fillable = [
        'name',
        'barbershop_name',
        'email',
        'phone',
        'city',
        'chairs_count',
        'selected_plan',
        'selected_plan_id',
        'message',
        'status',
        'notes',
        'ip',
        'converted_barbershop_id',
    ];

    public function selectedSaasPlan(): BelongsTo
    {
        return $this->belongsTo(SaasPlan::class, 'selected_plan_id');
    }

    public function convertedBarbershop(): BelongsTo
    {
        return $this->belongsTo(Barbershop::class, 'converted_barbershop_id');
    }
}

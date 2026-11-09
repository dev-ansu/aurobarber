<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BarbershopClient extends Model
{
    use HasFactory;

    protected $fillable = [
        'barbershop_id',
        'name',
        'phone',
        'birth_date',
        'notes',
        'loyalty_stamps',
        'loyalty_rewards_redeemed',
        'total_visits',
        'total_spent',
        'last_visit_at',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'total_spent' => 'decimal:2',
        'last_visit_at' => 'datetime',
    ];

    public function barbershop(): BelongsTo
    {
        return $this->belongsTo(Barbershop::class);
    }

    public function daysSinceLastVisit(): ?int
    {
        if (!$this->last_visit_at) {
            return null;
        }

        return (int) $this->last_visit_at->startOfDay()->diffInDays(now()->startOfDay());
    }
}

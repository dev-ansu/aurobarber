<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommissionPayout extends Model
{
    use HasFactory;

    protected $fillable = [
        'barbershop_id',
        'professional_id',
        'period_start',
        'period_end',
        'appointments_count',
        'services_gross',
        'services_commission',
        'products_gross',
        'products_commission',
        'products_count',
        'total_gross',
        'total_commission',
        'barbershop_cut',
        'payment_method',
        'notes',
        'paid_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'services_gross' => 'decimal:2',
        'services_commission' => 'decimal:2',
        'products_gross' => 'decimal:2',
        'products_commission' => 'decimal:2',
        'products_count' => 'integer',
        'total_gross' => 'decimal:2',
        'total_commission' => 'decimal:2',
        'barbershop_cut' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function barbershop(): BelongsTo
    {
        return $this->belongsTo(Barbershop::class);
    }

    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function productSales(): HasMany
    {
        return $this->hasMany(ProductSale::class);
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return match ($this->payment_method) {
            'pix' => 'PIX',
            'dinheiro' => 'Dinheiro',
            'transferencia' => 'Transferência Bancária',
            default => ucfirst($this->payment_method),
        };
    }
}

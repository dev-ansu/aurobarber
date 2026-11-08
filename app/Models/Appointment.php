<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'barbershop_id',
        'professional_id',
        'service_id',
        'client_name',
        'client_phone',
        'client_email',
        'scheduled_at',
        'ends_at',
        'status',
        'service_price',
        'commission_rate',
        'commission_amount',
        'payment_method',
        'payment_status',
        'notes',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'ends_at' => 'datetime',
        'service_price' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:2',
    ];

    public function barbershop(): BelongsTo
    {
        return $this->belongsTo(Barbershop::class);
    }

    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'scheduled' => 'Agendado',
            'confirmed' => 'Confirmado',
            'in_progress' => 'Em Atendimento',
            'completed' => 'Concluído',
            'cancelled' => 'Cancelado',
            'no_show' => 'Não Compareceu',
            default => ucfirst($this->status),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'scheduled' => 'warning',
            'confirmed' => 'info',
            'in_progress' => 'primary',
            'completed' => 'success',
            'cancelled' => 'danger',
            'no_show' => 'gray',
            default => 'secondary',
        };
    }
}

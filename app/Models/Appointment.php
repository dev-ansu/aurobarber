<?php

namespace App\Models;

use App\Services\SensitiveClientData;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
        'is_blocked_slot',
        'service_price',
        'products_total',
        'commission_rate',
        'commission_amount',
        'payment_method',
        'payment_status',
        'paid_at',
        'commission_status',
        'commission_payout_id',
        'commission_paid_at',
        'notes',
        'rating',
        'rating_comment',
        'rated_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'ends_at' => 'datetime',
        'paid_at' => 'datetime',
        'commission_paid_at' => 'datetime',
        'rated_at' => 'datetime',
        'is_blocked_slot' => 'boolean',
        'service_price' => 'decimal:2',
        'products_total' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (Appointment $appointment): void {
            $plainPhone = $appointment->client_phone;
            $plainEmail = $appointment->client_email;
            $appointment->client_phone_hash = SensitiveClientData::phoneHash($plainPhone);
            $appointment->client_email_hash = SensitiveClientData::emailHash($plainEmail);

            if (! SensitiveClientData::isEncrypted($appointment->getRawOriginal('client_phone'))) {
                $appointment->client_phone = SensitiveClientData::encrypt($plainPhone);
            }

            if (! SensitiveClientData::isEncrypted($appointment->getRawOriginal('client_email'))) {
                $appointment->client_email = SensitiveClientData::encrypt($plainEmail);
            }
        });

        static::saved(function (Appointment $appointment) {
            if ($appointment->is_blocked_slot || empty($appointment->client_phone) || empty($appointment->barbershop_id)) {
                return;
            }

            $cleanPhone = preg_replace('/\D/', '', $appointment->client_phone);
            if (strlen($cleanPhone) < 8) {
                return;
            }

            $client = BarbershopClient::firstOrNew([
                'barbershop_id' => $appointment->barbershop_id,
                'phone_hash' => $appointment->client_phone_hash,
            ]);
            $client->phone = $cleanPhone;

            $client->name = $appointment->client_name ?: ($client->name ?: 'Cliente');

            // Recalcula métricas baseadas nos agendamentos concluídos
            $completedQuery = static::where('barbershop_id', $appointment->barbershop_id)
                ->where('is_blocked_slot', false)
                ->where('status', 'completed')
                ->where('client_phone_hash', $appointment->client_phone_hash);

            $completedCount = (int) $completedQuery->count();
            $totalServiceSpent = (float) $completedQuery->sum('service_price');
            $totalProductsSpent = (float) $completedQuery->sum('products_total');
            $lastVisit = $completedQuery->max('scheduled_at');

            $targetCuts = (int) ($appointment->barbershop?->loyalty_target_cuts ?: 10);
            $redeemed = (int) ($client->loyalty_rewards_redeemed ?? 0);
            $availableStamps = max(0, $completedCount - ($redeemed * $targetCuts));

            $client->total_visits = $completedCount;
            $client->total_spent = $totalServiceSpent + $totalProductsSpent;
            $client->loyalty_stamps = $availableStamps;
            if ($lastVisit) {
                $client->last_visit_at = $lastVisit;
            }
            $client->saveQuietly();
        });
    }

    public function getClientPhoneAttribute(?string $value): ?string
    {
        return SensitiveClientData::decrypt($value);
    }

    public function setClientPhoneAttribute(?string $value): void
    {
        $this->attributes['client_phone'] = SensitiveClientData::encrypt($value);
    }

    public function getClientEmailAttribute(?string $value): ?string
    {
        return SensitiveClientData::decrypt($value);
    }

    public function setClientEmailAttribute(?string $value): void
    {
        $this->attributes['client_email'] = SensitiveClientData::encrypt($value);
    }

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

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'appointment_services')
            ->withPivot('price', 'duration_minutes')
            ->withTimestamps();
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'appointment_products')
            ->withPivot('quantity', 'unit_price', 'total_price')
            ->withTimestamps();
    }

    public function getTotalAmountAttribute(): float
    {
        return (float) $this->service_price + (float) $this->products_total;
    }

    public function commissionPayout(): BelongsTo
    {
        return $this->belongsTo(CommissionPayout::class);
    }

    public function getServicesSummaryAttribute(): string
    {
        if ($this->relationLoaded('services') && $this->services->isNotEmpty()) {
            return $this->services->pluck('name')->join(' + ');
        }

        return $this->service?->name ?? 'Serviço';
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
            default => 'gray',
        };
    }
}

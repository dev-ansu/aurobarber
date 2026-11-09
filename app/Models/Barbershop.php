<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Barbershop extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'owner_id',
        'parent_id',
        'name',
        'slug',
        'headline',
        'description',
        'logo_url',
        'banner_url',
        'phone',
        'whatsapp',
        'address',
        'city',
        'instagram',
        'brand_color',
        'opening_hours',
        'pix_key',
        'subscription_status',
        'subscription_plan',
        'mercadopago_preapproval_id',
        'is_active',
        // AuroBarber product licensing
        'license_status',
        'license_expires_at',
        'grace_period_days',
        'monthly_price',
        'license_notes',
        'suspended_at',
        'suspension_reason',
        'loyalty_enabled',
        'loyalty_target_cuts',
        'return_reminder_days',
    ];

    protected $casts = [
        'opening_hours' => 'array',
        'is_active' => 'boolean',
        'loyalty_enabled' => 'boolean',
        'loyalty_target_cuts' => 'integer',
        'return_reminder_days' => 'integer',
        'license_expires_at' => 'datetime',
        'suspended_at' => 'datetime',
        'grace_period_days' => 'integer',
        'monthly_price' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (Barbershop $barbershop) {
            // Se a licença estiver ativa ou com data futura, garante que is_active seja true
            if ($barbershop->license_status === 'active' && ! (bool) $barbershop->is_active) {
                $barbershop->is_active = true;
                $barbershop->suspended_at = null;
                $barbershop->suspension_reason = null;
            }
        });
    }

    /**
     * Verifica se a barbearia está com a licença ativa e regular (ou em carência).
     */
    public function isLicenseActive(): bool
    {
        if (! (bool) $this->is_active) {
            return false;
        }

        if ($this->license_status === 'suspended' || $this->license_status === 'cancelled') {
            return false;
        }

        if ($this->license_expires_at === null) {
            return true;
        }

        $graceDays = $this->grace_period_days ?? 5;
        $deadlineWithGrace = $this->license_expires_at->copy()->addDays($graceDays)->endOfDay();

        return now()->lte($deadlineWithGrace);
    }

    /**
     * Verifica se a barbearia está no período de carência (vencida, mas dentro dos 5 dias de tolerância).
     */
    public function isInGracePeriod(): bool
    {
        if (! (bool) $this->is_active || $this->license_status === 'suspended') {
            return false;
        }

        if ($this->license_expires_at === null) {
            return false;
        }

        $graceDays = $this->grace_period_days ?? 5;
        $expiry = $this->license_expires_at->copy()->endOfDay();
        $deadlineWithGrace = $this->license_expires_at->copy()->addDays($graceDays)->endOfDay();

        return now()->gt($expiry) && now()->lte($deadlineWithGrace);
    }

    /**
     * Verifica se está explicitamente suspensa.
     */
    public function isSuspended(): bool
    {
        return ! (bool) $this->is_active || $this->license_status === 'suspended';
    }

    /**
     * Retorna a quantidade de dias restantes até o vencimento oficial (positivo = dias restantes, negativo = dias em atraso).
     */
    public function daysRemaining(): int
    {
        if (! $this->license_expires_at) {
            return 999;
        }

        return (int) now()->startOfDay()->diffInDays($this->license_expires_at->copy()->startOfDay(), false);
    }

    /**
     * Quantidade de dias restantes de carência antes do corte total.
     */
    public function graceDaysRemaining(): int
    {
        if (! $this->isInGracePeriod() || ! $this->license_expires_at) {
            return 0;
        }

        $graceDays = $this->grace_period_days ?? 5;
        $deadline = $this->license_expires_at->copy()->addDays($graceDays)->endOfDay();

        return (int) max(0, now()->diffInDays($deadline, false));
    }

    /**
     * Renova a licença por X dias (padrão: 30 dias).
     */
    public function renew(int $days = 30): void
    {
        $baseDate = ($this->license_expires_at && $this->license_expires_at->isFuture())
            ? $this->license_expires_at
            : now();

        $this->forceFill([
            'license_status' => 'active',
            'license_expires_at' => $baseDate->copy()->addDays($days),
            'is_active' => true,
            'suspended_at' => null,
            'suspension_reason' => null,
        ])->save();
    }

    /**
     * Suspende o acesso ao sistema da barbearia.
     */
    public function suspend(string $reason = 'Suspensão manual por pendência financeira'): void
    {
        $this->forceFill([
            'license_status' => 'suspended',
            'is_active' => false,
            'suspended_at' => now(),
            'suspension_reason' => $reason,
        ])->save();
    }

    /**
     * Reativa o acesso da barbearia.
     */
    public function activate(): void
    {
        $newExpiresAt = ($this->license_expires_at && $this->license_expires_at->isFuture())
            ? $this->license_expires_at
            : now()->addDays(30);

        $this->forceFill([
            'license_status' => 'active',
            'is_active' => true,
            'license_expires_at' => $newExpiresAt,
            'suspended_at' => null,
            'suspension_reason' => null,
        ])->save();
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Barbershop::class, 'parent_id');
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Barbershop::class, 'parent_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role', 'is_primary')
            ->withTimestamps();
    }

    public function professionals(): HasMany
    {
        return $this->hasMany(Professional::class)->orderBy('display_order');
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class)->orderBy('display_order');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function cashMovements(): HasMany
    {
        return $this->hasMany(CashMovement::class);
    }

    public function payables(): HasMany
    {
        return $this->hasMany(Payable::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class)->orderBy('name');
    }

    public function clients(): HasMany
    {
        return $this->hasMany(BarbershopClient::class)->orderByDesc('last_visit_at');
    }

    public function getFormattedWhatsappAttribute(): ?string
    {
        if (! $this->whatsapp) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $this->whatsapp);

        return str_starts_with($digits, '55') ? $digits : '55'.$digits;
    }
}

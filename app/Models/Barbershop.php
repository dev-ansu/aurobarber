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
        'schema_name',
        'custom_domain',
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
        'saas_plan_id',
        'license_expires_at',
        'grace_period_days',
        'monthly_price',
        'license_notes',
        'suspended_at',
        'suspension_reason',
        'loyalty_enabled',
        'loyalty_target_cuts',
        'return_reminder_days',
        'max_units',
        'has_unrestricted_access',
        'max_professionals',
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
        'max_units' => 'integer',
        'has_unrestricted_access' => 'boolean',
        'max_professionals' => 'integer',
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

    /**
     * Verifica se a licença já passou da data de vencimento oficial.
     */
    public function isLicenseExpired(): bool
    {
        if ($this->license_expires_at === null) {
            return false;
        }

        return now()->gt($this->license_expires_at->copy()->endOfDay());
    }

    /**
     * Retorna o valor de mensalidade efetivo da barbearia (preço customizado ou preço do plano).
     */
    public function effectiveMonthlyPrice(): float
    {
        // Filiais não pagam mensalidade individual — o faturamento é centralizado na Matriz
        if ($this->isBranch()) {
            return (float) ($this->monthly_price ?? 0.0);
        }

        if ((float) $this->monthly_price > 0) {
            return (float) $this->monthly_price;
        }

        if ($this->saasPlan && (float) $this->saasPlan->monthly_price > 0) {
            return (float) $this->saasPlan->monthly_price;
        }

        return 89.90;
    }

    /**
     * Informa se este registro é uma filial/unidade de outra barbearia.
     */
    public function isBranch(): bool
    {
        return ! is_null($this->parent_id);
    }

    /**
     * Alias semântico em português para isBranch().
     */
    public function isFilial(): bool
    {
        return $this->isBranch();
    }

    /**
     * Informa se este registro é uma barbearia matriz / autônoma.
     */
    public function isMatriz(): bool
    {
        return is_null($this->parent_id);
    }

    /**
     * Informa se a barbearia possui filiais cadastradas.
     */
    public function hasBranches(): bool
    {
        return $this->relationLoaded('branches')
            ? $this->branches->isNotEmpty()
            : $this->branches()->exists();
    }

    /**
     * Scope para filtrar apenas barbearias matrizes (independentes).
     */
    public function scopeMatrizes($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope para filtrar apenas filiais.
     */
    public function scopeOnlyBranches($query)
    {
        return $query->whereNotNull('parent_id');
    }

    /**
     * Scope para filtrar apenas filiais (alias em português).
     */
    public function scopeFiliais($query)
    {
        return $query->whereNotNull('parent_id');
    }

    /**
     * Retorna a quantidade de dias padrão para renovação desta barbearia.
     */
    public function effectiveRenewalDays(): int
    {
        $days = (int) ($this->saasPlan?->renewal_days ?? 30);

        return $days > 0 ? $days : 30;
    }

    /**
     * Retorna a barbearia matriz (ou ela mesma se já for a matriz).
     */
    public function getPrimaryShop(): self
    {
        if ($this->parent_id && $this->relationLoaded('parent') && $this->parent) {
            return $this->parent;
        }

        if ($this->parent_id) {
            return $this->parent()->first() ?? $this;
        }

        return $this;
    }

    /**
     * Informa se a barbearia possui acesso irrestrito (Cliente VIP / pioneiro, ex: Barbearia Marinho).
     */
    public function hasUnrestrictedAccess(): bool
    {
        if ((bool) $this->has_unrestricted_access) {
            return true;
        }

        if ($this->parent_id) {
            return $this->getPrimaryShop()->hasUnrestrictedAccess();
        }

        return false;
    }

    /**
     * Retorna a quantidade máxima de unidades permitidas para esta barbearia.
     * Retorna PHP_INT_MAX se possuir acesso irrestrito.
     */
    public function getAllowedUnits(): int
    {
        if ($this->hasUnrestrictedAccess()) {
            return PHP_INT_MAX;
        }

        $primary = $this->getPrimaryShop();

        // 1. Override manual definido diretamente na barbearia
        if ($primary->max_units !== null && (int) $primary->max_units > 0) {
            return (int) $primary->max_units;
        }

        // 2. Cota definida pelo plano contratado
        if ($primary->saasPlan && $primary->saasPlan->max_units !== null && (int) $primary->saasPlan->max_units > 0) {
            return (int) $primary->saasPlan->max_units;
        }

        return 1;
    }

    /**
     * Retorna a quantidade atual de unidades vinculadas à matriz (matriz + filiais).
     */
    public function getCurrentUnitsCount(): int
    {
        $primary = $this->getPrimaryShop();

        return 1 + $primary->branches()->count();
    }

    /**
     * Informa se a barbearia pode adicionar uma nova filial/unidade.
     */
    public function canAddBranch(): bool
    {
        if ($this->hasUnrestrictedAccess()) {
            return true;
        }

        $primary = $this->getPrimaryShop();

        return $primary->getCurrentUnitsCount() < $primary->getAllowedUnits();
    }

    /**
     * Retorna a quantidade restante de unidades que ainda podem ser cadastradas.
     */
    public function getRemainingUnits(): int
    {
        if ($this->hasUnrestrictedAccess()) {
            return 9999;
        }

        $primary = $this->getPrimaryShop();

        return max(0, $primary->getAllowedUnits() - $primary->getCurrentUnitsCount());
    }

    /**
     * Quantidade máxima permitida de profissionais ativos (null = ilimitado).
     */
    public function getAllowedProfessionals(): ?int
    {
        if ($this->hasUnrestrictedAccess()) {
            return null;
        }

        return $this->max_professionals !== null ? (int) $this->max_professionals : null;
    }

    /**
     * Informa se a barbearia pode cadastrar mais um profissional.
     */
    public function canAddProfessional(): bool
    {
        if ($this->hasUnrestrictedAccess()) {
            return true;
        }

        $allowed = $this->getAllowedProfessionals();
        if ($allowed === null) {
            return true;
        }

        return $this->professionals()->count() < $allowed;
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function saasPlan(): BelongsTo
    {
        return $this->belongsTo(SaasPlan::class, 'saas_plan_id');
    }

    public function paymentSubmissions(): HasMany
    {
        return $this->hasMany(SaasPaymentSubmission::class, 'barbershop_id');
    }

    public function paymentReceipts(): HasMany
    {
        return $this->hasMany(SaasPaymentReceipt::class, 'barbershop_id');
    }

    public function planChangeRequests(): HasMany
    {
        return $this->hasMany(SaasPlanChangeRequest::class, 'barbershop_id');
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

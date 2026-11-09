<?php

namespace App\Models;

use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthentication;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\Email\Concerns\InteractsWithEmailAuthentication;
use Filament\Auth\MultiFactor\Email\Contracts\HasEmailAuthentication;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasTenants, HasAppAuthentication, HasAppAuthenticationRecovery, HasEmailAuthentication
{
    use HasFactory, HasRoles, Notifiable;
    use InteractsWithAppAuthentication;
    use InteractsWithAppAuthenticationRecovery;
    use InteractsWithEmailAuthentication;

    protected $fillable = [
        'barbershop_id',
        'name',
        'email',
        'phone',
        'password',
        'role', // master_admin, owner, barber, receptionist
        'avatar_url',
        'is_active',
        'must_change_password',
        'password_changed_at',
        'app_authentication_secret',
        'app_authentication_recovery_codes',
        'has_email_authentication',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'must_change_password' => 'boolean',
        'password_changed_at' => 'datetime',
    ];

    public function barbershop(): BelongsTo
    {
        return $this->belongsTo(Barbershop::class);
    }

    public function barbershops(): BelongsToMany
    {
        return $this->belongsToMany(Barbershop::class)
            ->withPivot('role', 'is_primary')
            ->withTimestamps();
    }

    public function ownedBarbershops(): HasMany
    {
        return $this->hasMany(Barbershop::class, 'owner_id');
    }

    public function professional(): HasOne
    {
        return $this->hasOne(Professional::class);
    }

    public function getTenants(Panel $panel): array|Collection
    {
        if ($this->role === 'master_admin') {
            return Barbershop::where('is_active', true)->get();
        }

        return $this->barbershops()->where('is_active', true)->get();
    }

    public function canAccessTenant(Model $tenant): bool
    {
        if ($this->role === 'master_admin') {
            return true;
        }

        return $this->barbershops()->where('barbershops.id', $tenant->id)->exists()
            || $this->ownedBarbershops()->where('id', $tenant->id)->exists()
            || $this->barbershop_id === $tenant->id;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if (!$this->is_active) {
            return false;
        }

        // Master admin tem acesso irrestrito ao SaaS Master e pode inspecionar unidades
        if ($this->role === 'master_admin') {
            return true;
        }

        // Usuários de barbearias não acessam o SaaS Master
        if ($panel->getId() === 'saas') {
            return false;
        }

        // Painel admin é acessível por qualquer membro vinculado à barbearia
        if ($panel->getId() === 'admin') {
            return in_array($this->role, ['owner', 'barber', 'receptionist'])
                || (method_exists($this, 'hasRole') && ($this->hasRole('super_admin') || $this->roles()->exists()))
                || !empty($this->barbershop_id)
                || $this->barbershops()->exists();
        }

        return false;
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner' || $this->role === 'master_admin';
    }

    public function isBarber(): bool
    {
        return $this->role === 'barber';
    }

    /**
     * Identifica se o usuário logado é um barbeiro com visão restrita (não é dono, nem gerente, nem super_admin).
     */
    public function isRestrictedBarber(): bool
    {
        if (in_array($this->role, ['master_admin', 'owner'], true)) {
            return false;
        }

        if (method_exists($this, 'hasAnyRole') && $this->hasAnyRole(['super_admin', 'barbershop_owner', 'barbershop_manager'])) {
            return false;
        }

        return $this->role === 'barber'
            || (method_exists($this, 'hasRole') && $this->hasRole('barber'))
            || $this->professional()->exists();
    }

    /**
     * Resolve o ID do perfil Professional correspondente a este usuário na barbearia atual.
     */
    public function resolveProfessionalId(?int $barbershopId = null): ?int
    {
        $barbershopId ??= filament()->getTenant()?->id ?? $this->barbershop_id;

        $direct = Professional::where('user_id', $this->id)
            ->when($barbershopId, fn ($q) => $q->where('barbershop_id', $barbershopId))
            ->first();

        if ($direct) {
            return (int) $direct->id;
        }

        if ($barbershopId) {
            $matched = Professional::where('barbershop_id', $barbershopId)
                ->whereNull('user_id')
                ->where(function ($q) {
                    $q->whereRaw('LOWER(name) = ?', [mb_strtolower($this->name)]);
                    if (!empty($this->phone)) {
                        $q->orWhere('phone', $this->phone);
                    }
                })
                ->first();

            if ($matched) {
                $matched->updateQuietly(['user_id' => $this->id]);
                return (int) $matched->id;
            }
        }

        return null;
    }

    public function isCollaborator(): bool
    {
        return false;
    }

    public function hasDefinedRole(): bool
    {
        return !empty($this->role);
    }
}

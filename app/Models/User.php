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
        return $this->barbershops()->where('is_active', true)->get();
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->barbershops()->where('barbershops.id', $tenant->id)->exists();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if (!$this->is_active) {
            return false;
        }

        // Master admin acessa exclusivamente o painel SaaS Master
        if ($this->role === 'master_admin' || (method_exists($this, 'hasRole') && $this->hasRole('super_admin'))) {
            if ($panel->getId() === 'saas') {
                return true;
            }

            // No painel admin das barbearias, só entra se for explicitamente membro da unidade
            return $this->barbershops()->exists();
        }

        // Painel admin é restrito à equipe da barbearia (dono, barbeiro, recepcionista)
        if ($panel->getId() === 'admin') {
            return in_array($this->role, ['owner', 'barber', 'receptionist']);
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
}

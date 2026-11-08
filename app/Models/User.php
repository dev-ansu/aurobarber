<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
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

class User extends Authenticatable implements FilamentUser, HasTenants
{
    use HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'barbershop_id',
        'name',
        'email',
        'phone',
        'password',
        'role', // master_admin, owner, barber, receptionist
        'avatar_url',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
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

        return $this->barbershops()->where('barbershops.id', $tenant->id)->exists();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if (!$this->is_active) {
            return false;
        }

        // Master admin can access everything
        if ($this->role === 'master_admin' || (method_exists($this, 'hasRole') && $this->hasRole('super_admin'))) {
            return true;
        }

        // Admin panel for barbershop
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

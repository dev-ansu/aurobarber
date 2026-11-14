<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Payable;
use App\Models\User;
use Filament\Facades\Filament;

class PayablePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canManageCurrentBarbershop($user);
    }

    public function view(User $user, Payable $payable): bool
    {
        return $this->canManageCurrentBarbershop($user, $payable);
    }

    public function create(User $user): bool
    {
        return $this->canManageCurrentBarbershop($user);
    }

    public function update(User $user, Payable $payable): bool
    {
        return $payable->status === 'pending'
            && $this->canManageCurrentBarbershop($user, $payable);
    }

    public function delete(User $user, Payable $payable): bool
    {
        return $payable->status === 'pending'
            && $this->canManageCurrentBarbershop($user, $payable);
    }

    private function canManageCurrentBarbershop(User $user, ?Payable $payable = null): bool
    {
        $tenant = Filament::getTenant();

        if (! $tenant || $user->isRestrictedBarber() || ! $user->canAccessTenant($tenant)) {
            return false;
        }

        return $payable === null || (int) $payable->barbershop_id === (int) $tenant->getKey();
    }
}

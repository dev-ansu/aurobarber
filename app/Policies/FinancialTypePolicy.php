<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\FinancialType;
use App\Models\User;
use Filament\Facades\Filament;

class FinancialTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canAccessCurrentBarbershop($user);
    }

    public function view(User $user, FinancialType $financialType): bool
    {
        return $this->canAccessCurrentBarbershop($user, $financialType);
    }

    public function create(User $user): bool
    {
        return $this->canAccessCurrentBarbershop($user);
    }

    public function update(User $user, FinancialType $financialType): bool
    {
        $tenant = Filament::getTenant();
        if (! $this->canAccessCurrentBarbershop($user, $financialType)) {
            return false;
        }

        // Permite editar se for da própria barbearia ou se for superadmin
        return $financialType->barbershop_id === null || (int) $financialType->barbershop_id === (int) $tenant?->getKey();
    }

    public function delete(User $user, FinancialType $financialType): bool
    {
        $tenant = Filament::getTenant();
        if (! $this->canAccessCurrentBarbershop($user, $financialType)) {
            return false;
        }

        // Não permite deletar contas padrão globais do sistema sem barbearia
        if ($financialType->barbershop_id === null) {
            return false;
        }

        // Não permite deletar se tiver contas filhas ou despesas vinculadas
        if ($financialType->children()->exists() || $financialType->payables()->exists()) {
            return false;
        }

        return (int) $financialType->barbershop_id === (int) $tenant?->getKey();
    }

    private function canAccessCurrentBarbershop(User $user, ?FinancialType $financialType = null): bool
    {
        $tenant = Filament::getTenant();

        if (! $tenant || $user->isRestrictedBarber() || ! $user->canAccessTenant($tenant)) {
            return false;
        }

        return $financialType === null
            || $financialType->barbershop_id === null
            || (int) $financialType->barbershop_id === (int) $tenant->getKey();
    }
}

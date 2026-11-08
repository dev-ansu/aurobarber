<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\CashRegister;
use Illuminate\Auth\Access\HandlesAuthorization;

class CashRegisterPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CashRegister');
    }

    public function view(AuthUser $authUser, CashRegister $cashRegister): bool
    {
        return $authUser->can('View:CashRegister');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CashRegister');
    }

    public function update(AuthUser $authUser, CashRegister $cashRegister): bool
    {
        return $authUser->can('Update:CashRegister');
    }

    public function delete(AuthUser $authUser, CashRegister $cashRegister): bool
    {
        return $authUser->can('Delete:CashRegister');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:CashRegister');
    }

    public function restore(AuthUser $authUser, CashRegister $cashRegister): bool
    {
        return $authUser->can('Restore:CashRegister');
    }

    public function forceDelete(AuthUser $authUser, CashRegister $cashRegister): bool
    {
        return $authUser->can('ForceDelete:CashRegister');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CashRegister');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CashRegister');
    }

    public function replicate(AuthUser $authUser, CashRegister $cashRegister): bool
    {
        return $authUser->can('Replicate:CashRegister');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CashRegister');
    }

}
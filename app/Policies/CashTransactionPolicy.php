<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\CashTransaction;
use Illuminate\Auth\Access\HandlesAuthorization;

class CashTransactionPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CashTransaction');
    }

    public function view(AuthUser $authUser, CashTransaction $cashTransaction): bool
    {
        return $authUser->can('View:CashTransaction');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CashTransaction');
    }

    public function update(AuthUser $authUser, CashTransaction $cashTransaction): bool
    {
        return $authUser->can('Update:CashTransaction');
    }

    public function delete(AuthUser $authUser, CashTransaction $cashTransaction): bool
    {
        return $authUser->can('Delete:CashTransaction');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:CashTransaction');
    }

    public function restore(AuthUser $authUser, CashTransaction $cashTransaction): bool
    {
        return $authUser->can('Restore:CashTransaction');
    }

    public function forceDelete(AuthUser $authUser, CashTransaction $cashTransaction): bool
    {
        return $authUser->can('ForceDelete:CashTransaction');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CashTransaction');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CashTransaction');
    }

    public function replicate(AuthUser $authUser, CashTransaction $cashTransaction): bool
    {
        return $authUser->can('Replicate:CashTransaction');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CashTransaction');
    }

}
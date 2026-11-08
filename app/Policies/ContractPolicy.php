<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Contract;
use Illuminate\Auth\Access\HandlesAuthorization;

class ContractPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Contract')
            || (method_exists($authUser, 'canManageLmsOnboarding') && $authUser->canManageLmsOnboarding());
    }

    public function view(AuthUser $authUser, Contract $contract): bool
    {
        return $authUser->can('View:Contract')
            || (method_exists($authUser, 'canManageLmsOnboarding') && $authUser->canManageLmsOnboarding());
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Contract')
            || (method_exists($authUser, 'canManageLmsOnboarding') && $authUser->canManageLmsOnboarding());
    }

    public function update(AuthUser $authUser, Contract $contract): bool
    {
        return $authUser->can('Update:Contract');
    }

    public function adjustFinancial(AuthUser $authUser, Contract $contract): bool
    {
        return $authUser->can('Update:Contract') || $authUser->can('AdjustFinancial:Contract');
    }

    public function delete(AuthUser $authUser, Contract $contract): bool
    {
        return $authUser->can('Delete:Contract');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Contract');
    }

    public function reactivate(AuthUser $authUser, Contract $contract): bool
    {
        return $authUser->can('Reactivate:Contract') || (method_exists($authUser, 'hasRole') && $authUser->hasRole('super_admin'));
    }

    public function restore(AuthUser $authUser, Contract $contract): bool
    {
        return $authUser->can('Reactivate:Contract') || $authUser->can('Restore:Contract') || (method_exists($authUser, 'hasRole') && $authUser->hasRole('super_admin'));
    }

    public function forceDelete(AuthUser $authUser, Contract $contract): bool
    {
        return $authUser->can('ForceDelete:Contract');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Contract');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('Reactivate:Contract') || $authUser->can('RestoreAny:Contract') || (method_exists($authUser, 'hasRole') && $authUser->hasRole('super_admin'));
    }

    public function replicate(AuthUser $authUser, Contract $contract): bool
    {
        return $authUser->can('Replicate:Contract');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Contract');
    }

    public function changeCommercialLead(AuthUser $authUser, Contract $contract): bool
    {
        return $authUser->can('ChangeCommercialLead:Contract') || (method_exists($authUser, 'hasRole') && $authUser->hasRole('super_admin'));
    }

    public function changeCollaborator(AuthUser $authUser, Contract $contract): bool
    {
        return $authUser->can('ChangeCollaborator:Contract') || (method_exists($authUser, 'hasRole') && $authUser->hasRole('super_admin'));
    }
}
<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ContractHistoryType;
use Illuminate\Auth\Access\HandlesAuthorization;

class ContractHistoryTypePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ContractHistoryType');
    }

    public function view(AuthUser $authUser, ContractHistoryType $contractHistoryType): bool
    {
        return $authUser->can('View:ContractHistoryType');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ContractHistoryType');
    }

    public function update(AuthUser $authUser, ContractHistoryType $contractHistoryType): bool
    {
        return $authUser->can('Update:ContractHistoryType');
    }

    public function delete(AuthUser $authUser, ContractHistoryType $contractHistoryType): bool
    {
        return $authUser->can('Delete:ContractHistoryType');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ContractHistoryType');
    }

    public function restore(AuthUser $authUser, ContractHistoryType $contractHistoryType): bool
    {
        return $authUser->can('Restore:ContractHistoryType');
    }

    public function forceDelete(AuthUser $authUser, ContractHistoryType $contractHistoryType): bool
    {
        return $authUser->can('ForceDelete:ContractHistoryType');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ContractHistoryType');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ContractHistoryType');
    }

    public function replicate(AuthUser $authUser, ContractHistoryType $contractHistoryType): bool
    {
        return $authUser->can('Replicate:ContractHistoryType');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ContractHistoryType');
    }

}
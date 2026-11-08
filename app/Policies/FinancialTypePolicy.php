<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\FinancialType;
use Illuminate\Auth\Access\HandlesAuthorization;

class FinancialTypePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:FinancialType');
    }

    public function view(AuthUser $authUser, FinancialType $financialType): bool
    {
        return $authUser->can('View:FinancialType');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:FinancialType');
    }

    public function update(AuthUser $authUser, FinancialType $financialType): bool
    {
        return $authUser->can('Update:FinancialType');
    }

    public function delete(AuthUser $authUser, FinancialType $financialType): bool
    {
        return $authUser->can('Delete:FinancialType');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:FinancialType');
    }

    public function restore(AuthUser $authUser, FinancialType $financialType): bool
    {
        return $authUser->can('Restore:FinancialType');
    }

    public function forceDelete(AuthUser $authUser, FinancialType $financialType): bool
    {
        return $authUser->can('ForceDelete:FinancialType');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:FinancialType');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:FinancialType');
    }

    public function replicate(AuthUser $authUser, FinancialType $financialType): bool
    {
        return $authUser->can('Replicate:FinancialType');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:FinancialType');
    }

}
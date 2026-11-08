<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Responsible;
use Illuminate\Auth\Access\HandlesAuthorization;

class ResponsiblePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Responsible')
            || (method_exists($authUser, 'canManageLmsOnboarding') && $authUser->canManageLmsOnboarding());
    }

    public function view(AuthUser $authUser, Responsible $responsible): bool
    {
        return $authUser->can('View:Responsible')
            || (method_exists($authUser, 'canManageLmsOnboarding') && $authUser->canManageLmsOnboarding());
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Responsible')
            || (method_exists($authUser, 'canManageLmsOnboarding') && $authUser->canManageLmsOnboarding());
    }

    public function update(AuthUser $authUser, Responsible $responsible): bool
    {
        return $authUser->can('Update:Responsible')
            || (method_exists($authUser, 'canManageLmsOnboarding') && $authUser->canManageLmsOnboarding());
    }

    public function delete(AuthUser $authUser, Responsible $responsible): bool
    {
        return $authUser->can('Delete:Responsible');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Responsible');
    }

    public function restore(AuthUser $authUser, Responsible $responsible): bool
    {
        return $authUser->can('Restore:Responsible');
    }

    public function forceDelete(AuthUser $authUser, Responsible $responsible): bool
    {
        return $authUser->can('ForceDelete:Responsible');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Responsible');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Responsible');
    }

    public function replicate(AuthUser $authUser, Responsible $responsible): bool
    {
        return $authUser->can('Replicate:Responsible');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Responsible');
    }

}
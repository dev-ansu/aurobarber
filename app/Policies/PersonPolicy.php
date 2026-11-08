<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Person;
use Illuminate\Auth\Access\HandlesAuthorization;

class PersonPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Person')
            || (method_exists($authUser, 'canManageLmsOnboarding') && $authUser->canManageLmsOnboarding());
    }

    public function view(AuthUser $authUser, Person $person): bool
    {
        return $authUser->can('View:Person')
            || (method_exists($authUser, 'canManageLmsOnboarding') && $authUser->canManageLmsOnboarding());
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Person')
            || (method_exists($authUser, 'canManageLmsOnboarding') && $authUser->canManageLmsOnboarding());
    }

    public function update(AuthUser $authUser, Person $person): bool
    {
        return $authUser->can('Update:Person')
            || (method_exists($authUser, 'canManageLmsOnboarding') && $authUser->canManageLmsOnboarding());
    }

    public function delete(AuthUser $authUser, Person $person): bool
    {
        return $authUser->can('Delete:Person');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Person');
    }

    public function restore(AuthUser $authUser, Person $person): bool
    {
        return $authUser->can('Restore:Person');
    }

    public function forceDelete(AuthUser $authUser, Person $person): bool
    {
        return $authUser->can('ForceDelete:Person');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Person');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Person');
    }

    public function replicate(AuthUser $authUser, Person $person): bool
    {
        return $authUser->can('Replicate:Person');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Person');
    }

}
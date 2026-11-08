<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Collaborator;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class CollaboratorPolicy
{
    use HandlesAuthorization;

    public function before(AuthUser $authUser, string $ability): ?bool
    {
        if ($authUser->hasRole('super_admin')) {
            return true;
        }

        return null;
    }

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Collaborator');
    }

    public function view(AuthUser $authUser, Collaborator $collaborator): bool
    {
        return $authUser->can('View:Collaborator');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Collaborator');
    }

    public function update(AuthUser $authUser, Collaborator $collaborator): bool
    {
        return $authUser->can('Update:Collaborator');
    }

    public function delete(AuthUser $authUser, Collaborator $collaborator): bool
    {
        return $authUser->can('Delete:Collaborator');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Collaborator');
    }

    public function restore(AuthUser $authUser, Collaborator $collaborator): bool
    {
        return $authUser->can('Restore:Collaborator');
    }

    public function forceDelete(AuthUser $authUser, Collaborator $collaborator): bool
    {
        return $authUser->can('ForceDelete:Collaborator');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Collaborator');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Collaborator');
    }

    public function replicate(AuthUser $authUser, Collaborator $collaborator): bool
    {
        return $authUser->can('Replicate:Collaborator');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Collaborator');
    }
}

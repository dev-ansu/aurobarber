<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Professional;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProfessionalPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Professional');
    }

    public function view(AuthUser $authUser, Professional $professional): bool
    {
        return $authUser->can('View:Professional');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Professional');
    }

    public function update(AuthUser $authUser, Professional $professional): bool
    {
        return $authUser->can('Update:Professional');
    }

    public function delete(AuthUser $authUser, Professional $professional): bool
    {
        return $authUser->can('Delete:Professional');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Professional');
    }

    public function restore(AuthUser $authUser, Professional $professional): bool
    {
        return $authUser->can('Restore:Professional');
    }

    public function forceDelete(AuthUser $authUser, Professional $professional): bool
    {
        return $authUser->can('ForceDelete:Professional');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Professional');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Professional');
    }

    public function replicate(AuthUser $authUser, Professional $professional): bool
    {
        return $authUser->can('Replicate:Professional');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Professional');
    }

}
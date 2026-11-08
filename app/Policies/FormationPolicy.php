<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Formation;
use Illuminate\Auth\Access\HandlesAuthorization;

class FormationPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Formation')
            || (method_exists($authUser, 'canManageLmsOnboarding') && $authUser->canManageLmsOnboarding());
    }

    public function view(AuthUser $authUser, Formation $formation): bool
    {
        return $authUser->can('View:Formation')
            || (method_exists($authUser, 'canManageLmsOnboarding') && $authUser->canManageLmsOnboarding());
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Formation');
    }

    public function update(AuthUser $authUser, Formation $formation): bool
    {
        return $authUser->can('Update:Formation');
    }

    public function delete(AuthUser $authUser, Formation $formation): bool
    {
        return $authUser->can('Delete:Formation');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Formation');
    }

    public function restore(AuthUser $authUser, Formation $formation): bool
    {
        return $authUser->can('Restore:Formation');
    }

    public function forceDelete(AuthUser $authUser, Formation $formation): bool
    {
        return $authUser->can('ForceDelete:Formation');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Formation');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Formation');
    }

    public function replicate(AuthUser $authUser, Formation $formation): bool
    {
        return $authUser->can('Replicate:Formation');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Formation');
    }

    public function toggleStatus(AuthUser $authUser, Formation $formation): bool
    {
        return $authUser->can('ToggleStatus:Formation');
    }

}
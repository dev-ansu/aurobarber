<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SchoolGoal;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class SchoolGoalPolicy
{
    use HandlesAuthorization;

    public function before(AuthUser $authUser, string $ability): ?bool
    {
        if (method_exists($authUser, 'hasRole') && $authUser->hasRole('super_admin')) {
            return true;
        }

        return null;
    }

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SchoolGoal');
    }

    public function view(AuthUser $authUser, SchoolGoal $schoolGoal): bool
    {
        return $authUser->can('View:SchoolGoal');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SchoolGoal');
    }

    public function update(AuthUser $authUser, SchoolGoal $schoolGoal): bool
    {
        return $authUser->can('Update:SchoolGoal');
    }

    public function delete(AuthUser $authUser, SchoolGoal $schoolGoal): bool
    {
        return $authUser->can('Delete:SchoolGoal');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:SchoolGoal');
    }

    public function restore(AuthUser $authUser, SchoolGoal $schoolGoal): bool
    {
        return $authUser->can('Restore:SchoolGoal');
    }

    public function forceDelete(AuthUser $authUser, SchoolGoal $schoolGoal): bool
    {
        return $authUser->can('ForceDelete:SchoolGoal');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SchoolGoal');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SchoolGoal');
    }

    public function replicate(AuthUser $authUser, SchoolGoal $schoolGoal): bool
    {
        return $authUser->can('Replicate:SchoolGoal');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SchoolGoal');
    }
}

<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\StudentSchedule;
use Illuminate\Auth\Access\HandlesAuthorization;

class StudentSchedulePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:StudentSchedule');
    }

    public function view(AuthUser $authUser, StudentSchedule $studentSchedule): bool
    {
        return $authUser->can('View:StudentSchedule');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:StudentSchedule');
    }

    public function update(AuthUser $authUser, StudentSchedule $studentSchedule): bool
    {
        return $authUser->can('Update:StudentSchedule');
    }

    public function delete(AuthUser $authUser, StudentSchedule $studentSchedule): bool
    {
        return $authUser->can('Delete:StudentSchedule');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:StudentSchedule');
    }

    public function restore(AuthUser $authUser, StudentSchedule $studentSchedule): bool
    {
        return $authUser->can('Restore:StudentSchedule');
    }

    public function forceDelete(AuthUser $authUser, StudentSchedule $studentSchedule): bool
    {
        return $authUser->can('ForceDelete:StudentSchedule');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:StudentSchedule');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:StudentSchedule');
    }

    public function replicate(AuthUser $authUser, StudentSchedule $studentSchedule): bool
    {
        return $authUser->can('Replicate:StudentSchedule');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:StudentSchedule');
    }

}
<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\StudentAttendance;
use Illuminate\Auth\Access\HandlesAuthorization;

class StudentAttendancePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:StudentAttendance');
    }

    public function view(AuthUser $authUser, StudentAttendance $studentAttendance): bool
    {
        return $authUser->can('View:StudentAttendance');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:StudentAttendance');
    }

    public function update(AuthUser $authUser, StudentAttendance $studentAttendance): bool
    {
        return $authUser->can('Update:StudentAttendance');
    }

    public function delete(AuthUser $authUser, StudentAttendance $studentAttendance): bool
    {
        return $authUser->can('Delete:StudentAttendance');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:StudentAttendance');
    }

    public function restore(AuthUser $authUser, StudentAttendance $studentAttendance): bool
    {
        return $authUser->can('Restore:StudentAttendance');
    }

    public function forceDelete(AuthUser $authUser, StudentAttendance $studentAttendance): bool
    {
        return $authUser->can('ForceDelete:StudentAttendance');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:StudentAttendance');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:StudentAttendance');
    }

    public function replicate(AuthUser $authUser, StudentAttendance $studentAttendance): bool
    {
        return $authUser->can('Replicate:StudentAttendance');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:StudentAttendance');
    }

    public function changeStatus(AuthUser $authUser, StudentAttendance $studentAttendance): bool
    {
        return $authUser->can('ChangeStatus:StudentAttendance');
    }
}

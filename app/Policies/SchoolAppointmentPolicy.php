<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SchoolAppointment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SchoolAppointmentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasRole('super_admin') || $user->can('ViewAny:SchoolAppointment');
    }

    public function view(User $user, SchoolAppointment $schoolAppointment): bool
    {
        return $user->hasRole('super_admin')
            || $user->can('View:SchoolAppointment')
            || $schoolAppointment->is_shared_with_all
            || $schoolAppointment->assigned_to_user_id === $user->id
            || $schoolAppointment->created_by_user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super_admin') || $user->can('Create:SchoolAppointment');
    }

    public function update(User $user, SchoolAppointment $schoolAppointment): bool
    {
        return $user->hasRole('super_admin')
            || $user->can('Update:SchoolAppointment')
            || $schoolAppointment->assigned_to_user_id === $user->id
            || $schoolAppointment->created_by_user_id === $user->id;
    }

    public function delete(User $user, SchoolAppointment $schoolAppointment): bool
    {
        return $user->hasRole('super_admin')
            || $user->can('Delete:SchoolAppointment')
            || $schoolAppointment->created_by_user_id === $user->id;
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasRole('super_admin') || $user->can('Delete:SchoolAppointment');
    }
}

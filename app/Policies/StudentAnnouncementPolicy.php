<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\StudentAnnouncement;
use Illuminate\Auth\Access\HandlesAuthorization;

class StudentAnnouncementPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:StudentAnnouncement');
    }

    public function view(AuthUser $authUser, StudentAnnouncement $studentAnnouncement): bool
    {
        return $authUser->can('View:StudentAnnouncement');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:StudentAnnouncement');
    }

    public function update(AuthUser $authUser, StudentAnnouncement $studentAnnouncement): bool
    {
        return $authUser->can('Update:StudentAnnouncement');
    }

    public function delete(AuthUser $authUser, StudentAnnouncement $studentAnnouncement): bool
    {
        return $authUser->can('Delete:StudentAnnouncement');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:StudentAnnouncement');
    }

    public function restore(AuthUser $authUser, StudentAnnouncement $studentAnnouncement): bool
    {
        return $authUser->can('Restore:StudentAnnouncement');
    }

    public function forceDelete(AuthUser $authUser, StudentAnnouncement $studentAnnouncement): bool
    {
        return $authUser->can('ForceDelete:StudentAnnouncement');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:StudentAnnouncement');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:StudentAnnouncement');
    }

    public function replicate(AuthUser $authUser, StudentAnnouncement $studentAnnouncement): bool
    {
        return $authUser->can('Replicate:StudentAnnouncement');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:StudentAnnouncement');
    }

}
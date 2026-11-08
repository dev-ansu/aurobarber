<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\CommercialVisit;
use Illuminate\Auth\Access\HandlesAuthorization;

class CommercialVisitPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CommercialVisit');
    }

    public function view(AuthUser $authUser, CommercialVisit $commercialVisit): bool
    {
        return $authUser->can('View:CommercialVisit');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CommercialVisit');
    }

    public function update(AuthUser $authUser, CommercialVisit $commercialVisit): bool
    {
        return $authUser->can('Update:CommercialVisit');
    }

    public function delete(AuthUser $authUser, CommercialVisit $commercialVisit): bool
    {
        return $authUser->can('Delete:CommercialVisit');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:CommercialVisit');
    }

    public function restore(AuthUser $authUser, CommercialVisit $commercialVisit): bool
    {
        return $authUser->can('Restore:CommercialVisit');
    }

    public function forceDelete(AuthUser $authUser, CommercialVisit $commercialVisit): bool
    {
        return $authUser->can('ForceDelete:CommercialVisit');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CommercialVisit');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CommercialVisit');
    }

    public function replicate(AuthUser $authUser, CommercialVisit $commercialVisit): bool
    {
        return $authUser->can('Replicate:CommercialVisit');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CommercialVisit');
    }

}
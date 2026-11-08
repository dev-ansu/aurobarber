<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\CommercialLead;
use Illuminate\Auth\Access\HandlesAuthorization;

class CommercialLeadPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CommercialLead');
    }

    public function view(AuthUser $authUser, CommercialLead $commercialLead): bool
    {
        return $authUser->can('View:CommercialLead');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CommercialLead');
    }

    public function update(AuthUser $authUser, CommercialLead $commercialLead): bool
    {
        return $authUser->can('Update:CommercialLead');
    }

    public function delete(AuthUser $authUser, CommercialLead $commercialLead): bool
    {
        return $authUser->can('Delete:CommercialLead');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:CommercialLead');
    }

    public function restore(AuthUser $authUser, CommercialLead $commercialLead): bool
    {
        return $authUser->can('Restore:CommercialLead');
    }

    public function forceDelete(AuthUser $authUser, CommercialLead $commercialLead): bool
    {
        return $authUser->can('ForceDelete:CommercialLead');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CommercialLead');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CommercialLead');
    }

    public function replicate(AuthUser $authUser, CommercialLead $commercialLead): bool
    {
        return $authUser->can('Replicate:CommercialLead');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CommercialLead');
    }

}
<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\SatisfactionSurvey;
use Illuminate\Auth\Access\HandlesAuthorization;

class SatisfactionSurveyPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SatisfactionSurvey');
    }

    public function view(AuthUser $authUser, SatisfactionSurvey $satisfactionSurvey): bool
    {
        return $authUser->can('View:SatisfactionSurvey');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SatisfactionSurvey');
    }

    public function update(AuthUser $authUser, SatisfactionSurvey $satisfactionSurvey): bool
    {
        return $authUser->can('Update:SatisfactionSurvey');
    }

    public function delete(AuthUser $authUser, SatisfactionSurvey $satisfactionSurvey): bool
    {
        return $authUser->can('Delete:SatisfactionSurvey');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:SatisfactionSurvey');
    }

    public function restore(AuthUser $authUser, SatisfactionSurvey $satisfactionSurvey): bool
    {
        return $authUser->can('Restore:SatisfactionSurvey');
    }

    public function forceDelete(AuthUser $authUser, SatisfactionSurvey $satisfactionSurvey): bool
    {
        return $authUser->can('ForceDelete:SatisfactionSurvey');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SatisfactionSurvey');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SatisfactionSurvey');
    }

    public function replicate(AuthUser $authUser, SatisfactionSurvey $satisfactionSurvey): bool
    {
        return $authUser->can('Replicate:SatisfactionSurvey');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SatisfactionSurvey');
    }

}
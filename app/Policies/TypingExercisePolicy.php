<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\TypingExercise;
use Illuminate\Auth\Access\HandlesAuthorization;

class TypingExercisePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:TypingExercise');
    }

    public function view(AuthUser $authUser, TypingExercise $typingExercise): bool
    {
        return $authUser->can('View:TypingExercise');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:TypingExercise');
    }

    public function update(AuthUser $authUser, TypingExercise $typingExercise): bool
    {
        return $authUser->can('Update:TypingExercise');
    }

    public function delete(AuthUser $authUser, TypingExercise $typingExercise): bool
    {
        return $authUser->can('Delete:TypingExercise');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:TypingExercise');
    }

    public function restore(AuthUser $authUser, TypingExercise $typingExercise): bool
    {
        return $authUser->can('Restore:TypingExercise');
    }

    public function forceDelete(AuthUser $authUser, TypingExercise $typingExercise): bool
    {
        return $authUser->can('ForceDelete:TypingExercise');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:TypingExercise');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:TypingExercise');
    }

    public function replicate(AuthUser $authUser, TypingExercise $typingExercise): bool
    {
        return $authUser->can('Replicate:TypingExercise');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:TypingExercise');
    }

}
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\FinancialAccount;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class FinancialAccountPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:FinancialAccount') || $authUser->hasRole('super_admin');
    }

    public function view(AuthUser $authUser, FinancialAccount $account): bool
    {
        return $authUser->can('View:FinancialAccount') || $authUser->hasRole('super_admin');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:FinancialAccount') || $authUser->hasRole('super_admin');
    }

    public function update(AuthUser $authUser, FinancialAccount $account): bool
    {
        return $authUser->can('Update:FinancialAccount') || $authUser->hasRole('super_admin');
    }

    public function delete(AuthUser $authUser, FinancialAccount $account): bool
    {
        return $authUser->can('Delete:FinancialAccount') || $authUser->hasRole('super_admin');
    }

    public function transfer(AuthUser $authUser, ?FinancialAccount $account = null): bool
    {
        return $authUser->can('Transfer:FinancialAccount') || $authUser->hasRole('super_admin');
    }

    public function statement(AuthUser $authUser, ?FinancialAccount $account = null): bool
    {
        return $authUser->can('Statement:FinancialAccount') || $authUser->hasRole('super_admin');
    }

    public function adjust(AuthUser $authUser, ?FinancialAccount $account = null): bool
    {
        return $authUser->can('Adjust:FinancialAccount') || $authUser->hasRole('super_admin');
    }
}


<?php

namespace App\Policies;

use App\Models\ContractHistory;
use App\Models\User;

class ContractHistoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:ContractHistory');
    }

    public function view(User $user, ContractHistory $record): bool
    {
        return $user->can('View:ContractHistory');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:ContractHistory');
    }
}

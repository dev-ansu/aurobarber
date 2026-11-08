<?php

namespace App\Policies;

use App\Models\User;
use Filament\Actions\Exports\Models\Export;

class ExportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->getAllPermissions()->contains('name', 'ViewAny:Export');
    }

    public function view(User $user, Export $export): bool
    {
        return (int) $export->user_id === (int) $user->getKey()
            && $user->getAllPermissions()->contains('name', 'Download:Export');
    }
}

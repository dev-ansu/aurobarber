<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Barbershop;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SensitiveClientDataAccess
{
    public const PERMISSION = 'View:SensitiveClientData';

    public function canView(?User $user = null): bool
    {
        $user ??= auth()->user();
        $tenant = Filament::getTenant();

        return $user instanceof User
            && $user->role !== 'master_admin'
            && $tenant instanceof Barbershop
            && $user->canAccessTenant($tenant)
            && $user->hasPermissionTo(self::PERMISSION, 'web');
    }

    /**
     * Record access before a sensitive value is returned to the user.
     *
     * @param  array<int, string>  $fields
     */
    public function record(Model $record, array $fields): void
    {
        $user = auth()->user();
        $tenant = Filament::getTenant();

        if (! $this->canView($user) || ! $tenant instanceof Barbershop || ! $user instanceof User) {
            throw new AuthorizationException('Você não tem permissão para visualizar os dados pessoais deste cliente.');
        }

        if (
            $record->getAttribute('barbershop_id') !== null
            && (int) $record->getAttribute('barbershop_id') !== (int) $tenant->getKey()
        ) {
            throw new AuthorizationException('O registro não pertence à barbearia atual.');
        }

        DB::table('sensitive_data_access_logs')->insert([
            'user_id' => $user->getKey(),
            'barbershop_id' => $tenant->getKey(),
            'subject_type' => $record::class,
            'subject_id' => (string) $record->getKey(),
            'fields' => json_encode(array_values(array_unique($fields)), JSON_THROW_ON_ERROR),
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }
}

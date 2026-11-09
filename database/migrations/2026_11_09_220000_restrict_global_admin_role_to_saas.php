<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $barbershopOwner = Role::firstOrCreate([
            'name' => 'barbershop_owner',
            'guard_name' => 'web',
        ]);

        User::query()
            ->where(function ($query): void {
                $query->where('role', 'owner')
                    ->orWhereHas('ownedBarbershops');
            })
            ->where('role', '!=', 'master_admin')
            ->with('roles')
            ->chunkById(100, function ($owners) use ($barbershopOwner): void {
                foreach ($owners as $owner) {
                    if ($owner->hasRole('super_admin')) {
                        $owner->removeRole('super_admin');
                    }

                    if (! $owner->hasRole('barbershop_owner')) {
                        $owner->assignRole($barbershopOwner);
                    }
                }
            });
    }

    public function down(): void
    {
        //
    }
};

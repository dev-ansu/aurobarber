<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $permissionModel = config('permission.models.permission');
        $roleModel = config('permission.models.role');
        $permission = $permissionModel::findOrCreate('Edit:OwnProfile', 'web');

        foreach ($roleModel::query()->where('guard_name', 'web')->get() as $role) {
            $role->givePermissionTo($permission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Keep profile access grants to avoid locking users out during rollback.
    }
};

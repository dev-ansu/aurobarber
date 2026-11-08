<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $permissionModel = config('permission.models.permission');
        $roleModel = config('permission.models.role');
        $superAdmin = $roleModel::query()->where('name', 'super_admin')->where('guard_name', 'web')->first();
        $admin = $roleModel::query()->where('name', 'admin')->where('guard_name', 'web')->first();

        foreach (['View:PixSettings', 'Update:PixSettings'] as $name) {
            $permission = $permissionModel::findOrCreate($name, 'web');
            $superAdmin?->givePermissionTo($permission);
            $admin?->givePermissionTo($permission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Keep permissions and role assignments during rollback.
    }
};

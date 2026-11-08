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

        $permission = $permissionModel::findOrCreate('Edit:OwnProfile', 'web');
        $superAdmin?->givePermissionTo($permission);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Permission and assignments are retained during rollback.
    }
};

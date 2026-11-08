<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $permissionModel = config('permission.models.permission');
        $roleModel = config('permission.models.role');
        $superAdmin = $roleModel::query()
            ->where('name', 'super_admin')
            ->where('guard_name', 'web')
            ->first();

        foreach (['View:CompletionReports', 'Export:CompletionReports'] as $permission) {
            $permissionRecord = $permissionModel::findOrCreate($permission, 'web');
            $superAdmin?->givePermissionTo($permissionRecord);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Keep permission assignments intact when rolling back application migrations.
    }
};

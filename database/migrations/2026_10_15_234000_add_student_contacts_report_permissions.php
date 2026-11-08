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

        $secretaria = $roleModel::query()
            ->where('name', 'secretaria')
            ->where('guard_name', 'web')
            ->first();

        foreach (['View:StudentContactsReport', 'Export:StudentContactsReport'] as $name) {
            $permission = $permissionModel::findOrCreate($name, 'web');
            $superAdmin?->givePermissionTo($permission);
            $secretaria?->givePermissionTo($permission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Permission rows are kept to preserve manual role assignments.
    }
};

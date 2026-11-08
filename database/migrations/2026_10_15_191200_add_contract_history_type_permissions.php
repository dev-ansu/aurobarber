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

        foreach (['ViewAny:ContractHistoryType', 'View:ContractHistoryType', 'Create:ContractHistoryType', 'Update:ContractHistoryType'] as $name) {
            $permission = $permissionModel::findOrCreate($name, 'web');
            $superAdmin?->givePermissionTo($permission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Permissions and assignments are retained during rollback.
    }
};

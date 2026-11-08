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

        $permissions = [
            'ViewAny:CommercialLead',
            'View:CommercialLead',
            'Create:CommercialLead',
            'Update:CommercialLead',
            'Delete:CommercialLead',
            'DeleteAny:CommercialLead',
            'ViewAny:CommercialVisit',
            'View:CommercialVisit',
            'Create:CommercialVisit',
            'Update:CommercialVisit',
            'Delete:CommercialVisit',
            'DeleteAny:CommercialVisit',
            'ViewAny:ContractHistory',
            'View:ContractHistory',
            'Create:ContractHistory',
            'View:CommercialOverview',
            'View:CommercialReport',
            'Export:CommercialReport',
        ];

        foreach ($permissions as $name) {
            $permission = $permissionModel::findOrCreate($name, 'web');
            $superAdmin?->givePermissionTo($permission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Keep permission assignments intact when rolling back application migrations.
    }
};

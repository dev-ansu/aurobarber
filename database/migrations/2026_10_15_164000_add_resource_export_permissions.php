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

        foreach ([
            'Export:CashRegister',
            'Export:CashTransaction',
            'Export:Classroom',
            'Export:Collaborator',
            'Export:ContractType',
            'Export:Course',
            'Export:FinancialType',
            'Export:Formation',
            'Export:Installment',
            'Export:PaymentMethod',
            'Export:Person',
            'Export:Responsible',
            'Export:Role',
            'Export:School',
            'Export:StudentSchedule',
            'Export:User',
        ] as $permission) {
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

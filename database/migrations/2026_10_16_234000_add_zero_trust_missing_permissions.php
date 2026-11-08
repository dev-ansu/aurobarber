<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'View:BirthdaysTableWidget',
            'View:CommercialLeadsKanban',
            'ViewAny:Payable',
            'View:Payable',
            'Create:Payable',
            'Update:Payable',
            'Delete:Payable',
            'DeleteAny:Payable',
            'View:SalesBySellerReport',
            'Export:SalesBySellerReport',
            'View:StudentAbsencesReport',
            'Export:StudentAbsencesReport',
            'View:DreReport',
            'Export:DreReport',
            'View:PixSettings',
            'View:ScheduleBoard',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $superAdmin = Role::where('name', 'super_admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permissions);
        }
    }

    public function down(): void
    {
        Permission::whereIn('name', [
            'View:BirthdaysTableWidget',
            'View:CommercialLeadsKanban',
            'ViewAny:Payable',
            'View:Payable',
            'Create:Payable',
            'Update:Payable',
            'Delete:Payable',
            'DeleteAny:Payable',
            'View:SalesBySellerReport',
            'Export:SalesBySellerReport',
            'View:StudentAbsencesReport',
            'Export:StudentAbsencesReport',
            'View:DreReport',
            'Export:DreReport',
            'View:PixSettings',
            'View:ScheduleBoard',
        ])->delete();
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'View:SalesBySellerReport',
            'Export:SalesBySellerReport',
            'Print:InstallmentReceipt',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $superAdmin = Role::where('name', 'super_admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permissions);
        }

        $secretaria = Role::where('name', 'secretaria')->first();
        if ($secretaria) {
            $secretaria->givePermissionTo($permissions);
        }
    }

    public function down(): void
    {
        Permission::whereIn('name', [
            'View:SalesBySellerReport',
            'Export:SalesBySellerReport',
            'Print:InstallmentReceipt',
        ])->delete();
    }
};

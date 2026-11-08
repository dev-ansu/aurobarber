<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'ViewSensitiveData:Person',
            'ViewSensitiveData:Contract',
            'ViewSensitive:Cpf',
            'ViewSensitive:Phone',
            'ViewSensitive:Email',
            'ViewSensitive:Financial',
        ];

        $createdPermissions = [];
        foreach ($permissions as $name) {
            $createdPermissions[] = Permission::findOrCreate($name, 'web');
        }

        $superAdmin = Role::where('name', 'super_admin')->where('guard_name', 'web')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($createdPermissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::whereIn('name', [
            'ViewSensitiveData:Person',
            'ViewSensitiveData:Contract',
            'ViewSensitive:Cpf',
            'ViewSensitive:Phone',
            'ViewSensitive:Email',
            'ViewSensitive:Financial',
        ])->where('guard_name', 'web')->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};

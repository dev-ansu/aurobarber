<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const PERMISSIONS = [
        'ViewAny:Product',
        'View:Product',
        'Create:Product',
        'Update:Product',
        'Delete:Product',
        'DeleteAny:Product',
        'Sell:Product',
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $created = [];
        foreach (self::PERMISSIONS as $permissionName) {
            $created[] = Permission::findOrCreate($permissionName, 'web');
        }

        Role::query()
            ->where('guard_name', 'web')
            ->whereIn('name', ['super_admin', 'barbershop_owner', 'barbershop_manager'])
            ->get()
            ->each(function (Role $role) use ($created): void {
                $role->givePermissionTo($created);
            });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::query()
            ->where('guard_name', 'web')
            ->where('name', 'Sell:Product')
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};

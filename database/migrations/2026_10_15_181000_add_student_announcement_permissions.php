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

        $permission = Permission::findOrCreate('ViewAny:StudentAnnouncement', 'web');
        Permission::findOrCreate('View:StudentAnnouncement', 'web');
        Permission::findOrCreate('Create:StudentAnnouncement', 'web');
        Permission::findOrCreate('Update:StudentAnnouncement', 'web');
        Permission::findOrCreate('Delete:StudentAnnouncement', 'web');

        Role::query()
            ->where('name', 'super_admin')
            ->where('guard_name', 'web')
            ->first()
            ?->givePermissionTo([
                'ViewAny:StudentAnnouncement',
                'View:StudentAnnouncement',
                'Create:StudentAnnouncement',
                'Update:StudentAnnouncement',
                'Delete:StudentAnnouncement',
            ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};

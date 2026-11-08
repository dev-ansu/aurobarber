<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'ViewAny:TaskBoard',
            'View:TaskBoard',
            'Create:TaskBoard',
            'Update:TaskBoard',
            'Delete:TaskBoard',
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
            'ViewAny:TaskBoard',
            'View:TaskBoard',
            'Create:TaskBoard',
            'Update:TaskBoard',
            'Delete:TaskBoard',
        ])->delete();
    }
};

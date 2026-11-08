<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::findOrCreate('ChangeStatus:StudentAttendance', 'web');

        $superAdmin = Role::where('name', 'super_admin')->where('guard_name', 'web')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permission);
        }
    }

    public function down(): void
    {
        Permission::where('name', 'ChangeStatus:StudentAttendance')->where('guard_name', 'web')->delete();
    }
};


<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'ViewAny:SchoolAppointment',
            'View:SchoolAppointment',
            'Create:SchoolAppointment',
            'Update:SchoolAppointment',
            'Delete:SchoolAppointment',
            'View:SchoolAppointmentsCalendar',
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
            'ViewAny:SchoolAppointment',
            'View:SchoolAppointment',
            'Create:SchoolAppointment',
            'Update:SchoolAppointment',
            'Delete:SchoolAppointment',
            'View:SchoolAppointmentsCalendar',
        ])->delete();
    }
};

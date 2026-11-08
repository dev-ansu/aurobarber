<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'ViewAny:SchoolGoal',
            'View:SchoolGoal',
            'Create:SchoolGoal',
            'Update:SchoolGoal',
            'Delete:SchoolGoal',
            'View:SchoolGoalsWidget',
            'View:CommissionReport',
            'Export:CommissionReport',
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
            'ViewAny:SchoolGoal',
            'View:SchoolGoal',
            'Create:SchoolGoal',
            'Update:SchoolGoal',
            'Delete:SchoolGoal',
            'View:SchoolGoalsWidget',
            'View:CommissionReport',
            'Export:CommissionReport',
        ])->delete();
    }
};

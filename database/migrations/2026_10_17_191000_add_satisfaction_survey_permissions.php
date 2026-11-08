<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'ViewAny:SatisfactionSurvey',
            'View:SatisfactionSurvey',
            'Create:SatisfactionSurvey',
            'Update:SatisfactionSurvey',
            'Delete:SatisfactionSurvey',
            'DeleteAny:SatisfactionSurvey',
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
            'ViewAny:SatisfactionSurvey',
            'View:SatisfactionSurvey',
            'Create:SatisfactionSurvey',
            'Update:SatisfactionSurvey',
            'Delete:SatisfactionSurvey',
            'DeleteAny:SatisfactionSurvey',
        ])->delete();
    }
};

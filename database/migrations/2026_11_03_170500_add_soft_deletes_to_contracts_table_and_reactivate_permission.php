<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('contracts', 'deleted_at')) {
            Schema::table('contracts', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        $permission = Permission::findOrCreate('Reactivate:Contract', 'web');

        $superAdmin = Role::where('name', 'super_admin')->where('guard_name', 'web')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permission);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('contracts', 'deleted_at')) {
            Schema::table('contracts', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        Permission::where('name', 'Reactivate:Contract')->where('guard_name', 'web')->delete();
    }
};


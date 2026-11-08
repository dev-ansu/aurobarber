<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('panel_access')->default(true)->after('password');
        });

        // Usuários com roles ou super admin recebem panel_access = true
        \App\Models\User::query()
            ->whereHas('roles')
            ->orWhere('id', 1)
            ->update(['panel_access' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('panel_access');
        });
    }
};

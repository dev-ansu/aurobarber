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
        if (Schema::hasTable('schools') && ! Schema::hasColumn('schools', 'pix_enabled')) {
            Schema::table('schools', function (Blueprint $table) {
                $table->boolean('pix_enabled')->default(true)->after('state');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('schools') && Schema::hasColumn('schools', 'pix_enabled')) {
            Schema::table('schools', function (Blueprint $table) {
                $table->dropColumn('pix_enabled');
            });
        }
    }
};

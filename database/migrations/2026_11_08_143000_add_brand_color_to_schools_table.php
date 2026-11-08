<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('schools')) {
            Schema::table('schools', function (Blueprint $table) {
                if (! Schema::hasColumn('schools', 'brand_color')) {
                    $table->string('brand_color', 20)->default('#F59E0B')->after('logo_path');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('schools')) {
            Schema::table('schools', function (Blueprint $table) {
                if (Schema::hasColumn('schools', 'brand_color')) {
                    $table->dropColumn('brand_color');
                }
            });
        }
    }
};

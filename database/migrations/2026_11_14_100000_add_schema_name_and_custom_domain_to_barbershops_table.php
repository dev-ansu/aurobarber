<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('barbershops', function (Blueprint $table) {
            if (!Schema::hasColumn('barbershops', 'schema_name')) {
                $table->string('schema_name')->nullable()->unique()->after('slug');
            }
            if (!Schema::hasColumn('barbershops', 'custom_domain')) {
                $table->string('custom_domain')->nullable()->unique()->after('schema_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('barbershops', function (Blueprint $table) {
            $table->dropColumn(['schema_name', 'custom_domain']);
        });
    }
};

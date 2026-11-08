<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commercial_leads', function (Blueprint $table) {
            $table->string('responsible_name', 255)->nullable()->after('phone');
            $table->string('responsible_phone', 40)->nullable()->after('responsible_name');
            $table->string('responsible_relationship', 30)->nullable()->after('responsible_phone');
        });
    }

    public function down(): void
    {
        Schema::table('commercial_leads', function (Blueprint $table) {
            $table->dropColumn(['responsible_name', 'responsible_phone', 'responsible_relationship']);
        });
    }
};

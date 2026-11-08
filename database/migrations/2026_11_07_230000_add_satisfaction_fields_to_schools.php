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
                if (! Schema::hasColumn('schools', 'show_satisfaction_rating')) {
                    $table->boolean('show_satisfaction_rating')->default(false)->after('showcase_enabled');
                }
                if (! Schema::hasColumn('schools', 'custom_satisfaction_score')) {
                    $table->string('custom_satisfaction_score', 50)->nullable()->after('show_satisfaction_rating');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('schools')) {
            Schema::table('schools', function (Blueprint $table) {
                if (Schema::hasColumn('schools', 'custom_satisfaction_score')) {
                    $table->dropColumn('custom_satisfaction_score');
                }
                if (Schema::hasColumn('schools', 'show_satisfaction_rating')) {
                    $table->dropColumn('show_satisfaction_rating');
                }
            });
        }
    }
};

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
        Schema::table('classrooms', function (Blueprint $table) {
            Schema::table('classrooms', function (Blueprint $table) {
                $table->time('opening_time')->default('08:00:00');
                $table->time('closing_time')->default('23:00:00');
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('classrooms', function (Blueprint $table) {
            Schema::table('classrooms', function (Blueprint $table) {
                $table->dropColumn(['opening_time', 'closing_time']);
            });
        });
    }
};

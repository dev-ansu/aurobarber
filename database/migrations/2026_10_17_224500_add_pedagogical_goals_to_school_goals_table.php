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
        Schema::table('school_goals', function (Blueprint $table) {
            $table->decimal('target_attendance_rate', 5, 2)->nullable()->default(85.00)->after('target_revenue_amount');
            $table->unsignedInteger('target_max_cancellations')->nullable()->default(3)->after('target_attendance_rate');
            $table->unsignedInteger('target_retention_interviews')->nullable()->default(10)->after('target_max_cancellations');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('school_goals', function (Blueprint $table) {
            $table->dropColumn([
                'target_attendance_rate',
                'target_max_cancellations',
                'target_retention_interviews',
            ]);
        });
    }
};

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
        Schema::table('lms_student_lesson_progress', function (Blueprint $table) {
            $table->boolean('help_requested')->default(false)->after('time_spent_seconds');
            $table->timestamp('help_requested_at')->nullable()->after('help_requested');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lms_student_lesson_progress', function (Blueprint $table) {
            $table->dropColumn(['help_requested', 'help_requested_at']);
        });
    }
};

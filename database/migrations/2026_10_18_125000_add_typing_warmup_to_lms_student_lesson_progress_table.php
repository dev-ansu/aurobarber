<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lms_student_lesson_progress', function (Blueprint $table) {
            $table->timestamp('typing_warmup_completed_at')->nullable()->after('session_date');
            $table->unsignedInteger('typing_time_seconds')->default(0)->after('typing_warmup_completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('lms_student_lesson_progress', function (Blueprint $table) {
            $table->dropColumn(['typing_warmup_completed_at', 'typing_time_seconds']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_access_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->nullable()->constrained('contracts')->cascadeOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('people')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('lesson_id')->nullable()->constrained('lms_lessons')->nullOnDelete();
            $table->string('action', 50)->index(); // portal_login, portal_logout, lesson_start, lesson_exit, lesson_completed, help_requested
            $table->string('description', 255);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['contract_id', 'created_at']);
            $table->index(['action', 'created_at']);
        });

        Schema::table('lms_student_lesson_progress', function (Blueprint $table) {
            $table->timestamp('last_heartbeat_at')->nullable()->after('time_spent_seconds');
            $table->timestamp('exited_at')->nullable()->after('last_heartbeat_at');
        });
    }

    public function down(): void
    {
        Schema::table('lms_student_lesson_progress', function (Blueprint $table) {
            $table->dropColumn(['last_heartbeat_at', 'exited_at']);
        });

        Schema::dropIfExists('student_access_logs');
    }
};

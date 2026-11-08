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
        Schema::create('student_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_session_id')
                ->constrained('class_sessions')
                ->restrictOnDelete();
            $table->foreignId('student_schedule_id')
                ->constrained('student_schedules')
                ->restrictOnDelete();
            $table->date('attendance_date');
            $table->string('status', 20)->default('absent');
            $table->text('note')->nullable();
            $table->foreignId('marked_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('marked_at')->nullable();
            $table->string('marking_source', 20)->nullable();
            $table->timestamps();

            $table->unique(
                ['class_session_id', 'student_schedule_id'],
                'student_attendances_session_schedule_unique',
            );
            $table->unique(
                ['student_schedule_id', 'attendance_date'],
                'student_attendances_schedule_date_unique',
            );
            $table->index(['attendance_date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_attendances');
    }
};

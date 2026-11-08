<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lms_student_lesson_progress', function (Blueprint $table): void {
            if (! Schema::hasColumn('lms_student_lesson_progress', 'is_in_focus')) {
                $table->boolean('is_in_focus')->default(true)->after('help_requested_at');
            }
            if (! Schema::hasColumn('lms_student_lesson_progress', 'focus_lost_at')) {
                $table->timestamp('focus_lost_at')->nullable()->after('is_in_focus');
            }
            if (! Schema::hasColumn('lms_student_lesson_progress', 'total_focus_lost_seconds')) {
                $table->unsignedInteger('total_focus_lost_seconds')->default(0)->after('focus_lost_at');
            }
        });

        Schema::table('lms_student_step_attempts', function (Blueprint $table): void {
            if (! Schema::hasColumn('lms_student_step_attempts', 'attempt_data')) {
                $table->json('attempt_data')->nullable()->after('student_answer');
            }
        });

        if (! Schema::hasTable('lms_student_assignments')) {
            Schema::create('lms_student_assignments', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('progress_id')->constrained('lms_student_lesson_progress')->cascadeOnDelete();
                $table->foreignId('step_id')->constrained('lms_lesson_steps')->cascadeOnDelete();
                $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
                $table->foreignId('student_id')->constrained('people')->cascadeOnDelete();
                $table->string('file_path');
                $table->string('file_name');
                $table->text('student_notes')->nullable();
                $table->string('status')->default('pending'); // pending, approved, needs_revision
                $table->decimal('grade', 5, 2)->nullable();
                $table->text('feedback')->nullable();
                $table->foreignId('graded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('graded_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lms_student_assignments');

        Schema::table('lms_student_step_attempts', function (Blueprint $table): void {
            if (Schema::hasColumn('lms_student_step_attempts', 'attempt_data')) {
                $table->dropColumn('attempt_data');
            }
        });

        Schema::table('lms_student_lesson_progress', function (Blueprint $table): void {
            $columns = array_filter(
                ['is_in_focus', 'focus_lost_at', 'total_focus_lost_seconds'],
                fn (string $col): bool => Schema::hasColumn('lms_student_lesson_progress', $col)
            );
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};

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
            if (! Schema::hasColumn('lms_student_lesson_progress', 'educator_message')) {
                $table->text('educator_message')->nullable()->after('total_focus_lost_seconds');
            }
            if (! Schema::hasColumn('lms_student_lesson_progress', 'educator_message_sender')) {
                $table->string('educator_message_sender')->nullable()->after('educator_message');
            }
            if (! Schema::hasColumn('lms_student_lesson_progress', 'educator_message_at')) {
                $table->timestamp('educator_message_at')->nullable()->after('educator_message_sender');
            }
            if (! Schema::hasColumn('lms_student_lesson_progress', 'educator_message_read_at')) {
                $table->timestamp('educator_message_read_at')->nullable()->after('educator_message_at');
            }
            if (! Schema::hasColumn('lms_student_lesson_progress', 'student_rating')) {
                $table->unsignedTinyInteger('student_rating')->nullable()->after('educator_message_read_at');
            }
            if (! Schema::hasColumn('lms_student_lesson_progress', 'student_rating_comment')) {
                $table->text('student_rating_comment')->nullable()->after('student_rating');
            }
            if (! Schema::hasColumn('lms_student_lesson_progress', 'student_rated_at')) {
                $table->timestamp('student_rated_at')->nullable()->after('student_rating_comment');
            }
        });

        if (! Schema::hasTable('lms_course_final_exam_attempts')) {
            Schema::create('lms_course_final_exam_attempts', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
                $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
                $table->foreignId('student_id')->nullable()->constrained('people')->nullOnDelete();
                $table->decimal('score', 5, 2)->default(0);
                $table->boolean('passed')->default(false);
                $table->unsignedSmallInteger('total_questions')->default(0);
                $table->unsignedSmallInteger('correct_answers')->default(0);
                $table->json('answers_payload')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lms_course_final_exam_attempts');

        Schema::table('lms_student_lesson_progress', function (Blueprint $table): void {
            $cols = array_filter([
                'educator_message',
                'educator_message_sender',
                'educator_message_at',
                'educator_message_read_at',
                'student_rating',
                'student_rating_comment',
                'student_rated_at',
            ], fn (string $c): bool => Schema::hasColumn('lms_student_lesson_progress', $c));

            if ($cols !== []) {
                $table->dropColumn($cols);
            }
        });
    }
};

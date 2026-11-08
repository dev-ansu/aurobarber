<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('typing_exercises', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->unsignedSmallInteger('level')->default(1)->index();
            $table->string('category', 50)->default('home_row')->index();
            $table->text('target_text');
            $table->text('instructions')->nullable();
            $table->string('keyboard_layout', 20)->default('abnt2');
            $table->unsignedSmallInteger('min_wpm')->default(15);
            $table->decimal('min_accuracy', 5, 2)->default(80.00);
            $table->unsignedInteger('sort_order')->default(1)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('student_typing_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('people')->cascadeOnDelete();
            $table->foreignId('typing_exercise_id')->constrained('typing_exercises')->cascadeOnDelete();
            $table->foreignId('lesson_id')->nullable()->constrained('lms_lessons')->nullOnDelete();
            $table->decimal('wpm', 8, 2)->default(0.00)->index();
            $table->decimal('cpm', 8, 2)->default(0.00);
            $table->decimal('accuracy_percentage', 5, 2)->default(0.00);
            $table->unsignedInteger('total_keystrokes')->default(0);
            $table->unsignedInteger('correct_keystrokes')->default(0);
            $table->unsignedInteger('error_keystrokes')->default(0);
            $table->unsignedInteger('time_spent_seconds')->default(0);
            $table->unsignedTinyInteger('stars')->default(1);
            $table->boolean('passed')->default(true);
            $table->timestamp('completed_at')->nullable()->index();
            $table->timestamps();

            $table->index(['contract_id', 'typing_exercise_id']);
            $table->index(['typing_exercise_id', 'wpm']);
            $table->index(['contract_id', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_typing_records');
        Schema::dropIfExists('typing_exercises');
    }
};

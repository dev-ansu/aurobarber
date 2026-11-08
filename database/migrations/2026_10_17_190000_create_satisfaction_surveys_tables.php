<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('satisfaction_surveys', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('satisfaction_survey_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('satisfaction_survey_id')->constrained('satisfaction_surveys')->cascadeOnDelete();
            $table->string('question');
            $table->string('type')->default('rating_5'); // rating_5, nps_10, multiple_choice, text
            $table->json('options')->nullable();
            $table->boolean('is_required')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('satisfaction_survey_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('satisfaction_survey_id')->constrained('satisfaction_surveys')->cascadeOnDelete();
            $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('people')->nullOnDelete();
            $table->foreignId('student_attendance_id')->nullable()->constrained('student_attendances')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('satisfaction_survey_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('satisfaction_survey_response_id')->constrained('satisfaction_survey_responses')->cascadeOnDelete();
            $table->foreignId('satisfaction_survey_question_id')->constrained('satisfaction_survey_questions')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating_value')->nullable();
            $table->text('text_value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('satisfaction_survey_answers');
        Schema::dropIfExists('satisfaction_survey_responses');
        Schema::dropIfExists('satisfaction_survey_questions');
        Schema::dropIfExists('satisfaction_surveys');
    }
};

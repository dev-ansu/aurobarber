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
        // 1. Módulos do Curso LMS
        Schema::create('lms_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Aulas do LMS (Cada aula com meta de 90min)
        Schema::create('lms_lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained('lms_modules')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('estimated_duration_minutes')->default(90);
            $table->decimal('passing_score', 5, 2)->default(80.00);
            $table->unsignedInteger('sort_order')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 3. Etapas da Aula (Método Jesuítico: Praelectio, Experientia, Repetitio, Concertatio, Examen)
        Schema::create('lms_lesson_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained('lms_lessons')->cascadeOnDelete();
            $table->string('phase')->default('praelectio'); // praelectio, practice, repetitio, concertatio, examen
            $table->string('step_type')->default('theory'); // theory, quiz, hotspot_simulation, challenge
            $table->string('title');
            $table->json('content'); // Armazena texto, URLs de áudio/imagem, hotspots ou questões
            $table->unsignedInteger('sort_order')->default(1);
            $table->timestamps();
        });

        // 4. Progresso do Aluno na Aula com Trava Diária e Pacing
        Schema::create('lms_student_lesson_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('people')->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained('lms_lessons')->cascadeOnDelete();
            $table->foreignId('current_step_id')->nullable()->constrained('lms_lesson_steps')->nullOnDelete();
            $table->string('status')->default('locked'); // locked, in_progress, completed, failed
            $table->decimal('score', 5, 2)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->date('session_date')->nullable();
            $table->unsignedInteger('time_spent_seconds')->default(0);
            $table->timestamps();

            $table->unique(['contract_id', 'lesson_id']);
        });

        // 5. Tentativas e Auditoria das Etapas (Repetitio / Fixação Inaciana)
        Schema::create('lms_student_step_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('progress_id')->constrained('lms_student_lesson_progress')->cascadeOnDelete();
            $table->foreignId('step_id')->constrained('lms_lesson_steps')->cascadeOnDelete();
            $table->unsignedInteger('attempt_number')->default(1);
            $table->boolean('is_correct')->default(false);
            $table->text('student_answer')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lms_student_step_attempts');
        Schema::dropIfExists('lms_student_lesson_progress');
        Schema::dropIfExists('lms_lesson_steps');
        Schema::dropIfExists('lms_lessons');
        Schema::dropIfExists('lms_modules');
    }
};

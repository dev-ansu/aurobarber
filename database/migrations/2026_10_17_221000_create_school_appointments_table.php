<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_appointments', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 255);
            $table->string('type', 40)->default('geral'); // cancelamento, reuniao_pais, reuniao_equipe, atendimento, evento_escola, geral
            $table->string('priority', 20)->default('media'); // baixa, media, alta, urgente
            $table->string('status', 20)->default('scheduled'); // scheduled, completed, canceled
            $table->date('date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->boolean('all_day')->default(false);
            $table->string('location', 150)->nullable();
            $table->text('description')->nullable();

            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_shared_with_all')->default(false);

            $table->foreignId('student_id')->nullable()->constrained('people')->nullOnDelete();
            $table->foreignId('contract_id')->nullable()->constrained('contracts')->nullOnDelete();

            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['date', 'status']);
            $table->index('assigned_to_user_id');
            $table->index('is_shared_with_all');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_appointments');
    }
};

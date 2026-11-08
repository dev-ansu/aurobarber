<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_makeup_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->foreignId('student_attendance_id')->nullable()->constrained('student_attendances')->nullOnDelete();
            $table->foreignId('classroom_id')->nullable()->constrained('classrooms')->nullOnDelete();
            $table->foreignId('scheduled_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('makeup_date');
            $table->time('start_hour');
            $table->time('end_hour');
            $table->string('status', 30)->default('scheduled'); // scheduled, completed, missed, cancelled
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['contract_id', 'makeup_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_makeup_classes');
    }
};

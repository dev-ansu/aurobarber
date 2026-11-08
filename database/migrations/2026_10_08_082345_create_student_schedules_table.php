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
        Schema::create('student_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('weekday');
            $table->foreignId('contract_item_id')
                ->constrained('contract_items')
                ->cascadeOnDelete();
            $table->foreignId('classroom_id')
                ->constrained('classrooms', 'id', 'students_schedules_classroom_id')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('start_hour');
            $table->string('end_hour');
            $table->timestamps();

            $table->unique([
                'contract_item_id',
                'weekday',
                'start_hour',
                'end_hour',
            ], 'student_schedules_item_slot_unique');
            $table->index([
                'classroom_id',
                'weekday',
                'start_hour',
                'end_hour',
            ], 'student_schedules_room_slot_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_schedules');
    }
};

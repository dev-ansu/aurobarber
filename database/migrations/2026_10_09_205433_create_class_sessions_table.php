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
        Schema::create('class_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')
                ->constrained('classrooms')
                ->restrictOnDelete();
            $table->date('date');
            $table->unsignedTinyInteger('weekday');
            $table->string('start_hour');
            $table->string('end_hour');
            $table->timestamps();

            $table->unique(
                ['classroom_id', 'date', 'start_hour', 'end_hour'],
                'class_sessions_room_date_slot_unique',
            );
            $table->index(['date', 'classroom_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_sessions');
    }
};

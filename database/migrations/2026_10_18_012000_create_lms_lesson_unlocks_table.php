<?php

declare(strict_types=1);

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
        Schema::create('lms_lesson_unlocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->foreignId('lesson_id')->nullable()->constrained('lms_lessons')->cascadeOnDelete();
            $table->foreignId('unlocked_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('unlocked_date');
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->index(['contract_id', 'unlocked_date']);
            $table->index(['contract_id', 'lesson_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lms_lesson_unlocks');
    }
};

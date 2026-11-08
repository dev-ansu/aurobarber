<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('professionals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barbershop_id')->constrained('barbershops')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // Login do barbeiro
            $table->string('name');
            $table->string('nickname')->nullable(); // ex: Marinho, Zé do Corte
            $table->string('phone')->nullable();
            $table->string('photo_url')->nullable();
            $table->text('bio')->nullable();
            $table->decimal('commission_rate', 5, 2)->default(50.00); // 50% de comissão padrão
            $table->json('working_days')->nullable(); // ['mon', 'tue', 'wed', 'thu', 'fri', 'sat']
            $table->time('work_start_time')->default('08:00:00');
            $table->time('work_end_time')->default('19:00:00');
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('professionals');
    }
};

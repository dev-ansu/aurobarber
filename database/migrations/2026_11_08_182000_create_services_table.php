<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barbershop_id')->constrained('barbershops')->cascadeOnDelete();
            $table->string('name'); // ex: Corte Degradê, Barboterapia, Combo Barba + Cabelo
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2); // ex: 45.00
            $table->integer('duration_minutes')->default(30); // Duração para cálculo de slots na agenda
            $table->string('category')->default('cabelo'); // cabelo, barba, combo, tratamentos
            $table->decimal('custom_commission_rate', 5, 2)->nullable(); // comissão específica do serviço
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};

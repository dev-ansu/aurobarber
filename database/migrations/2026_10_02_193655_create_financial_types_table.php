<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_types', function (Blueprint $table) {
            $table->id();
            // Se o seu sistema for multi-empresa, descomente a linha abaixo:
            // $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            $table->string('name'); // Ex: Mensalidade, Taxa de Matrícula, Uniforme
            $table->string('slug')->nullable(); // Útil para regras de backend se necessário
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_types');
    }
};

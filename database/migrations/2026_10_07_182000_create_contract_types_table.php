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
        Schema::create('contract_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Ex: Contrato de Prestação de Serviços Educacionais
            $table->string('version')->nullable(); // Ex: 2026.1, 2027
            $table->text('description')->nullable(); // Descrição interna do modelo
            $table->longText('content')->nullable(); // Cláusulas, termos e minuta contratual
            $table->boolean('is_active')->default(true); // Se está ativo para novas matrículas
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_types');
    }
};

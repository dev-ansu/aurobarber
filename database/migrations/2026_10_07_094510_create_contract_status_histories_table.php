<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_status_histories', function (Blueprint $table) {
            $table->id();

            // Contrato vinculado
            $table->foreignId('contract_id')
                ->constrained('contracts')
                ->cascadeOnDelete();

            // Usuário do sistema (operador/atendente) que realizou a alteração
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Mapeamento da transição
            $table->string('from_status');
            $table->string('to_status');
            $table->text('reason')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_status_histories');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_register_id')->constrained('cash_registers')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete(); // Usuário logado que realizou o recebimento

            // Vínculo opcional. Se for o recebimento de uma parcela, salva aqui. Se for sangria/saída avulsa, fica nulo.
            $table->foreignId('installment_id')->nullable()->constrained('installments')->nullOnDelete();

            $table->enum('type', ['input', 'output']); // Sentido do dinheiro
            $table->decimal('amount', 10, 2);
            $table->string('payment_method'); // Ex: Pix, Dinheiro, Cartao_Credito, Cartao_Debito
            $table->string('description')->nullable(); // Ex: "Recebimento da parcela 02/12 do contrato 2026-0001"

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_transactions');
    }
};

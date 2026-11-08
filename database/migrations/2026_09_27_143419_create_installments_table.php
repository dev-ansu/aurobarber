<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->onDelete('cascade');

            // Tipo financeiro diferenciado para a secretaria
            $table->string('type')->default('tuition'); // Ex: 'tuition' (mensalidade), 'registration' (matrícula), 'material', 'class_replacement'

            // Dados de ordenação (ficam nulos para parcelas avulsas como reposição)
            $table->integer('installment_number')->nullable(); // Ex: 3
            $table->integer('total_installments')->nullable();   // Ex: 12

            // Valores financeiros da parcela
            $table->decimal('amount', 10, 2);       // Valor base da cobrança
            $table->decimal('discount', 10, 2)->default(0.00);
            $table->decimal('fine', 10, 2)->default(0.00);     // Multas ou acréscimos aplicados na hora do recebimento
            $table->decimal('amount_paid', 10, 2)->nullable(); // Total de fato recebido no caixa
            $table->string('payment_method')->nullable();
            // Controle de datas e conciliação
            $table->date('due_date');                // Data de vencimento acordada
            $table->date('paid_at')->nullable();     // Data real do pagamento na secretaria

            $table->enum('status', ['pending', 'paid', 'partial', 'canceled'])
                ->default('pending');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installments');
    }
};

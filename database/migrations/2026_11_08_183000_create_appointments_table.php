<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barbershop_id')->constrained('barbershops')->cascadeOnDelete();
            $table->foreignId('professional_id')->constrained('professionals')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->string('client_name');
            $table->string('client_phone'); // WhatsApp para confirmação
            $table->string('client_email')->nullable();
            $table->timestamp('scheduled_at'); // Data e horário de início
            $table->timestamp('ends_at'); // Calculado automaticamente com base na duração do serviço
            $table->string('status')->default('scheduled'); // scheduled, confirmed, in_progress, completed, cancelled, no_show
            $table->decimal('service_price', 10, 2);
            $table->decimal('commission_rate', 5, 2); // % no momento do agendamento
            $table->decimal('commission_amount', 10, 2); // Valor em R$ que o barbeiro receberá
            $table->string('payment_method')->nullable(); // pix, dinheiro, debito, credito
            $table->string('payment_status')->default('pending'); // pending, paid
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['barbershop_id', 'scheduled_at']);
            $table->index(['professional_id', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};

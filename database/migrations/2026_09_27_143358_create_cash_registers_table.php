<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_registers', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Ex: Caixa Secretaria 01, Caixa Tesouraria
            $table->enum('status', ['open', 'closed'])->default('closed');
            $table->decimal('opening_balance', 10, 2)->default(0.00);
            $table->decimal('current_balance', 10, 2)->default(0.00);

            // Operador responsável pelo turno atual do caixa
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_registers');
    }
};

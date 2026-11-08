<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number')->unique(); // Ex: 2026-0001

            // Relacionamentos estruturais
            $table->foreignId('student_id')
                ->constrained('people')
                ->restrictOnDelete();

            $table->foreignId('responsible_id')
                ->constrained('responsibles')
                ->restrictOnDelete();

            $table->foreignId('collaborator_id')
                ->nullable()
                ->constrained('collaborators')
                ->nullOnDelete();

            // Vigência do contrato
            $table->date('start_date');
            $table->date('end_date')->nullable();

            // Valores globais (usados para a geração e auditoria do parcelamento)
            $table->decimal('total_value', 10, 2);
            $table->decimal('discount_value', 10, 2)->default(0.00);

            // Status operacional do contrato
            $table->enum('status', ['draft', 'active', 'suspended', 'canceled', 'finished'])
                ->default('active');

            $table->text('observations')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};

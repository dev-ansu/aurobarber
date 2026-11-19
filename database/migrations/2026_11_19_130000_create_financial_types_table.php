<?php

declare(strict_types=1);

use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_types', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('barbershop_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('code', 20)->nullable()->index();
            $table->string('name');
            $table->string('slug')->nullable()->index();
            $table->foreignId('parent_id')->nullable()->constrained('financial_types')->nullOnDelete();
            $table->string('type', 20)->default('expense')->index();
            $table->boolean('is_synthetic')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['barbershop_id', 'is_active', 'is_synthetic']);
        });

        // Popula automaticamente a estrutura padrão do Plano de Contas
        try {
            app(ChartOfAccountsSeeder::class)->run(null);
        } catch (Throwable) {
            // Em testes unitários isolados onde o banco pode estar temporário
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_types');
    }
};

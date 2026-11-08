<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_types', function (Blueprint $table) {
            $table->string('code', 20)->nullable()->index()->after('id');
            $table->foreignId('parent_id')->nullable()->after('code')->constrained('financial_types')->nullOnDelete();
            $table->string('type', 20)->default('revenue')->index()->after('slug'); // 'revenue', 'cost', 'expense', 'financial'
            $table->boolean('is_synthetic')->default(false)->after('type'); // true = agrupadora/sintética, false = analítica (aceita lançamentos)
            $table->text('description')->nullable()->after('is_active');
        });

        // Popula automaticamente a estrutura padrão do Plano de Contas se a tabela de migrações estiver ativa
        try {
            \Illuminate\Support\Facades\Artisan::call('db:seed', [
                '--class' => \Database\Seeders\ChartOfAccountsSeeder::class,
                '--force' => true,
            ]);
        } catch (\Throwable $e) {
            // Em ambientes de teste isolados ou restritos, falha silenciosa permitida
        }
    }

    public function down(): void
    {
        Schema::table('financial_types', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['code', 'parent_id', 'type', 'is_synthetic', 'description']);
        });
    }
};


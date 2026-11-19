<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payables', function (Blueprint $table): void {
            $table->foreignId('financial_type_id')
                ->nullable()
                ->after('supplier')
                ->constrained('financial_types')
                ->nullOnDelete();

            $table->decimal('amount_paid', 10, 2)->nullable()->after('payment_date');
            $table->string('receipt_path')->nullable()->after('notes');
            $table->boolean('is_recurring')->default(false)->after('receipt_path');
            $table->string('recurrence_period')->default('monthly')->after('is_recurring');
            $table->unsignedInteger('recurrence_limit')->nullable()->after('recurrence_period');
            $table->string('recurrence_group_id')->nullable()->after('recurrence_limit')->index();
        });

        // Garantir dados existentes: mapeia categorias legadas para o novo Plano de Contas
        try {
            $mapping = [
                'Aluguel' => '3.1.1',
                'Água e energia' => '3.1.3',
                'Produtos e insumos' => '2.2.1',
                'Salários e comissões' => '2.1.1',
                'Impostos e taxas' => '5.2.1',
                'Manutenção' => '3.2.1',
                'Outros' => '3.3.5',
            ];

            foreach ($mapping as $categoryName => $code) {
                $typeId = DB::table('financial_types')->where('code', $code)->value('id');
                if ($typeId) {
                    DB::table('payables')
                        ->whereNull('financial_type_id')
                        ->where('category', $categoryName)
                        ->update(['financial_type_id' => $typeId]);
                }
            }

            // Atualiza amount_paid para contas já marcadas como pagas anteriormente
            DB::table('payables')
                ->where('status', 'paid')
                ->whereNull('amount_paid')
                ->update(['amount_paid' => DB::raw('amount')]);
        } catch (Throwable) {
            // Em testes onde tabela pode estar vazia
        }
    }

    public function down(): void
    {
        Schema::table('payables', function (Blueprint $table): void {
            $table->dropForeign(['financial_type_id']);
            $table->dropIndex(['recurrence_group_id']);
            $table->dropColumn([
                'financial_type_id',
                'amount_paid',
                'receipt_path',
                'is_recurring',
                'recurrence_period',
                'recurrence_limit',
                'recurrence_group_id',
            ]);
        });
    }
};

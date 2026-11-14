<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabela de Vendas de Produtos (Avulso no Balcão ou comissionado a Barbeiro)
        if (!Schema::hasTable('product_sales')) {
            Schema::create('product_sales', function (Blueprint $table) {
                $table->id();
                $table->foreignId('barbershop_id')->constrained('barbershops')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->foreignId('professional_id')->nullable()->constrained('professionals')->nullOnDelete();
                $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
                $table->integer('quantity')->default(1);
                $table->decimal('unit_price', 10, 2)->default(0.00);
                $table->decimal('total_price', 10, 2)->default(0.00);
                $table->decimal('cost_price', 10, 2)->default(0.00);
                $table->decimal('commission_rate', 5, 2)->default(0.00);
                $table->decimal('commission_amount', 10, 2)->default(0.00);
                $table->string('commission_status')->default('pending'); // pending, paid
                $table->foreignId('commission_payout_id')->nullable()->constrained('commission_payouts')->nullOnDelete();
                $table->timestamp('commission_paid_at')->nullable();
                $table->string('payment_method')->default('pix');
                $table->string('client_name')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['barbershop_id', 'professional_id', 'commission_status']);
                $table->index(['barbershop_id', 'created_at']);
            });
        }

        // 2. Discriminativo de Produtos em Recibos de Fechamento de Comissão
        Schema::table('commission_payouts', function (Blueprint $table) {
            if (!Schema::hasColumn('commission_payouts', 'services_gross')) {
                $table->decimal('services_gross', 10, 2)->default(0.00)->after('appointments_count');
                $table->decimal('services_commission', 10, 2)->default(0.00)->after('services_gross');
                $table->decimal('products_gross', 10, 2)->default(0.00)->after('services_commission');
                $table->decimal('products_commission', 10, 2)->default(0.00)->after('products_gross');
                $table->integer('products_count')->default(0)->after('products_commission');
            }
        });

        // 3. Coluna de Comissão sobre Produtos no Atendimento
        Schema::table('appointments', function (Blueprint $table) {
            if (!Schema::hasColumn('appointments', 'products_commission_amount')) {
                $table->decimal('products_commission_amount', 10, 2)->default(0.00)->after('products_total');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_sales');

        Schema::table('commission_payouts', function (Blueprint $table) {
            $table->dropColumn([
                'services_gross',
                'services_commission',
                'products_gross',
                'products_commission',
                'products_count',
            ]);
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['products_commission_amount']);
        });
    }
};

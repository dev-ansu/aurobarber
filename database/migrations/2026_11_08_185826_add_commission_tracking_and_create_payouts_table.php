<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabela de fechamentos de comissões (recibos de pagamento)
        Schema::create('commission_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barbershop_id')->constrained('barbershops')->cascadeOnDelete();
            $table->foreignId('professional_id')->constrained('professionals')->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->integer('appointments_count')->default(0);
            $table->decimal('total_gross', 10, 2)->default(0.00);
            $table->decimal('total_commission', 10, 2)->default(0.00);
            $table->decimal('barbershop_cut', 10, 2)->default(0.00);
            $table->string('payment_method')->default('pix'); // pix, dinheiro, transferencia
            $table->text('notes')->nullable();
            $table->timestamp('paid_at')->useCurrent();
            $table->timestamps();

            $table->index(['barbershop_id', 'professional_id']);
        });

        // 2. Colunas de controle na tabela appointments
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('commission_status')->default('pending')->after('commission_amount'); // pending, paid
            $table->foreignId('commission_payout_id')->nullable()->after('commission_status')->constrained('commission_payouts')->nullOnDelete();
            $table->timestamp('commission_paid_at')->nullable()->after('commission_payout_id');
            $table->timestamp('paid_at')->nullable()->after('payment_status');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['commission_payout_id']);
            $table->dropColumn(['commission_status', 'commission_payout_id', 'commission_paid_at', 'paid_at']);
        });

        Schema::dropIfExists('commission_payouts');
    }
};

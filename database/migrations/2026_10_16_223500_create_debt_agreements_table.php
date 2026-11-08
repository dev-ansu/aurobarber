<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('debt_agreements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->string('agreement_number', 50)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('total_original_amount', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0.00);
            $table->decimal('total_agreed_amount', 10, 2);
            $table->decimal('down_payment_amount', 10, 2)->default(0.00);
            $table->boolean('down_payment_paid')->default(false);
            $table->integer('installments_count')->default(1);
            $table->json('renegotiated_installment_ids')->nullable();
            $table->json('new_installment_ids')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('debt_agreements');
    }
};

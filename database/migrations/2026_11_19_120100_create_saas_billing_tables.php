<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('saas_billing_settings')) {
            Schema::create('saas_billing_settings', function (Blueprint $table): void {
                $table->id();
                $table->boolean('pix_enabled')->default(false);
                $table->string('pix_key')->nullable();
                $table->string('merchant_name', 25)->default('AUROSOFT');
                $table->string('merchant_city', 15)->default('FORTALEZA');
                $table->string('support_whatsapp', 30)->nullable();
                $table->text('payment_instructions')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('saas_payment_submissions')) {
            Schema::create('saas_payment_submissions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('barbershop_id')->constrained('barbershops')->cascadeOnDelete();
                $table->foreignId('saas_plan_id')->nullable()->constrained('saas_plans')->nullOnDelete();
                $table->string('submitted_by_name')->nullable();
                $table->string('submitted_by_email')->nullable();
                $table->decimal('amount', 10, 2);
                $table->string('proof_path');
                $table->string('status', 20)->default('pending')->index();
                $table->text('review_note')->nullable();
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();

                $table->index(['barbershop_id', 'status']);
            });
        }

        if (! Schema::hasTable('saas_payment_receipts')) {
            Schema::create('saas_payment_receipts', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('payment_submission_id')->nullable()->unique()->constrained('saas_payment_submissions')->nullOnDelete();
                $table->foreignId('barbershop_id')->constrained('barbershops')->cascadeOnDelete();
                $table->decimal('amount', 10, 2);
                $table->date('received_on');
                $table->date('competence');
                $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['barbershop_id', 'competence']);
            });
        }

        if (! Schema::hasTable('saas_plan_change_requests')) {
            Schema::create('saas_plan_change_requests', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('barbershop_id')->constrained('barbershops')->cascadeOnDelete();
                $table->foreignId('current_plan_id')->nullable()->constrained('saas_plans')->nullOnDelete();
                $table->foreignId('requested_plan_id')->constrained('saas_plans')->cascadeOnDelete();
                $table->string('requested_by_name')->nullable();
                $table->string('requested_by_email')->nullable();
                $table->string('status', 20)->default('pending')->index();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['barbershop_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('saas_plan_change_requests');
        Schema::dropIfExists('saas_payment_receipts');
        Schema::dropIfExists('saas_payment_submissions');
        Schema::dropIfExists('saas_billing_settings');
    }
};

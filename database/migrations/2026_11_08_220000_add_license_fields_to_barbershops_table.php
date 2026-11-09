<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('barbershops', function (Blueprint $table) {
            $table->string('license_status')->default('active')->after('subscription_status');
            $table->timestamp('license_expires_at')->nullable()->after('license_status');
            $table->unsignedInteger('grace_period_days')->default(5)->after('license_expires_at');
            $table->decimal('monthly_price', 10, 2)->default(120.00)->after('grace_period_days');
            $table->text('license_notes')->nullable()->after('monthly_price');
            $table->timestamp('suspended_at')->nullable()->after('license_notes');
            $table->string('suspension_reason')->nullable()->after('suspended_at');
        });

        // Atualiza todas as barbearias existentes com 30 dias de validade inicial e status ativo
        \Illuminate\Support\Facades\DB::table('barbershops')->update([
            'license_status' => 'active',
            'license_expires_at' => now()->addDays(30),
            'grace_period_days' => 5,
            'monthly_price' => 120.00,
        ]);
    }

    public function down(): void
    {
        Schema::table('barbershops', function (Blueprint $table) {
            $table->dropColumn([
                'license_status',
                'license_expires_at',
                'grace_period_days',
                'monthly_price',
                'license_notes',
                'suspended_at',
                'suspension_reason',
            ]);
        });
    }
};

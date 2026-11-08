<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_account_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('financial_account_id')->constrained('financial_accounts')->cascadeOnDelete();
            $table->enum('type', ['deposit', 'withdrawal', 'transfer_in', 'transfer_out', 'adjustment']);
            $table->decimal('amount', 12, 2);
            $table->decimal('balance_after', 12, 2);
            $table->string('description');
            $table->string('source_type')->nullable(); // 'cash_register_closing', 'cash_register_sangria', 'internal_transfer', 'manual_adjustment', 'initial_balance'
            $table->unsignedBigInteger('source_id')->nullable();
            $table->foreignId('related_account_id')->nullable()->constrained('financial_accounts')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['school_id', 'financial_account_id']);
            $table->index(['school_id', 'created_at']);
        });

        // Registrar permissões granulares no Spatie Shield
        $permissions = [
            'Transfer:FinancialAccount',
            'Statement:FinancialAccount',
            'Adjust:FinancialAccount',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_account_transactions');

        $permissions = [
            'Transfer:FinancialAccount',
            'Statement:FinancialAccount',
            'Adjust:FinancialAccount',
        ];

        foreach ($permissions as $permission) {
            Permission::where('name', $permission)->where('guard_name', 'web')->delete();
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabela de Contas Financeiras Internas da Escola (Cofre, Banco Cora, Banco do Brasil, etc.)
        if (! Schema::hasTable('financial_accounts')) {
            Schema::create('financial_accounts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained()->cascadeOnDelete();
                $table->string('name'); // Ex: "Cofre da Unidade", "Banco Cora PJ", "Banco do Brasil"
                $table->string('type', 30)->default('bank'); // 'vault' (cofre), 'bank' (bancária), 'other'
                $table->string('bank_name')->nullable();
                $table->string('agency_number', 30)->nullable();
                $table->string('account_number', 50)->nullable();
                $table->decimal('initial_balance', 12, 2)->default(0.00);
                $table->decimal('current_balance', 12, 2)->default(0.00);
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 2. Coluna destination_account_id na tabela cash_transactions para registrar destino de sangrias
        if (Schema::hasTable('cash_transactions') && ! Schema::hasColumn('cash_transactions', 'destination_account_id')) {
            Schema::table('cash_transactions', function (Blueprint $table) {
                $table->foreignId('destination_account_id')
                    ->nullable()
                    ->after('payment_method_id')
                    ->constrained('financial_accounts')
                    ->nullOnDelete();
            });
        }

        // 3. Permissões Zero Trust para Contas Financeiras e Kanban de Cobrança
        $permissions = [
            'ViewAny:FinancialAccount',
            'View:FinancialAccount',
            'Create:FinancialAccount',
            'Update:FinancialAccount',
            'Delete:FinancialAccount',
            'View:CollectionKanban',
            'page_CollectionKanban',
        ];

        foreach ($permissions as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        $superAdmin = Role::where('name', 'super_admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permissions);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('cash_transactions', 'destination_account_id')) {
            Schema::table('cash_transactions', function (Blueprint $table) {
                $table->dropForeign(['destination_account_id']);
                $table->dropColumn('destination_account_id');
            });
        }

        Schema::dropIfExists('financial_accounts');

        Permission::whereIn('name', [
            'ViewAny:FinancialAccount',
            'View:FinancialAccount',
            'Create:FinancialAccount',
            'Update:FinancialAccount',
            'Delete:FinancialAccount',
            'View:CollectionKanban',
            'page_CollectionKanban',
        ])->delete();
    }
};


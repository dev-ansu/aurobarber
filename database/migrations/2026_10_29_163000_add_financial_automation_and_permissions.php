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
        // 1. Campos de Automação Financeira na tabela schools
        Schema::table('schools', function (Blueprint $table) {
            if (! Schema::hasColumn('schools', 'late_fine_percentage')) {
                $table->decimal('late_fine_percentage', 5, 2)->default(2.00)->after('pix_enabled');
            }
            if (! Schema::hasColumn('schools', 'daily_interest_percentage')) {
                $table->decimal('daily_interest_percentage', 6, 4)->default(0.0333)->after('late_fine_percentage');
            }
            if (! Schema::hasColumn('schools', 'grace_days')) {
                $table->integer('grace_days')->default(0)->after('daily_interest_percentage');
            }
            if (! Schema::hasColumn('schools', 'punctuality_discount_enabled')) {
                $table->boolean('punctuality_discount_enabled')->default(false)->after('grace_days');
            }
            if (! Schema::hasColumn('schools', 'punctuality_discount_type')) {
                $table->string('punctuality_discount_type', 20)->default('fixed')->after('punctuality_discount_enabled');
            }
            if (! Schema::hasColumn('schools', 'punctuality_discount_value')) {
                $table->decimal('punctuality_discount_value', 10, 2)->default(0.00)->after('punctuality_discount_type');
            }
            if (! Schema::hasColumn('schools', 'punctuality_discount_days_tolerance')) {
                $table->integer('punctuality_discount_days_tolerance')->default(0)->after('punctuality_discount_value');
            }
        });

        // 2. Campos de Cancelamento, Auditoria e Pontualidade na tabela installments
        Schema::table('installments', function (Blueprint $table) {
            if (! Schema::hasColumn('installments', 'punctuality_discount_applied')) {
                $table->boolean('punctuality_discount_applied')->default(false)->after('discount');
            }
            if (! Schema::hasColumn('installments', 'notes')) {
                $table->text('notes')->nullable()->after('status');
            }
            if (! Schema::hasColumn('installments', 'cancellation_reason')) {
                $table->string('cancellation_reason', 255)->nullable()->after('notes');
            }
            if (! Schema::hasColumn('installments', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('cancellation_reason');
            }
            if (! Schema::hasColumn('installments', 'cancelled_by_id')) {
                $table->foreignId('cancelled_by_id')->nullable()->constrained('users')->nullOnDelete()->after('cancelled_at');
            }
        });

        // 3. Permissões Zero Trust no Spatie / Shield
        $permissions = [
            'ChargeWhatsApp:Installment',
            'Cancel:Installment',
            'CancelAny:Installment',
            'ApplyPunctualityDiscount:Installment',
            'WaiveFine:Installment',
            'View:FinancialSettings',
            'Update:FinancialSettings',
            'View:CashFlowForecast',
            'Export:CashFlowForecast',
            'page_CashFlowForecast',
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
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn([
                'late_fine_percentage',
                'daily_interest_percentage',
                'grace_days',
                'punctuality_discount_enabled',
                'punctuality_discount_type',
                'punctuality_discount_value',
                'punctuality_discount_days_tolerance',
            ]);
        });

        Schema::table('installments', function (Blueprint $table) {
            $table->dropForeign(['cancelled_by_id']);
            $table->dropColumn([
                'punctuality_discount_applied',
                'notes',
                'cancellation_reason',
                'cancelled_at',
                'cancelled_by_id',
            ]);
        });

        Permission::whereIn('name', [
            'ChargeWhatsApp:Installment',
            'Cancel:Installment',
            'CancelAny:Installment',
            'ApplyPunctualityDiscount:Installment',
            'WaiveFine:Installment',
            'View:FinancialSettings',
            'Update:FinancialSettings',
            'View:CashFlowForecast',
            'Export:CashFlowForecast',
            'page_CashFlowForecast',
        ])->delete();
    }
};

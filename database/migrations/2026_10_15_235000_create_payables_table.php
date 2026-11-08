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
        Schema::create('payables', function (Blueprint $table) {
            $table->id();
            $table->string('description');
            $table->string('supplier')->nullable();
            $table->foreignId('financial_type_id')->nullable()->constrained('financial_types')->nullOnDelete();
            $table->decimal('amount', 10, 2);
            $table->date('due_date');
            $table->date('payment_date')->nullable();
            $table->decimal('amount_paid', 10, 2)->nullable();
            $table->string('status')->default('pending'); // pending, paid, canceled
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();
            $table->foreignId('cash_register_id')->nullable()->constrained('cash_registers')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Permissões do módulo
        $permissions = [
            'ViewAny:Payable',
            'View:Payable',
            'Create:Payable',
            'Update:Payable',
            'Delete:Payable',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $superAdmin = Role::where('name', 'super_admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permissions);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payables');

        Permission::whereIn('name', [
            'ViewAny:Payable',
            'View:Payable',
            'Create:Payable',
            'Update:Payable',
            'Delete:Payable',
        ])->delete();
    }
};

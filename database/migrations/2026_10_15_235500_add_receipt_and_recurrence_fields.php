<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payables', function (Blueprint $table) {
            $table->string('receipt_path')->nullable()->after('notes');
            $table->boolean('is_recurring')->default(false)->after('receipt_path');
            $table->string('recurrence_period')->default('monthly')->after('is_recurring'); // weekly, monthly, bimonthly, yearly
            $table->unsignedInteger('recurrence_limit')->nullable()->after('recurrence_period');
            $table->string('recurrence_group_id')->nullable()->after('recurrence_limit')->index();
        });

        Schema::table('installments', function (Blueprint $table) {
            $table->string('receipt_path')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('payables', function (Blueprint $table) {
            $table->dropIndex(['recurrence_group_id']);
            $table->dropColumn([
                'receipt_path',
                'is_recurring',
                'recurrence_period',
                'recurrence_limit',
                'recurrence_group_id',
            ]);
        });

        Schema::table('installments', function (Blueprint $table) {
            $table->dropColumn(['receipt_path']);
        });
    }
};

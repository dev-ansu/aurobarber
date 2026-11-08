<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_registers', function (Blueprint $table) {
            $table->decimal('closing_balance', 10, 2)->nullable()->after('current_balance');
            $table->decimal('counted_cash', 10, 2)->nullable()->after('closing_balance');
            $table->decimal('difference', 10, 2)->nullable()->after('counted_cash');
            $table->text('closing_notes')->nullable()->after('difference');
        });
    }

    public function down(): void
    {
        Schema::table('cash_registers', function (Blueprint $table) {
            $table->dropColumn(['closing_balance', 'counted_cash', 'difference', 'closing_notes']);
        });
    }
};

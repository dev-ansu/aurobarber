<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            // Datas operacionais de encerramento
            $table->timestamp('finished_at')->nullable()->after('status');
            $table->timestamp('cancelled_at')->nullable()->after('finished_at');

            // Registra o motivo de rescisão/evasão para métricas de churn
            $table->string('cancellation_reason')->nullable()->after('cancelled_at');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn(['finished_at', 'cancelled_at', 'cancellation_reason']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('installments', function (Blueprint $table) {
            // Adiciona a chave estrangeira (pode permitir nulo temporariamente se já houver dados)
            $table->foreignId('financial_type_id')
                ->nullable()
                ->constrained('financial_types')
                ->nullOnDelete();
            $table->dropColumn('type');
        });
    }

    public function down(): void
    {
        Schema::table('installments', function (Blueprint $table) {
            $table->dropForeign(['financial_type_id']);
            $table->dropColumn('financial_type_id');
            $table->string('type')->nullable();
        });
    }
};

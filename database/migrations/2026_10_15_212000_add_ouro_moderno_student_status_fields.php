<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('people', function (Blueprint $table): void {
            $table->unsignedInteger('ouro_moderno_cod_int')->nullable()->unique();
        });

        Schema::table('contract_item_courses', function (Blueprint $table): void {
            $table->json('ouro_moderno_status')->nullable();
            $table->timestamp('ouro_moderno_status_checked_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('contract_item_courses', function (Blueprint $table): void {
            $table->dropColumn(['ouro_moderno_status', 'ouro_moderno_status_checked_at']);
        });

        Schema::table('people', function (Blueprint $table): void {
            $table->dropUnique(['ouro_moderno_cod_int']);
            $table->dropColumn('ouro_moderno_cod_int');
        });
    }
};

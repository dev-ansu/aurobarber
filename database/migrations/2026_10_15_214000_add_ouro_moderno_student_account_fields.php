<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('people', function (Blueprint $table): void {
            $table->string('ouro_moderno_usuario')->nullable();
            $table->timestamp('ouro_moderno_registered_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('people', function (Blueprint $table): void {
            $table->dropColumn(['ouro_moderno_usuario', 'ouro_moderno_registered_at']);
        });
    }
};

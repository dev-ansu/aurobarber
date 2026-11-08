<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Adicionar owner_id e parent_id (filiais/unidades) na tabela barbershops
        Schema::table('barbershops', function (Blueprint $table) {
            $table->foreignId('owner_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->after('owner_id')->constrained('barbershops')->nullOnDelete();
        });

        // 2. Criar tabela pivô barbershop_user para permitir que um usuário pertença ou gerencie múltiplas unidades
        Schema::create('barbershop_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barbershop_id')->constrained('barbershops')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role')->default('owner'); // owner, manager, barber, receptionist
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['barbershop_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barbershop_user');

        Schema::table('barbershops', function (Blueprint $table) {
            $table->dropForeign(['owner_id']);
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['owner_id', 'parent_id']);
        });
    }
};

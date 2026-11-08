<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->onDelete('cascade');

            // Permite vincular um curso isolado ou uma formação completa
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('formation_id')->nullable()->constrained()->nullOnDelete();

            $table->decimal('price', 10, 2); // Preço cobrado por este item neste contrato específico
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_items');
    }
};

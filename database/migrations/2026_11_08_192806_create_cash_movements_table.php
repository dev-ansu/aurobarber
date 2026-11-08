<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barbershop_id')->constrained('barbershops')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type'); // opening (fundo inicial), inflow (suprimento), outflow (sangria)
            $table->decimal('amount', 10, 2);
            $table->string('description');
            $table->date('date');
            $table->timestamps();

            $table->index(['barbershop_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_movements');
    }
};

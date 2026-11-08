<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('school_partners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('cpf', 14)->nullable();
            $table->string('role')->nullable();
            $table->decimal('ownership_percentage', 5, 2)->nullable();
            $table->boolean('legal_representative')->default(false);
            $table->timestamps();

            $table->index(['school_id', 'legal_representative']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_partners');
    }
};

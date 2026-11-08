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
        Schema::create('schools', function (Blueprint $table) {
            $table->bigInteger('id')->primary();
            $table->string('legal_name');
            $table->string('trade_name')->nullable();
            $table->string('cnpj', 18)->nullable()->unique();
            $table->string('state_registration')->nullable();
            $table->string('municipal_registration')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('website')->nullable();
            $table->string('postal_code', 9)->nullable();
            $table->string('street')->nullable();
            $table->string('number', 30)->nullable();
            $table->string('complement')->nullable();
            $table->string('neighborhood')->nullable();
            $table->string('city')->nullable();
            $table->char('state', 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schools');
    }
};

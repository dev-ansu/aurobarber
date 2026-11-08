<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barbershops', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique(); // ex: marinho -> barber.aurosoft.com.br/marinho
            $table->string('headline')->nullable(); // ex: Estilo, Tradição e Barboterapia
            $table->text('description')->nullable();
            $table->string('logo_url')->nullable();
            $table->string('banner_url')->nullable();
            $table->string('phone')->nullable();
            $table->string('whatsapp')->nullable(); // para notificações
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('instagram')->nullable();
            $table->string('brand_color')->default('#d97706'); // Âmbar/Dourado clássico de barbearia
            $table->json('opening_hours')->nullable(); // Horários de funcionamento
            $table->string('pix_key')->nullable();
            $table->string('subscription_status')->default('trial'); // trial, active, past_due, cancelled
            $table->string('subscription_plan')->default('pro');
            $table->string('mercadopago_preapproval_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barbershops');
    }
};

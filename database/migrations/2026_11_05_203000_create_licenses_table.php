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
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->string('client_name');
            $table->string('trade_name')->nullable();
            $table->string('cnpj', 20)->nullable()->index();
            $table->string('license_key', 64)->unique()->index();
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 30)->nullable();
            
            // Modalidade: 'cloud_saas' ou 'on_premise'
            $table->string('type', 30)->default('cloud_saas')->index();
            
            // Status: 'active', 'trial', 'suspended', 'cancelled', 'expired'
            $table->string('status', 30)->default('active')->index();
            
            $table->date('starts_at')->nullable();
            $table->date('expires_at')->nullable()->index();
            $table->unsignedInteger('grace_period_days')->default(5); // Dias de tolerância offline
            $table->decimal('monthly_price', 10, 2)->default(0.00);
            
            // Limites e restrições
            $table->unsignedInteger('max_schools')->default(1);
            $table->unsignedInteger('max_students')->nullable();
            $table->string('hardware_id')->nullable()->index(); // Fingerprint da máquina para On-Premise
            $table->string('schema_name', 64)->nullable()->index(); // Schema no PostgreSQL (se for Cloud)
            $table->boolean('is_demo')->default(false)->index();
            
            // Auditoria de checagem remota
            $table->timestamp('last_checked_at')->nullable();
            $table->string('last_ip', 45)->nullable();
            
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};


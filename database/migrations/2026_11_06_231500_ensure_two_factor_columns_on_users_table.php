<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Garante colunas de 2FA diretamente no PostgreSQL (independente de schema/search_path)
        DB::statement('ALTER TABLE users ADD COLUMN IF NOT EXISTS two_factor_enabled BOOLEAN DEFAULT FALSE');
        DB::statement('ALTER TABLE users ADD COLUMN IF NOT EXISTS two_factor_code VARCHAR(255) NULL');
        DB::statement('ALTER TABLE users ALTER COLUMN two_factor_code TYPE VARCHAR(255)');
        DB::statement('ALTER TABLE users ADD COLUMN IF NOT EXISTS two_factor_expires_at TIMESTAMP NULL');

        // 2. Garante a tabela de dispositivos confiáveis
        if (! Schema::hasTable('user_trusted_devices')) {
            Schema::create('user_trusted_devices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('device_token_hash', 64)->index();
                $table->string('name')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->index();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_trusted_devices');

        DB::statement('ALTER TABLE users DROP COLUMN IF EXISTS two_factor_enabled');
        DB::statement('ALTER TABLE users DROP COLUMN IF EXISTS two_factor_code');
        DB::statement('ALTER TABLE users DROP COLUMN IF EXISTS two_factor_expires_at');
    }
};

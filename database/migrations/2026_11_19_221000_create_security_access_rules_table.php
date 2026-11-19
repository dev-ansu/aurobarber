<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('security_access_rules')) {
            Schema::create('security_access_rules', function (Blueprint $table): void {
                $table->id();
                $table->string('type', 20)->index(); // 'whitelist' | 'blacklist'
                $table->string('ip_address', 45)->nullable()->index();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('device_fingerprint', 64)->nullable()->index();
                $table->string('label')->default('Regra de Acesso');
                $table->text('user_agent')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamp('expires_at')->nullable()->index();
                $table->timestamp('last_matched_at')->nullable();
                $table->unsignedInteger('match_count')->default(0);
                $table->text('notes')->nullable();
                $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('security_access_rules');
    }
};

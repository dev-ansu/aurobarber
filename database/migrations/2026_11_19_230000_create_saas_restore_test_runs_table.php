<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('saas_restore_test_runs')) {
            Schema::create('saas_restore_test_runs', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('backup_file');
                $table->string('target_schema', 64)->nullable()->index();
                $table->string('status', 20)->default('queued')->index(); // queued, running, ready, failed, cleaned
                $table->unsignedTinyInteger('progress')->default(0);
                $table->string('phase', 120)->default('Iniciando');
                $table->text('message')->nullable();
                $table->string('access_token_hash', 64)->nullable()->index();
                $table->text('access_url')->nullable();
                $table->timestamp('expires_at')->nullable()->index();

                // Auditoria Automatizada (Smoke Test)
                $table->string('automated_check_status', 20)->default('pending')->index(); // pending, passed, warning, failed
                $table->json('automated_check_results')->nullable();

                // Auditoria e Laudo Humano
                $table->string('human_validation_status', 20)->default('pending')->index(); // pending, approved, rejected
                $table->text('human_validation_notes')->nullable();
                $table->timestamp('human_validated_at')->nullable();
                $table->foreignId('human_validated_by_user_id')->nullable()->constrained('users')->nullOnDelete();

                $table->timestamp('started_at')->nullable();
                $table->timestamp('finished_at')->nullable();
                $table->timestamps();

                $table->index(['created_by_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('saas_restore_test_runs');
    }
};

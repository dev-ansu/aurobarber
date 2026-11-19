<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('security_events')) {
            Schema::create('security_events', function (Blueprint $table): void {
                $table->id();
                $table->string('tenant_schema', 63)->default('public');
                $table->string('event_type', 80);
                $table->string('severity', 16)->default('info'); // info, warning, critical
                $table->string('subject_hash', 64)->nullable();
                $table->string('user_id', 100)->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['tenant_schema', 'created_at']);
                $table->index(['event_type', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('security_events');
    }
};

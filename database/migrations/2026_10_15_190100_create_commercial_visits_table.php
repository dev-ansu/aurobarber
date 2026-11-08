<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commercial_visits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('commercial_lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('collaborator_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contract_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('visited_at')->index();
            $table->string('status', 30)->default('attended')->index();
            $table->string('purpose')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commercial_visits');
    }
};

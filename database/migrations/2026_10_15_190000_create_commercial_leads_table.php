<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commercial_leads', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->foreignId('person_id')->nullable()->constrained('people')->nullOnDelete();
            $table->date('birth_date')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('source')->nullable();
            $table->foreignId('formation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('collaborator_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 30)->default('new')->index();
            $table->timestamp('next_follow_up_at')->nullable()->index();
            $table->timestamp('converted_at')->nullable()->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commercial_leads');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('school_messages')) {
            Schema::create('school_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sender_school_id')->nullable()->constrained('schools')->nullOnDelete();
                $table->foreignId('recipient_school_id')->nullable()->constrained('schools')->nullOnDelete();
                $table->foreignId('sender_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('parent_id')->nullable()->constrained('school_messages')->cascadeOnDelete();
                $table->foreignId('contract_id')->nullable()->constrained('contracts')->nullOnDelete();
                $table->string('category', 40)->default('general')->index();
                $table->string('subject');
                $table->text('body');
                $table->timestamp('read_at')->nullable();
                $table->foreignId('read_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['recipient_school_id', 'read_at']);
                $table->index(['sender_school_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('school_messages');
    }
};

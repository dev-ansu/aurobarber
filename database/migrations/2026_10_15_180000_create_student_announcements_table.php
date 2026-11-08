<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_announcements', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 160);
            $table->text('body')->nullable();
            $table->string('image_path')->nullable();
            $table->string('external_url', 2048)->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_announcements');
    }
};

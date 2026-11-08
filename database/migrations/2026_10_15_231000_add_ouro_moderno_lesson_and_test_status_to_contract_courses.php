<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contract_item_courses', function (Blueprint $table): void {
            $table->json('ouro_moderno_lesson_status')->nullable();
            $table->timestamp('ouro_moderno_lesson_status_checked_at')->nullable();
            $table->json('ouro_moderno_test_status')->nullable();
            $table->timestamp('ouro_moderno_test_status_checked_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('contract_item_courses', function (Blueprint $table): void {
            $table->dropColumn([
                'ouro_moderno_lesson_status',
                'ouro_moderno_lesson_status_checked_at',
                'ouro_moderno_test_status',
                'ouro_moderno_test_status_checked_at',
            ]);
        });
    }
};

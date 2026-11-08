<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_boards', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('color')->default('primary');
            $table->string('icon')->default('heroicon-o-view-columns');
            $table->boolean('is_default')->default(false);
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('task_columns', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_board_id')->constrained('task_boards')->cascadeOnDelete();
            $table->string('name');
            $table->string('color')->default('gray');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_done_column')->default(false);
            $table->timestamps();
        });

        Schema::create('task_cards', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_board_id')->constrained('task_boards')->cascadeOnDelete();
            $table->foreignId('task_column_id')->constrained('task_columns')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('priority')->default('medium'); // low, medium, high, urgent
            $table->string('sector')->nullable(); // pedagogico, administrativo, comercial, financeiro, gestao, geral
            $table->date('due_date')->nullable();
            $table->boolean('assigned_to_all')->default(false);
            $table->integer('sort_order')->default(0);
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('task_card_user', function (Blueprint $table): void {
            $table->foreignId('task_card_id')->constrained('task_cards')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->primary(['task_card_id', 'user_id']);
        });

        Schema::create('task_checklists', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_card_id')->constrained('task_cards')->cascadeOnDelete();
            $table->string('title');
            $table->boolean('is_completed')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_checklists');
        Schema::dropIfExists('task_card_user');
        Schema::dropIfExists('task_cards');
        Schema::dropIfExists('task_columns');
        Schema::dropIfExists('task_boards');
    }
};

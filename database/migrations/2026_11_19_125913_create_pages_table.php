<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql'
            && DB::selectOne('SELECT current_schema() AS schema')->schema !== 'public') {
            return;
        }

        Schema::create('pages', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique()->index();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();
            $table->string('og_image')->nullable();
            $table->json('content')->nullable();
            $table->boolean('is_published')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql'
            && DB::selectOne('SELECT current_schema() AS schema')->schema !== 'public') {
            return;
        }

        Schema::dropIfExists('pages');
    }
};

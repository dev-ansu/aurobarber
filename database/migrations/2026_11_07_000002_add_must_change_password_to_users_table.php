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
        // Garante a coluna must_change_password diretamente no PostgreSQL para funcionar em qualquer schema
        DB::statement('ALTER TABLE users ADD COLUMN IF NOT EXISTS must_change_password BOOLEAN DEFAULT FALSE');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE users DROP COLUMN IF EXISTS must_change_password');
    }
};

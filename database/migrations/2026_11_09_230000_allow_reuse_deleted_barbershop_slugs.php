<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->ensureSupportedDriver();

        Schema::table('barbershops', function (Blueprint $table): void {
            $table->dropUnique('barbershops_slug_unique');
        });

        DB::statement('CREATE UNIQUE INDEX barbershops_slug_unique ON barbershops (slug) WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        $this->ensureSupportedDriver();

        Schema::table('barbershops', function (Blueprint $table): void {
            $table->dropIndex('barbershops_slug_unique');
            $table->unique('slug');
        });
    }

    private function ensureSupportedDriver(): void
    {
        if (! in_array(DB::getDriverName(), ['pgsql', 'sqlite'], true)) {
            throw new RuntimeException('Reusing deleted barbershop slugs requires PostgreSQL or SQLite.');
        }
    }
};

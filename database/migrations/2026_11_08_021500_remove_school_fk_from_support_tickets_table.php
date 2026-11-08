<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            DB::statement("ALTER TABLE public.support_tickets DROP CONSTRAINT IF EXISTS support_tickets_school_id_foreign");
        } catch (\Throwable) {
            // Ignora caso a constraint já tenha sido removida
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};

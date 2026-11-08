<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Cria um índice único restrito apenas a caixas com status 'open' no Postgres
        DB::statement("
            CREATE UNIQUE INDEX unique_user_open_cash_register 
            ON cash_registers (user_id) 
            WHERE status = 'open';
        ");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS unique_user_open_cash_register;');
    }
};

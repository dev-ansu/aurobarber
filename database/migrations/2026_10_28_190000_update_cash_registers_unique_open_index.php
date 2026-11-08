<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP INDEX IF EXISTS unique_user_open_cash_register;');

        DB::statement("
            CREATE UNIQUE INDEX unique_user_open_cash_register 
            ON cash_registers (user_id, school_id) 
            WHERE status = 'open';
        ");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS unique_user_open_cash_register;');

        DB::statement("
            CREATE UNIQUE INDEX unique_user_open_cash_register 
            ON cash_registers (user_id) 
            WHERE status = 'open';
        ");
    }
};


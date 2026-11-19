<?php

declare(strict_types=1);

use App\Models\Page;
use Database\Seeders\CmsInitialContentSeeder;
use Illuminate\Database\Migrations\Migration;
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

        if (Schema::hasTable('pages')) {
            try {
                (new CmsInitialContentSeeder())->run();
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    public function down(): void
    {
        // Não remove a página no rollback por segurança
    }
};

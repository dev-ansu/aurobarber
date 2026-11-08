<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Se estiver explicitamente em produção E o banco for aurobarber_prod,
        // roda estritamente o seeder limpo de produção.
        // Caso contrário (desenvolvimento / staging / testes no dev), popula com dados ricos de teste.
        if (config('app.env') === 'production' && config('database.connections.pgsql.database') === 'aurobarber_prod') {
            $this->call(ProductionSeeder::class);
        } else {
            $this->call(DevFakeDataSeeder::class);
        }
    }
}

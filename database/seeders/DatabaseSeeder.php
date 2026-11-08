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
        // 1º: Cria as roles e permissões estruturais do Filament/Spatie
        if ($this->command) {
            $this->command->call('shield:generate', ['--all' => true]);
        } else {
            \Illuminate\Support\Facades\Artisan::call('shield:generate', ['--all' => true]);
        }

        $this->call([

            AttendanceStaffRolesSeeder::class,
            UserSeeder::class,
            PaymentMethodSeeder::class,
            FinancialTypeSeeder::class,
            ChartOfAccountsSeeder::class,
            LocationSeeder::class,
            ContractHistoryTypeSeeder::class,
            TypingExerciseSeeder::class,
        ]);
    }
}

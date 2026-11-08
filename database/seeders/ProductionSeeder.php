<?php

namespace Database\Seeders;

use App\Models\Barbershop;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class ProductionSeeder extends Seeder
{
    /**
     * Seeder limpo para ambiente de Produção.
     * Cria APENAS o administrador master, papéis fundamentais e a Unidade Matriz do SaaS.
     * ZERO dados fictícios, ZERO clientes ou agendamentos de teste.
     */
    public function run(): void
    {
        // 1. Roles fundamentais do sistema
        $roles = [
            'super_admin',
            'barbershop_owner',
            'barbershop_manager',
            'barber',
            'receptionist',
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        // 2. Administrador Master Oficial do SaaS (Aurosoft)
        $masterAdmin = User::updateOrCreate(
            ['email' => 'dev.andersondesouzafeitosa@gmail.com'],
            [
                'name' => 'Anderson Feitosa (SaaS Master)',
                'password' => Hash::make('AuroDev@2026!#Temp'),
                'role' => 'master_admin',
                'must_change_password' => true,
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $masterAdmin->assignRole('super_admin');

        // 3. Unidade Matriz Oficial do SaaS (Âncora de Tenancy)
        $matriz = Barbershop::updateOrCreate(
            ['slug' => 'matriz'],
            [
                'name' => 'AuroBarber - Matriz',
                'owner_id' => $masterAdmin->id,
                'headline' => 'Unidade Matriz Oficial • Central SaaS AuroBarber',
                'city' => 'Fortaleza / CE',
                'is_active' => true,
                'brand_color' => '#d97706',
                'opening_hours' => [
                    'days' => ['mon', 'tue', 'wed', 'thu', 'fri', 'sat'],
                    'start_time' => '08:00',
                    'end_time' => '19:30',
                    'slot_interval' => 30,
                ],
            ]
        );

        $matriz->users()->syncWithoutDetaching([$masterAdmin->id]);
    }
}

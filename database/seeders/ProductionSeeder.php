<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class ProductionSeeder extends Seeder
{
    /**
     * Seeder limpo para ambiente de Produção.
     * Cria APENAS o administrador master e papéis fundamentais.
     * ZERO dados fictícios, ZERO barbearias de teste.
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
    }
}

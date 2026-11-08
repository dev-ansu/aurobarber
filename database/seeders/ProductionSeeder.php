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

        // 2. Administrador Master do SaaS (Aurosoft)
        $admin = User::firstOrCreate(
            ['email' => 'admin@aurobarber.com.br'],
            [
                'name' => 'Admin AuroBarber',
                'password' => Hash::make('AuroBarber@2026!'),
                'role' => 'master_admin',
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        $admin->assignRole('super_admin');
    }
}

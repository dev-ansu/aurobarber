<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use RuntimeException;
use Spatie\Permission\Models\Role;

class ProductionSeeder extends Seeder
{
    /**
     * Seeder limpo para ambiente de Produção.
     * Cria apenas o administrador master e os papéis fundamentais do SaaS.
     * ZERO dados fictícios, ZERO clientes ou agendamentos de teste.
     */
    public function run(): void
    {
        $masterName = (string) config('services.saas_master.name');
        $masterEmail = (string) config('services.saas_master.email');
        $masterPassword = (string) config('services.saas_master.password');

        if (blank($masterName) || ! filter_var($masterEmail, FILTER_VALIDATE_EMAIL) || blank($masterPassword)) {
            throw new RuntimeException('Defina SAAS_MASTER_NAME, SAAS_MASTER_EMAIL e SAAS_MASTER_PASSWORD antes de executar o seeder de produção.');
        }

        Validator::make(
            ['password' => $masterPassword],
            ['password' => [Password::min(12)->letters()->mixedCase()->numbers()->symbols()]],
        )->validate();

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

        $masterAdmin = User::updateOrCreate(
            ['email' => $masterEmail],
            [
                'name' => $masterName,
                'password' => Hash::make($masterPassword),
                'role' => 'master_admin',
                'must_change_password' => true,
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $masterAdmin->assignRole('super_admin');
    }
}

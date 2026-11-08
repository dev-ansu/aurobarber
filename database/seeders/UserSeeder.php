<?php

namespace Database\Seeders;

use App\Models\Person;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);

        // Opcional: Se quiser que o super_admin tenha TODAS as permissões existentes
        // Isso é útil se você já gerou permissões via filament-shield
        $allPermissions = Permission::all();
        $superAdmin->syncPermissions($allPermissions);

        // Cria ou busca a Pessoa (Admin)
        $person = Person::withoutGlobalScopes()->firstOrCreate([
            'name' => 'Admin',
            'email' => 'admin@admin.com',
        ]);

        // Cria ou busca o Usuário
        $user = User::firstOrCreate(
            ['email' => $person->email], // Busca pelo email para evitar duplicação
            [
                'person_id' => $person->id,
                'password' => bcrypt('123'),
            ]
        );

        // Atribui a função super_admin ao usuário
        $user->assignRole('super_admin');

    }
}

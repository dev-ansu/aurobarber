<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\License;
use App\Models\Person;
use App\Models\School;
use App\Models\User;
use Database\Seeders\AttendanceStaffRolesSeeder;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\ContractHistoryTypeSeeder;
use Database\Seeders\FinancialTypeSeeder;
use Database\Seeders\LocationSeeder;
use Database\Seeders\PaymentMethodSeeder;
use Database\Seeders\TypingExerciseSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class TenantBaselineSeeder extends Seeder
{
    public function __construct(
        protected License $license,
        protected array $options = []
    ) {}

    /**
     * Executa a carga inicial limpa de um novo cliente/escola no seu schema isolado.
     */
    public function run(): void
    {
        // 1. Seeders estruturais de tabelas de domínio
        $this->call([
            AttendanceStaffRolesSeeder::class,
            PaymentMethodSeeder::class,
            FinancialTypeSeeder::class,
            ChartOfAccountsSeeder::class,
            LocationSeeder::class,
            ContractHistoryTypeSeeder::class,
            TypingExerciseSeeder::class,
        ]);

        // 2. Garante o papel de Super Administrador e permissões
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $allPermissions = Permission::all();
        if ($allPermissions->isNotEmpty()) {
            $superAdmin->syncPermissions($allPermissions);
        }

        // 3. Cria a Escola Principal do Tenant com base nos dados da Licença
        $schoolTrade = $this->license->trade_name ?: $this->license->client_name;
        $schoolLegal = $this->license->client_name;

        $school = School::query()->withoutGlobalScopes()->updateOrCreate(
            ['id' => 1],
            [
                'trade_name' => $schoolTrade,
                'legal_name' => $schoolLegal,
                'is_headquarters' => true,
                'cnpj' => $this->license->cnpj,
                'email' => $this->license->contact_email,
                'phone' => $this->license->contact_phone,
                'postal_code' => $this->license->postal_code ?: '60000-000',
                'street' => $this->license->street ?: 'Rua Principal',
                'number' => $this->license->number ?: '100',
                'complement' => $this->license->complement,
                'neighborhood' => $this->license->neighborhood ?: 'Centro',
                'city' => $this->license->city ?: 'Fortaleza',
                'state' => $this->license->state ?: 'CE',
                'pix_enabled' => false,
                'late_fine_percentage' => 2.0,
                'daily_interest_percentage' => 0.033,
                'grace_days' => 5,
            ]
        );

        // 4. Cria o Administrador Principal do Tenant com senha provisória
        $adminName = $this->options['admin_name'] ?? $this->license->contact_name ?? 'Administrador';
        $adminEmail = $this->options['admin_email'] ?? $this->license->contact_email ?? ('admin@' . ($this->license->slug ?: 'escola') . '.com.br');
        $rawPassword = $this->options['admin_password'] ?? '12345678';

        $person = Person::query()->withoutGlobalScopes()->create([
            'name' => $adminName,
            'email' => $adminEmail,
            'phone' => $this->license->contact_phone,
        ]);

        $user = User::query()->withoutGlobalScopes()->create([
            'person_id' => $person->id,
            'email' => $adminEmail,
            'password' => Hash::make($rawPassword),
            'panel_access' => true,
            'is_developer' => false,
            'must_change_password' => true, // Exige troca de senha no 1º acesso
        ]);

        $user->assignRole($superAdmin);
        $user->schools()->syncWithoutDetaching([$school->id]);
    }
}

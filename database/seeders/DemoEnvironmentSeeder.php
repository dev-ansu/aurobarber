<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\GenderEnum;
use App\Enums\RelationshipEnum;
use App\Models\CashRegister;
use App\Models\CashTransaction;
use App\Models\CommercialLead;
use App\Models\FinancialAccount;
use App\Models\Person;
use App\Models\School;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DemoEnvironmentSeeder extends Seeder
{
    /**
     * Popula o schema de demonstração com dados realistas prontos para apresentação a clientes.
     */
    public function run(): void
    {
        // 1. Executa seeders estruturais essenciais
        $this->call([
            AttendanceStaffRolesSeeder::class,
            PaymentMethodSeeder::class,
            FinancialTypeSeeder::class,
            ChartOfAccountsSeeder::class,
            LocationSeeder::class,
            ContractHistoryTypeSeeder::class,
            TypingExerciseSeeder::class,
        ]);

        // 2. Garante Role Super Admin e Permissões
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $allPermissions = Permission::all();
        if ($allPermissions->isNotEmpty()) {
            $superAdmin->syncPermissions($allPermissions);
        }

        // 3. Cria a Escola Modelo de Demonstração
        $school = School::query()->withoutGlobalScopes()->updateOrCreate(
            ['id' => 1],
            [
                'trade_name' => 'Aurosoft Academy - Unidade Modelo',
                'legal_name' => 'Aurosoft Educação e Tecnologia Ltda',
                'is_headquarters' => true,
                'cnpj' => '12.345.678/0001-90',
                'email' => 'contato@aurosoft.com.br',
                'phone' => '(11) 98765-4321',
                'postal_code' => '01310-100',
                'street' => 'Avenida Paulista',
                'number' => '1000',
                'neighborhood' => 'Bela Vista',
                'city' => 'São Paulo',
                'state' => 'SP',
                'pix_enabled' => true,
                'pix_key' => 'financeiro@aurosoft.com.br',
                'pix_key_type' => 'email',
                'pix_merchant_name' => 'Aurosoft Academy Demo',
                'pix_merchant_city' => 'São Paulo',
                'late_fine_percentage' => 2.0,
                'daily_interest_percentage' => 0.033,
                'grace_days' => 5,
            ]
        );

        // 4. Cria a Pessoa e Usuário Super Admin Demo
        $person = Person::withoutGlobalScopes()->updateOrCreate(
            ['email' => 'demo@aurosoft.com.br'],
            [
                'name' => 'Diretor de Demonstração',
                'phone' => '(11) 98765-4321',
            ]
        );

        $demoUser = User::query()->updateOrCreate(
            ['email' => 'demo@aurosoft.com.br'],
            [
                'person_id' => $person->id,
                'password' => Hash::make('demo123'),
                'panel_access' => true,
                'is_developer' => false,
            ]
        );

        $demoUser->syncRoles(['super_admin']);
        $school->users()->syncWithoutDetaching([$demoUser->id]);

        // 5. Popula Cursos, Formações, Contratos e Alunos via ActiveContractSeeder
        $this->call(ActiveContractSeeder::class);

        // 6. Cria Leads Comerciais Realistas no Funil CRM
        $this->seedCommercialLeads($school);

        // 7. Cria Conta Financeira e Caixa Operacional para o Dashboard Financeiro
        $this->seedCashAndFinance($school, $demoUser);
    }

    private function seedCommercialLeads(School $school): void
    {
        $leads = [
            [
                'name' => 'Lucas Ferreira',
                'phone' => '(11) 97111-2233',
                'email' => 'lucas.ferreira@exemplo.com',
                'source' => 'whatsapp',
                'status' => 'new',
                'notes' => 'Entrou em contato pelo WhatsApp interessado no curso de Informática e Programação.',
            ],
            [
                'name' => 'Camila Rodrigues',
                'phone' => '(11) 98222-3344',
                'email' => 'camila.rodrigues@exemplo.com',
                'source' => 'instagram',
                'status' => 'contacted',
                'next_follow_up_at' => Carbon::now()->addDay()->setTime(14, 0),
                'notes' => 'Agendou atendimento para conhecer a metodologia de ensino e horários disponíveis.',
            ],
            [
                'name' => 'Gabriel Martins',
                'phone' => '(11) 99333-4455',
                'email' => 'gabriel.martins@exemplo.com',
                'source' => 'google',
                'status' => 'scheduled_visit',
                'next_follow_up_at' => Carbon::now()->addDays(2)->setTime(16, 30),
                'notes' => 'Visita presencial agendada para testar o laboratório e os módulos práticos.',
            ],
            [
                'name' => 'Beatriz Santos',
                'phone' => '(11) 96444-5566',
                'email' => 'beatriz.santos@exemplo.com',
                'source' => 'indication',
                'status' => 'converted',
                'converted_at' => Carbon::now()->subDays(3),
                'notes' => 'Matrícula efetuada com sucesso! Aluna indicada por Ana Souza.',
            ],
        ];

        foreach ($leads as $leadData) {
            CommercialLead::withoutGlobalScopes()->updateOrCreate(
                [
                    'school_id' => $school->id,
                    'phone' => $leadData['phone'],
                ],
                array_merge($leadData, ['school_id' => $school->id])
            );
        }
    }

    private function seedCashAndFinance(School $school, User $operator): void
    {
        // Conta Bancária / Caixa Geral
        $account = FinancialAccount::withoutGlobalScopes()->firstOrCreate(
            [
                'school_id' => $school->id,
                'name' => 'Caixa Operacional Balcão',
            ],
            [
                'account_type' => 'cash',
                'initial_balance' => 500.00,
                'current_balance' => 1250.00,
                'is_active' => true,
            ]
        );

        // Caixa Aberto Hoje
        $cashRegister = CashRegister::withoutGlobalScopes()->firstOrCreate(
            [
                'school_id' => $school->id,
                'name' => 'Caixa Principal 01',
                'status' => 'open',
            ],
            [
                'opening_balance' => 500.00,
                'current_balance' => 1250.00,
                'user_id' => $operator->id,
                'opened_at' => Carbon::now()->startOfDay()->addHours(8),
            ]
        );

        // Transação de Entrada e Saída Demonstrativas
        CashTransaction::withoutGlobalScopes()->firstOrCreate(
            [
                'cash_register_id' => $cashRegister->id,
                'description' => 'Recebimento Matrícula à Vista (Ana Souza)',
            ],
            [
                'user_id' => $operator->id,
                'type' => 'input',
                'amount' => 800.00,
                'created_at' => Carbon::now()->subHours(2),
            ]
        );

        CashTransaction::withoutGlobalScopes()->firstOrCreate(
            [
                'cash_register_id' => $cashRegister->id,
                'description' => 'Compra de Material de Apoio e Papelaria',
            ],
            [
                'user_id' => $operator->id,
                'type' => 'output',
                'amount' => 50.00,
                'created_at' => Carbon::now()->subHour(),
            ]
        );
    }
}

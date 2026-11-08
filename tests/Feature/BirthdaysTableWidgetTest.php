<?php

use App\Enums\ContactTypeEnum;
use App\Enums\RelationshipEnum;
use App\Filament\Widgets\BirthdaysTableWidget;
use App\Models\Collaborator;
use App\Models\Contact;
use App\Models\Contract;
use App\Models\Person;
use App\Models\Responsible;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('birthdays table widget lists birthdays of the month and highlights birthdays of today', function () {
    Carbon::setTestNow('2026-10-16 10:00:00');

    $adminRole = Role::findOrCreate('super_admin', 'web');
    $adminPerson = Person::create([
        'name' => 'Admin Teste',
        'email' => 'admin@example.com',
    ]);
    $adminUser = User::create([
        'person_id' => $adminPerson->id,
        'email' => 'admin@example.com',
        'password' => 'secret',
    ]);
    $adminUser->assignRole($adminRole);
    $this->actingAs($adminUser);

    $school = School::create([
        'legal_name' => 'Escola Modelo Ltda',
        'trade_name' => 'Aurosoft Academy',
        'cnpj' => '12.345.678/0001-90',
        'email' => 'contato@escola.com',
    ]);

    // Aniversariante de Hoje (16/10)
    $todayPerson = Person::create([
        'name' => 'Carlos Silva Hoje',
        'email' => 'carlos.hoje@example.com',
        'birth_date' => '1996-10-16', // completa 30 anos em 2026
    ]);
    Contact::create([
        'person_id' => $todayPerson->id,
        'type' => ContactTypeEnum::WhatsApp,
        'value' => '(11) 98888-7777',
        'is_primary' => true,
    ]);
    $respPerson = Person::create([
        'name' => 'Mãe Silva',
        'email' => 'mae@example.com',
    ]);
    $resp = Responsible::create([
        'person_id' => $respPerson->id,
        'relationship' => RelationshipEnum::Mother,
        'financial_responsible' => true,
    ]);

    Contract::create([
        'contract_number' => 'CTR-2026-0001',
        'student_id' => $todayPerson->id,
        'responsible_id' => $resp->id,
        'status' => 'active',
        'start_date' => '2026-01-01',
        'total_value' => 1200,
    ]);

    // Aniversariante no final do mês (25/10)
    $laterPerson = Person::create([
        'name' => 'Mariana Souza Fim',
        'email' => 'mariana.fim@example.com',
        'birth_date' => '2000-10-25', // completa 26 anos
    ]);
    Collaborator::create([
        'person_id' => $laterPerson->id,
        'role' => 'Instrutora de Informática',
        'admission_date' => '2025-02-01',
    ]);

    // Aniversariante de outro mês (15/12)
    $decemberPerson = Person::create([
        'name' => 'Pedro Dezembro',
        'email' => 'pedro.dez@example.com',
        'birth_date' => '1998-12-15',
    ]);

    expect(BirthdaysTableWidget::canView())->toBeTrue();

    // 1. Testa listagem padrão (mês atual: Outubro)
    Livewire::test(BirthdaysTableWidget::class)
        ->assertSee('Carlos Silva Hoje')
        ->assertSee('🎉 HOJE!')
        ->assertSee('Aluno Ativo')
        ->assertSee('30 anos')
        ->assertSee('(11) 98888-7777')
        ->assertSee('Mariana Souza Fim')
        ->assertSee('Colaborador')
        ->assertSee('26 anos')
        ->assertDontSee('Pedro Dezembro');

    // 2. Testa filtro apenas Hoje
    Livewire::test(BirthdaysTableWidget::class)
        ->filterTable('period', 'today')
        ->assertSee('Carlos Silva Hoje')
        ->assertDontSee('Mariana Souza Fim');

    // 3. Testa pesquisa por nome
    Livewire::test(BirthdaysTableWidget::class)
        ->searchTable('Mariana')
        ->assertSee('Mariana Souza Fim')
        ->assertDontSee('Carlos Silva Hoje');

    // 4. Testa filtro por perfil
    Livewire::test(BirthdaysTableWidget::class)
        ->filterTable('profile', 'collaborator')
        ->assertSee('Mariana Souza Fim')
        ->assertDontSee('Carlos Silva Hoje');

    // 5. Testa filtro por mês específico (Dezembro = 12)
    Livewire::test(BirthdaysTableWidget::class)
        ->filterTable('period', 'all_year')
        ->filterTable('specific_month', '12')
        ->assertSee('Pedro Dezembro')
        ->assertDontSee('Carlos Silva Hoje')
        ->assertDontSee('Mariana Souza Fim');
});

test('birthdays table widget supports pagination with many records', function () {
    Carbon::setTestNow('2026-10-16 10:00:00');

    $adminRole = Role::findOrCreate('super_admin', 'web');
    $adminPerson = Person::create([
        'name' => 'Admin Paginacao',
        'email' => 'admin.pag@example.com',
    ]);
    $adminUser = User::create([
        'person_id' => $adminPerson->id,
        'email' => 'admin.pag@example.com',
        'password' => 'secret',
    ]);
    $adminUser->assignRole($adminRole);
    $this->actingAs($adminUser);

    // Cria 12 pessoas fazendo aniversário no mês 10
    foreach (range(1, 12) as $i) {
        $day = str_pad((string) min($i * 2, 28), 2, '0', STR_PAD_LEFT);
        Person::create([
            'name' => "Aniversariante {$i} Pag",
            'email' => "aniv{$i}@example.com",
            'birth_date' => "1990-10-{$day}",
        ]);
    }

    // Com paginação padrão de 5 itens por página:
    $test = Livewire::test(BirthdaysTableWidget::class);

    // Página 1 contém os primeiros itens ordenados por dia
    $test->assertSee('Aniversariante 1 Pag')
        ->assertSee('Aniversariante 2 Pag')
        ->assertSee('Aniversariante 3 Pag')
        ->assertSee('Aniversariante 4 Pag')
        ->assertSee('Aniversariante 5 Pag')
        ->assertDontSee('Aniversariante 12 Pag');

    // Navega para página 3
    $test->set('tableRecordsPerPage', 5)
        ->call('gotoPage', 3)
        ->assertSee('Aniversariante 12 Pag')
        ->assertDontSee('Aniversariante 1 Pag');
});

<?php

use App\Filament\Pages\OuroModernoLmsStudents;
use App\Models\Person;
use App\Models\Responsible;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $person = Person::create(['name' => 'Admin Teste', 'email' => 'admin@teste.com']);
    $user = User::create([
        'person_id' => $person->id,
        'name' => 'Admin Teste',
        'email' => 'admin@teste.com',
        'password' => bcrypt('password'),
    ]);
    $user->assignRole($role);
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

test('importAndCreateContract creates person and responsible from ouro moderno cached student and redirects to contract', function () {
    $page = new OuroModernoLmsStudents();

    $cacheKey = 'ouro-moderno:lms-students:'.auth()->id();
    Cache::put($cacheKey, [
        [
            'cod_int' => '7890',
            'id_aluno' => '100',
            'name' => 'Aluno Importado Ouro',
            'email' => 'aluno.ouro@exemplo.com',
            'username' => 'alunoouro',
            'birth_date' => '2005-04-12',
            'active' => 'S',
            'start_date' => '2024-01-15',
            'end_date' => null,
        ],
    ], now()->addMinutes(20));

    try {
        $page->importAndCreateContract('7890', true);
    } catch (\Throwable $e) {
        // Redirecionamento disparado
    }

    $person = Person::where('ouro_moderno_cod_int', 7890)->first();
    expect($person)->not->toBeNull()
        ->and($person->name)->toBe('Aluno Importado Ouro')
        ->and($person->email)->toBe('aluno.ouro@exemplo.com')
        ->and($person->ouro_moderno_usuario)->toBe('alunoouro')
        ->and($person->birth_date->format('Y-m-d'))->toBe('2005-04-12');

    $responsible = Responsible::where('person_id', $person->id)->first();
    expect($responsible)->not->toBeNull()
        ->and($responsible->financial_responsible)->toBeTrue();
});

test('importAndStartEnrollment creates person from LMS and redirects to edit person with relation managers', function () {
    $page = new OuroModernoLmsStudents();

    $cacheKey = 'ouro-moderno:lms-students:'.auth()->id();
    Cache::put($cacheKey, [
        [
            'cod_int' => '7891',
            'id_aluno' => '101',
            'name' => 'Aluno Fluxo Nativo',
            'email' => 'aluno.nativo@exemplo.com',
            'username' => 'alunonativo',
            'birth_date' => '2004-06-20',
            'active' => 'S',
            'start_date' => '2024-02-01',
            'end_date' => null,
        ],
    ], now()->addMinutes(20));

    try {
        $page->importAndStartEnrollment('7891');
    } catch (\Throwable $e) {
        // Redirecionamento disparado
    }

    $person = Person::where('ouro_moderno_cod_int', 7891)->first();
    expect($person)->not->toBeNull()
        ->and($person->name)->toBe('Aluno Fluxo Nativo')
        ->and($person->email)->toBe('aluno.nativo@exemplo.com')
        ->and($person->ouro_moderno_usuario)->toBe('alunonativo')
        ->and($person->birth_date->format('Y-m-d'))->toBe('2004-06-20');
});

test('status filter in OuroModernoLmsStudents filters active and inactive students', function () {
    $page = new OuroModernoLmsStudents();

    $cacheKey = 'ouro-moderno:lms-students:'.auth()->id();
    Cache::put($cacheKey, [
        ['cod_int' => '1', 'name' => 'Aluno Ativo 1', 'active' => 'S'],
        ['cod_int' => '2', 'name' => 'Aluno Inativo 1', 'active' => 'N'],
        ['cod_int' => '3', 'name' => 'Aluno Ativo 2', 'active' => '1'],
        ['cod_int' => '4', 'name' => 'Aluno Inativo 2', 'active' => '0'],
    ], now()->addMinutes(20));

    // Default statusFilter is 'active'
    $page->statusFilter = 'active';
    expect($page->filteredStudents()->total())->toBe(2);

    $page->statusFilter = 'inactive';
    expect($page->filteredStudents()->total())->toBe(2);

    $page->statusFilter = 'all';
    expect($page->filteredStudents()->total())->toBe(4);
});

test('can link any course to a contract formation even if the course does not belong to that formation', function () {
    $person = Person::create(['name' => 'Aluno Custom']);
    $responsible = Responsible::create([
        'person_id' => $person->id,
        'financial_responsible' => true,
        'relationship' => \App\Enums\RelationshipEnum::Legal_guardian,
    ]);

    $contract = \App\Models\Contract::create([
        'contract_number' => 'CUST-001',
        'student_id' => $person->id,
        'responsible_id' => $responsible->id,
        'status' => 'draft',
        'start_date' => now(),
        'due_day' => 10,
        'total_value' => 1000,
        'discount_value' => 0,
        'payment_method' => 'carnet',
    ]);

    // Cria uma formação sem cursos (ex: Personalizado ITEP)
    $formation = \App\Models\Formation::create([
        'name' => 'Personalizado ITEP',
        'description' => 'Formação personalizada livre',
        'active' => true,
    ]);

    // Cria cursos avulsos no catálogo que NÃO pertencem à formação
    $courseA = \App\Models\Course::create(['name' => 'Curso Avulso A', 'workload' => 40, 'active' => true]);
    $courseB = \App\Models\Course::create(['name' => 'Curso Avulso B', 'workload' => 20, 'active' => true]);

    $item = $contract->items()->create([
        'formation_id' => $formation->id,
        'order' => 1,
    ]);

    // Vincula os cursos diretamente ao contrato nessa formação personalizada
    $contractCourseA = \App\Models\ContractCourse::create([
        'contract_item_id' => $item->id,
        'course_id' => $courseA->id,
        'order' => 1,
        'status' => 'not_started',
    ]);

    $contractCourseB = \App\Models\ContractCourse::create([
        'contract_item_id' => $item->id,
        'course_id' => $courseB->id,
        'order' => 2,
        'status' => 'in_progress',
    ]);

    expect($item->contractCourses()->count())->toBe(2)
        ->and($item->contractCourses->pluck('course_id')->all())->toBe([$courseA->id, $courseB->id]);
});

test('importStudentCoursesFromLms imports courses from Ouro Moderno into contract', function () {
    $person = Person::create([
        'name' => 'Aluno Com Cursos OM',
        'ouro_moderno_cod_int' => 9988,
    ]);
    $responsible = Responsible::create([
        'person_id' => $person->id,
        'financial_responsible' => true,
        'relationship' => \App\Enums\RelationshipEnum::Legal_guardian,
    ]);

    $contract = \App\Models\Contract::create([
        'contract_number' => 'OMCS-001',
        'student_id' => $person->id,
        'responsible_id' => $responsible->id,
        'status' => 'draft',
        'start_date' => now(),
        'due_day' => 10,
        'total_value' => 1200,
        'discount_value' => 0,
        'payment_method' => 'carnet',
    ]);

    // Mock OuroModernoService
    $mockOm = Mockery::mock(\App\Services\OuroModernoService::class);
    $mockOm->shouldReceive('isConfigured')->andReturn(true);
    $mockOm->shouldReceive('studentCourseStatus')->with(9988)->andReturn([
        'cursos' => [
            ['idcurso' => 501, 'curso' => 'Photoshop CC', 'carga' => 30, 'situacao' => 'concluido', 'media' => 9.5],
            ['idcurso' => 502, 'curso' => 'Excel Avançado', 'carga' => 40, 'situacao' => 'cursando'],
        ],
    ]);
    app()->instance(\App\Services\OuroModernoService::class, $mockOm);

    $syncService = app(\App\Services\OuroModernoContractSyncService::class);
    $count = $syncService->importStudentCoursesFromLms($contract);

    expect($count)->toBe(2);
    expect($contract->items()->count())->toBe(1);

    $item = $contract->items()->first();
    expect($item->contractCourses()->count())->toBe(2);

    $photoshopCourse = \App\Models\Course::where('ouro_moderno_id', 501)->first();
    expect($photoshopCourse)->not->toBeNull()
        ->and($photoshopCourse->name)->toBe('Photoshop CC');

    $cc1 = $item->contractCourses()->where('course_id', $photoshopCourse->id)->first();
    expect($cc1->status)->toBe('completed')
        ->and((float) $cc1->grade)->toBe(9.5)
        ->and($cc1->progress_source)->toBe('ouro_moderno');

    $excelCourse = \App\Models\Course::where('ouro_moderno_id', 502)->first();
    $cc2 = $item->contractCourses()->where('course_id', $excelCourse->id)->first();
    expect($cc2->status)->toBe('in_progress')
        ->and($cc2->progress_source)->toBe('ouro_moderno');
});

test('importStudentCoursesFromLms retries with updateStudentById if cod_int was not registered in Ouro Moderno', function () {
    $person = Person::create([
        'name' => 'Aluno OM Sem Cod Int Previo',
        'ouro_moderno_cod_int' => 4944,
    ]);
    $responsible = Responsible::create([
        'person_id' => $person->id,
        'financial_responsible' => true,
        'relationship' => \App\Enums\RelationshipEnum::Legal_guardian,
    ]);

    $contract = \App\Models\Contract::create([
        'contract_number' => 'OMCS-002',
        'student_id' => $person->id,
        'responsible_id' => $responsible->id,
        'status' => 'draft',
        'start_date' => now(),
        'due_day' => 10,
        'total_value' => 1200,
        'discount_value' => 0,
        'payment_method' => 'carnet',
    ]);

    // Mock OuroModernoService where first call returns empty, then updateStudentById is called, then 2nd call returns courses
    $mockOm = Mockery::mock(\App\Services\OuroModernoService::class);
    $mockOm->shouldReceive('isConfigured')->andReturn(true);
    $mockOm->shouldReceive('studentCourseStatus')->with(4944)->once()->andReturn([
        'error' => 'Dados não encontrados',
        'code' => 0,
    ]);
    $mockOm->shouldReceive('updateStudentById')->with(4944, ['cod_int' => 4944])->once()->andReturn([
        'Msg' => 'Usuário atualizado',
        'Code' => 1,
    ]);
    $mockOm->shouldReceive('studentCourseStatus')->with(4944)->once()->andReturn([
        [
            'idcurso' => 160,
            'curso' => 'Word 2019',
            'situacao' => 'cursando',
            'disponivel' => 1,
        ],
    ]);
    app()->instance(\App\Services\OuroModernoService::class, $mockOm);

    $syncService = app(\App\Services\OuroModernoContractSyncService::class);
    $count = $syncService->importStudentCoursesFromLms($contract);

    expect($count)->toBe(1);
    $item = $contract->items()->first();
    expect($item->contractCourses()->count())->toBe(1);
    expect($item->contractCourses()->first()->course->name)->toBe('Word 2019');
});

test('orphan person with responsible can be safely deleted when having no contracts', function () {
    $person = Person::create([
        'name' => 'Aluno Orfão Sem Contrato',
        'email' => 'orfao@teste.com',
        'ouro_moderno_cod_int' => 8888,
    ]);

    $responsible = Responsible::create([
        'person_id' => $person->id,
        'financial_responsible' => true,
        'relationship' => \App\Enums\RelationshipEnum::Legal_guardian,
    ]);

    expect($person->responsibles()->count())->toBe(1);

    // Exclusão deve remover a pessoa e o responsável órfão em cascata sem violar constraint
    $person->delete();

    expect(Person::find($person->id))->toBeNull();
    expect(Responsible::find($responsible->id))->toBeNull();
});

test('person cannot be deleted if active contract exists', function () {
    $person = Person::create([
        'name' => 'Aluno Com Contrato Ativo',
        'email' => 'ativo@teste.com',
    ]);

    $responsible = Responsible::create([
        'person_id' => $person->id,
        'financial_responsible' => true,
        'relationship' => \App\Enums\RelationshipEnum::Legal_guardian,
    ]);

    $contract = \App\Models\Contract::create([
        'contract_number' => 'BLCK-001',
        'student_id' => $person->id,
        'responsible_id' => $responsible->id,
        'status' => 'active',
        'start_date' => now(),
        'due_day' => 10,
        'total_value' => 1000,
        'discount_value' => 0,
        'payment_method' => 'carnet',
    ]);

    expect(fn () => $person->delete())->toThrow(\RuntimeException::class);
});

test('user without super_admin but with View:OuroModernoLmsStudents can import student and create contract through inherited permissions', function () {
    // Cria um operador normal (NÃO é super_admin)
    $operatorRole = Role::firstOrCreate(['name' => 'atendente_lms', 'guard_name' => 'web']);
    $permission = \Spatie\Permission\Models\Permission::findOrCreate('View:OuroModernoLmsStudents', 'web');
    $operatorRole->syncPermissions([$permission]);

    $operatorPerson = Person::create(['name' => 'Operador Sem SuperAdmin', 'email' => 'operador@teste.com']);
    $operator = User::create([
        'person_id' => $operatorPerson->id,
        'name' => 'Operador Sem SuperAdmin',
        'email' => 'operador@teste.com',
        'password' => bcrypt('password'),
    ]);
    $operator->assignRole($operatorRole);

    $this->actingAs($operator);

    // Valida que o operador herda permissão para criar Pessoa e Contrato via canManageLmsOnboarding
    expect($operator->canManageLmsOnboarding())->toBeTrue()
        ->and($operator->can('create', Person::class))->toBeTrue()
        ->and($operator->can('create', \App\Models\Contract::class))->toBeTrue();

    // Executa a importação na página
    $page = new OuroModernoLmsStudents();
    $cacheKey = 'ouro-moderno:lms-students:'.$operator->id;
    Cache::put($cacheKey, [
        [
            'cod_int' => '9991',
            'id_aluno' => '9991',
            'name' => 'Aluno Importado por Atendente',
            'email' => 'atendente.import@teste.com',
            'username' => 'alunoatendente',
            'active' => 'S',
        ],
    ], now()->addMinutes(20));

    try {
        $page->importAndCreateContract('9991', true);
    } catch (\Throwable $e) {
        // Redirecionamento disparado
    }

    $imported = Person::where('ouro_moderno_cod_int', 9991)->first();
    expect($imported)->not->toBeNull()
        ->and($imported->name)->toBe('Aluno Importado por Atendente');
});

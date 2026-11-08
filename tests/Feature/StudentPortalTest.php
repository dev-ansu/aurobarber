<?php

use App\Enums\RelationshipEnum;
use App\Livewire\Student\StudentPortal;
use App\Models\Contract;
use App\Models\ContractCourse;
use App\Models\ContractItem;
use App\Models\Course;
use App\Models\LmsLesson;
use App\Models\LmsModule;
use App\Models\LmsStudentLessonProgress;
use App\Models\Person;
use App\Models\Responsible;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->studentPerson = Person::create([
        'name' => 'Aluno Estudioso',
        'email' => 'aluno.estudioso@aurosoft.com',
        'birth_date' => '2005-03-15',
    ]);

    $this->responsiblePerson = Person::create([
        'name' => 'Mãe do Aluno',
        'email' => 'mae@aurosoft.com',
    ]);

    $this->responsible = Responsible::create([
        'person_id' => $this->responsiblePerson->id,
        'relationship' => RelationshipEnum::Mother,
        'financial_responsible' => true,
    ]);

    $this->studentUser = User::create([
        'person_id' => $this->studentPerson->id,
        'email' => 'aluno.estudioso@aurosoft.com',
        'password' => bcrypt('secret'),
    ]);

    $this->contract = Contract::create([
        'contract_number' => 'MAT-2026-999',
        'student_id' => $this->studentPerson->id,
        'responsible_id' => $this->responsible->id,
        'status' => 'active',
        'start_date' => today(),
        'total_value' => 1500,
        'validity_months' => 12,
        'enrollment_fee' => 50,
        'monthly_payment' => 150,
        'installments_count' => 10,
        'payment_due_day' => 10,
    ]);

    $this->course = Course::create([
        'name' => 'Informática Essencial',
        'workload' => 60,
        'active' => true,
        'ouro_moderno_id' => null,
    ]);

    $this->formation = \App\Models\Formation::create([
        'name' => 'Formação Informática Essencial',
        'active' => true,
    ]);

    $this->formation->courses()->attach($this->course->id, ['order' => 1]);

    $this->contractItem = ContractItem::create([
        'contract_id' => $this->contract->id,
        'formation_id' => $this->formation->id,
    ]);

    $this->contractCourse = ContractCourse::create([
        'contract_item_id' => $this->contractItem->id,
        'course_id' => $this->course->id,
        'status' => 'in_progress',
    ]);

    $this->module = LmsModule::create([
        'course_id' => $this->course->id,
        'title' => 'Módulo 1: Sistemas Operacionais',
        'sort_order' => 1,
    ]);

    $this->lesson1 = LmsLesson::create([
        'module_id' => $this->module->id,
        'title' => 'Aula 01: Windows e Gerenciamento de Arquivos',
        'estimated_duration_minutes' => 90,
        'passing_score' => 80.00,
        'sort_order' => 1,
        'is_active' => true,
    ]);

    $this->lesson2 = LmsLesson::create([
        'module_id' => $this->module->id,
        'title' => 'Aula 02: Segurança e Armazenamento em Nuvem',
        'estimated_duration_minutes' => 90,
        'passing_score' => 80.00,
        'sort_order' => 2,
        'is_active' => true,
    ]);
});

it('redirects unauthenticated users trying to access student portal', function () {
    $this->get(route('student.portal'))
        ->assertRedirect(route('login'));
});

it('renders the student portal for student with enrolled courses, metrics and pacing indicator', function () {
    $this->actingAs($this->studentUser)
        ->get(route('student.portal'))
        ->assertSuccessful()
        ->assertSee('Aluno Estudioso')
        ->assertSee('MAT-2026-999')
        ->assertSee('Média Geral')
        ->assertSee('Aulas Concluídas')
        ->assertSee('Tempo de Estudo')
        ->assertSee('Ranking Geral')
        ->assertSee('Informática Essencial');

    // Quando clica ou acessa a aba de cursos
    Livewire::actingAs($this->studentUser)
        ->test(StudentPortal::class)
        ->set('activeTab', 'courses')
        ->assertSee('Informática Essencial')
        ->assertSee('Aula 01: Windows e Gerenciamento de Arquivos')
        ->assertSee('Disponível Hoje');
});

it('displays lesson status badges according to linear progression and daily pacing', function () {
    // Na aba de cursos, aula 1 está disponível para hoje
    Livewire::actingAs($this->studentUser)
        ->test(StudentPortal::class)
        ->set('activeTab', 'courses')
        ->assertSee('Disponível Hoje')
        ->assertSee('Iniciar Aula');

    // Aluno conclui a aula 1 com 90% hoje
    LmsStudentLessonProgress::create([
        'contract_id' => $this->contract->id,
        'student_id' => $this->studentPerson->id,
        'lesson_id' => $this->lesson1->id,
        'status' => 'completed',
        'score' => 90.00,
        'completed_at' => now(),
        'session_date' => today(),
    ]);

    // Ao recarregar o portal na aba de cursos:
    // - Aula 1 deve estar marcada como 'Concluída (90%)'
    // - Aula 2 deve estar BLOQUEADA pela TRAVA DIÁRIA (1 aula/dia)
    Livewire::actingAs($this->studentUser)
        ->test(StudentPortal::class)
        ->set('activeTab', 'courses')
        ->assertSee('Concluída (90%)')
        ->assertSee('Trava Diária (1 aula/dia)')
        ->assertSee('Liberada na próxima aula');
});

it('allows super_admins to preview the student portal', function () {
    $adminPerson = Person::create(['name' => 'Diretor', 'email' => 'diretor@aurosoft.com']);
    $adminUser = User::create([
        'person_id' => $adminPerson->id,
        'email' => 'diretor@aurosoft.com',
        'password' => bcrypt('secret'),
    ]);
    $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $adminUser->assignRole($superAdmin);

    $this->actingAs($adminUser)
        ->get(route('student.portal'))
        ->assertSuccessful()
        ->assertSee('Informática Essencial');
});

it('shows synchronization warning for ouro moderno course when student is not migrated', function () {
    // Para testar o curso Ouro Moderno, concluímos as aulas do curso 1 para liberar o curso 2
    LmsStudentLessonProgress::create([
        'contract_id' => $this->contract->id,
        'student_id' => $this->studentPerson->id,
        'lesson_id' => $this->lesson1->id,
        'status' => 'completed',
        'score' => 95.00,
        'completed_at' => now()->subDay(),
    ]);
    LmsStudentLessonProgress::create([
        'contract_id' => $this->contract->id,
        'student_id' => $this->studentPerson->id,
        'lesson_id' => $this->lesson2->id,
        'status' => 'completed',
        'score' => 90.00,
        'completed_at' => now()->subDay(),
    ]);

    $omCourse = Course::create([
        'name' => 'Photoshop Interativo OM',
        'active' => true,
        'ouro_moderno_id' => 777,
    ]);

    $this->formation->courses()->attach($omCourse->id, ['order' => 2]);
    $this->contractItem->contractCourses()->create([
        'course_id' => $omCourse->id,
        'status' => 'in_progress',
    ]);

    // Aluno não migrado (registered_at nulo)
    $this->studentPerson->update([
        'ouro_moderno_registered_at' => null,
        'ouro_moderno_cod_int' => null,
        'ouro_moderno_usuario' => null,
    ]);

    Livewire::actingAs($this->studentUser)
        ->test(StudentPortal::class)
        ->set('activeTab', 'courses')
        ->call('selectCourse', $omCourse->id)
        ->assertSee('Matrícula aguardando sincronização com a Ouro Moderno')
        ->assertDontSee('Conectar ao LMS Ouro Moderno');

    // Quando o aluno é migrado para a Ouro Moderno
    $this->studentPerson->update([
        'ouro_moderno_registered_at' => now(),
        'ouro_moderno_cod_int' => 1234,
        'ouro_moderno_usuario' => $this->contract->contract_number,
    ]);

    Livewire::actingAs($this->studentUser)
        ->test(StudentPortal::class)
        ->set('activeTab', 'courses')
        ->call('selectCourse', $omCourse->id)
        ->assertSee('Conectar ao LMS Ouro Moderno')
        ->assertDontSee('Matrícula aguardando sincronização com a Ouro Moderno');
});

it('uses school custom name when registered in database instead of default aurosoft', function () {
    $school = \App\Models\School::first();
    if ($school) {
        $school->update([
            'legal_name' => 'Instituto Educacional Alpha LTDA',
            'trade_name' => 'Colégio Alpha Tech',
        ]);
    } else {
        $school = \App\Models\School::create([
            'legal_name' => 'Instituto Educacional Alpha LTDA',
            'trade_name' => 'Colégio Alpha Tech',
        ]);
    }

    $this->actingAs($this->studentUser)
        ->get(route('student.portal'))
        ->assertSuccessful()
        ->assertSee('Colégio Alpha Tech')
        ->assertDontSee('Aurosoft • Portal do Aluno');
});

it('locks subsequent courses in the formation until all lessons of previous course are completed', function () {
    // Adiciona Curso 2 na formação (ordem 2)
    $course2 = Course::create([
        'name' => 'Programação Web Completa',
        'workload' => 80,
        'active' => true,
        'ouro_moderno_id' => null,
    ]);

    $this->formation->courses()->attach($course2->id, ['order' => 2]);
    $this->contractItem->contractCourses()->create([
        'course_id' => $course2->id,
        'status' => 'not_started',
    ]);

    $moduleCourse2 = LmsModule::create([
        'course_id' => $course2->id,
        'title' => 'Módulo 1: HTML e CSS',
        'sort_order' => 1,
    ]);

    $lessonCourse2 = LmsLesson::create([
        'module_id' => $moduleCourse2->id,
        'title' => 'Aula 01: Estrutura HTML5',
        'estimated_duration_minutes' => 90,
        'passing_score' => 80.00,
        'sort_order' => 1,
        'is_active' => true,
    ]);

    // Inicialmente, as aulas do Curso 1 NÃO estão concluídas
    // O Curso 2 DEVE ESTAR BLOQUEADO no portal
    $test = Livewire::actingAs($this->studentUser)
        ->test(StudentPortal::class)
        ->set('activeTab', 'courses')
        ->call('selectCourse', $course2->id)
        ->assertSee('Sequência Pedagógica • Curso Bloqueado')
        ->assertSee("Você precisa concluir todas as aulas do curso anterior ('Informática Essencial') para liberar este curso.");

    // Agora o aluno conclui todas as aulas do Curso 1 (aula 1 e aula 2) com notas válidas
    LmsStudentLessonProgress::create([
        'contract_id' => $this->contract->id,
        'student_id' => $this->studentPerson->id,
        'lesson_id' => $this->lesson1->id,
        'status' => 'completed',
        'score' => 95.00,
        'completed_at' => now()->subDays(2),
    ]);

    LmsStudentLessonProgress::create([
        'contract_id' => $this->contract->id,
        'student_id' => $this->studentPerson->id,
        'lesson_id' => $this->lesson2->id,
        'status' => 'completed',
        'score' => 88.00,
        'completed_at' => now()->subDay(),
    ]);

    // Agora o Curso 2 DEVE ESTAR LIBERADO!
    Livewire::actingAs($this->studentUser)
        ->test(StudentPortal::class)
        ->set('activeTab', 'courses')
        ->call('selectCourse', $course2->id)
        ->assertDontSee('Curso Bloqueado')
        ->assertSee('Aula 01: Estrutura HTML5')
        ->assertSee('Disponível Hoje');
});

it('blocks direct player access to lesson when its course is locked', function () {
    // Adiciona Curso 2 com aula
    $course2 = Course::create([
        'name' => 'Design Gráfico Digital',
        'active' => true,
        'ouro_moderno_id' => null,
    ]);

    $this->formation->courses()->attach($course2->id, ['order' => 2]);
    $this->contractItem->contractCourses()->create([
        'course_id' => $course2->id,
        'status' => 'not_started',
    ]);

    $module = LmsModule::create(['course_id' => $course2->id, 'title' => 'Módulo 1', 'sort_order' => 1]);
    $lesson = LmsLesson::create([
        'module_id' => $module->id,
        'title' => 'Aula 01: Teoria das Cores',
        'estimated_duration_minutes' => 90,
        'passing_score' => 80.00,
        'sort_order' => 1,
        'is_active' => true,
    ]);

    // Aluno tenta abrir o player da aula do Curso 2 diretamente pela rota
    Livewire::actingAs($this->studentUser)
        ->test(\App\Livewire\Lms\LmsPlayer::class, ['lesson' => $lesson])
        ->assertSet('isLocked', true)
        ->assertSet('lockReason', 'previous_course_incomplete')
        ->assertSee('Pré-requisito Pendente')
        ->assertSee('Voltar ao Portal do Aluno');
});

it('redirects to the current or next lesson when resumeStudy is called', function () {
    Livewire::actingAs($this->studentUser)
        ->test(StudentPortal::class)
        ->call('resumeStudy')
        ->assertRedirect(route('lms.player', [
            'lesson' => $this->lesson1->id,
            'contract_id' => $this->contract->id,
        ]));
});

it('renders overdue installments warning banner with student name without error', function () {
    $type = \App\Models\FinancialType::create(['name' => 'Mensalidade']);

    \App\Models\Installment::create([
        'contract_id' => $this->contract->id,
        'financial_type_id' => $type->id,
        'installment_number' => 1,
        'total_installments' => 1,
        'amount' => 100,
        'amount_paid' => 0,
        'due_date' => today()->subDays(5),
        'status' => 'pending',
    ]);

    expect($this->contract->hasOverdueInstallments())->toBeTrue();

    Livewire::actingAs($this->studentUser)
        ->test(StudentPortal::class)
        ->assertSuccessful()
        ->assertSee('Atualização de Matrícula Necessária')
        ->assertSee('Aluno Estudioso');
});

it('allows educators to test portal as student with all courses unlocked and sequential progression without database contract', function () {
    Role::firstOrCreate(['name' => 'super_admin']);
    $educatorPerson = Person::create([
        'name' => 'Professor Silva',
        'email' => 'prof.silva@aurosoft.com',
    ]);
    $educator = User::create([
        'person_id' => $educatorPerson->id,
        'email' => 'prof.silva@aurosoft.com',
        'password' => bcrypt('secret'),
    ]);
    $educator->assignRole('super_admin');

    // Salva modo aluno na sessão
    session(['portal_mode_' . $educator->id => 'student']);

    $test = Livewire::actingAs($educator)
        ->test(StudentPortal::class)
        ->assertSuccessful()
        ->assertDontSee('Nenhuma matrícula ativa localizada')
        ->assertSee('Modo de Teste do Educador')
        ->assertSee($this->course->name);

    expect($test->get('isEducatorTestingAsStudent'))->toBeTrue();
    expect($test->get('contract'))->toBeNull();

    // Todas as aulas liberadas para teste do educador (sem bloquear acesso prévio)
    $progression = app(\App\Services\LmsProgressionService::class);
    $status1 = $test->instance()->getLessonStatus($this->lesson1, $progression);
    $status2 = $test->instance()->getLessonStatus($this->lesson2, $progression);

    expect($status1['state'])->toBe('available');
    expect($status2['state'])->toBe('available');

    // Conclui aula 1 na sessão do teste
    session(['educator_test_completed_lessons_' . $educator->id => [$this->lesson1->id]]);

    $status1After = $test->instance()->getLessonStatus($this->lesson1, $progression);
    $status2After = $test->instance()->getLessonStatus($this->lesson2, $progression);

    expect($status1After['state'])->toBe('completed');
    expect($status2After['state'])->toBe('available');

    // Reinicia teste do curso
    $test->call('resetCourseTestProgress', $this->course->id);
    expect(session('educator_test_completed_lessons_' . $educator->id))->toBeEmpty();
});




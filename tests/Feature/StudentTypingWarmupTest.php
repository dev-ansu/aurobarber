<?php

declare(strict_types=1);

use App\Livewire\Student\StudentTypingWarmup;
use App\Models\Contract;
use App\Models\Course;
use App\Models\LmsLesson;
use App\Models\LmsModule;
use App\Models\Person;
use App\Models\Responsible;
use App\Models\StudentAccessLog;
use App\Models\StudentTypingRecord;
use App\Models\TypingExercise;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->respPerson = Person::create(['name' => 'Resp Pai', 'email' => 'resp.pai@teste.com']);
    $this->responsible = Responsible::create([
        'person_id' => $this->respPerson->id,
        'relationship' => \App\Enums\RelationshipEnum::Father,
        'financial_responsible' => true,
    ]);

    $this->studentPerson = Person::create(['name' => 'Lucas Digitador', 'email' => 'lucas@teste.com']);
    $this->studentUser = User::create([
        'person_id' => $this->studentPerson->id,
        'email' => 'lucas@teste.com',
        'password' => bcrypt('123'),
    ]);

    $this->contract = Contract::create([
        'student_id' => $this->studentPerson->id,
        'responsible_id' => $this->responsible->id,
        'contract_number' => 'CTR-DIGIT-01',
        'status' => 'active',
        'start_date' => today(),
        'total_value' => 1000,
        'validity_months' => 6,
        'enrollment_fee' => 50,
        'monthly_payment' => 150,
        'installments_count' => 6,
        'payment_due_day' => 10,
    ]);

    $this->course = Course::create(['name' => 'Operador de Computador', 'workload' => 80, 'active' => true]);
    $this->module = LmsModule::create(['course_id' => $this->course->id, 'title' => 'Módulo 1', 'sort_order' => 1]);
    $this->lesson = LmsLesson::create([
        'module_id' => $this->module->id,
        'title' => 'Aula 01: Windows e Digitação',
        'estimated_duration_minutes' => 90,
        'passing_score' => 80.0,
        'sort_order' => 1,
        'is_active' => true,
    ]);

    $this->ex1 = TypingExercise::create([
        'title' => 'Exercício 01: Dedos Indicadores',
        'level' => 1,
        'category' => 'home_row',
        'target_text' => 'ffff jjjj ff jj',
        'instructions' => 'F e J',
        'min_wpm' => 15,
        'min_accuracy' => 85.00,
        'sort_order' => 1,
        'is_active' => true,
    ]);

    $this->ex2 = TypingExercise::create([
        'title' => 'Exercício 06: Pangrama do Jabuti Xereta',
        'level' => 2,
        'category' => 'all_letters',
        'target_text' => 'Um pequeno jabuti xereta viu dez cegonhas felizes.',
        'instructions' => 'Todas as letras',
        'min_wpm' => 20,
        'min_accuracy' => 90.00,
        'sort_order' => 6,
        'is_active' => true,
    ]);

    $this->ex3 = TypingExercise::create([
        'title' => 'Exercício 18: Texto Curto - Ergonomia',
        'level' => 6,
        'category' => 'short_text',
        'target_text' => 'Ao digitar no computador mantenha a postura ereta.',
        'instructions' => 'Ergonomia',
        'min_wpm' => 25,
        'min_accuracy' => 90.00,
        'sort_order' => 18,
        'is_active' => true,
    ]);
});

test('aluno autenticado pode acessar a tela de treino de digitação', function () {
    $this->actingAs($this->studentUser)
        ->get(route('student.typing', ['contract_id' => $this->contract->id, 'lesson_id' => $this->lesson->id]))
        ->assertOk()
        ->assertSee('Exercício 01: Dedos Indicadores')
        ->assertSee('Digitação Vocacional');
});

test('submissão de resultado de digitação calcula wpm precisão estrelas e gera log', function () {
    $component = Livewire::actingAs($this->studentUser)
        ->test(StudentTypingWarmup::class, [
            'contract_id' => $this->contract->id,
            'lesson_id' => $this->lesson->id,
            'exercise_id' => $this->ex1->id,
        ]);

    // Submete resultado: 32 WPM, 160 CPM, 96% precisão, 14 caracteres, 0 erros, 45 segundos
    $component->call('submitTypingResult', 32.0, 160.0, 96.0, 14, 14, 0, 45);

    $component->assertSet('isFinished', true)
        ->assertSet('finalWpm', 32.0)
        ->assertSet('finalAccuracy', 96.0)
        ->assertSet('stars', 3)
        ->assertSet('passed', true);

    $record = StudentTypingRecord::where('contract_id', $this->contract->id)->where('typing_exercise_id', $this->ex1->id)->first();
    expect($record)->not->toBeNull();
    expect($record->stars)->toBe(3);
    expect($record->wpm)->toBe('32.00');

    // Verifica que o log de acesso foi gerado com o aluno correto
    $log = StudentAccessLog::where('contract_id', $this->contract->id)->where('action', 'typing_warmup_completed')->first();
    expect($log)->not->toBeNull()
        ->and($log->student->name)->toBe('Lucas Digitador')
        ->and($log->description)->toContain('Exercício 01: Dedos Indicadores');
});

test('ranking geral da escola computa alunos e melhores velocidades', function () {
    // Aluno 1
    StudentTypingRecord::create([
        'contract_id' => $this->contract->id,
        'student_id' => $this->studentPerson->id,
        'typing_exercise_id' => $this->ex1->id,
        'wpm' => 45.0,
        'cpm' => 225.0,
        'accuracy_percentage' => 98.0,
        'stars' => 3,
        'passed' => true,
        'completed_at' => now(),
    ]);

    // Aluno 2
    $student2 = Person::create(['name' => 'Beatriz Veloz', 'email' => 'beatriz@teste.com']);
    $contract2 = Contract::create([
        'student_id' => $student2->id,
        'responsible_id' => $this->responsible->id,
        'contract_number' => 'CTR-DIGIT-02',
        'status' => 'active',
        'start_date' => today(),
        'total_value' => 1000,
        'validity_months' => 6,
        'enrollment_fee' => 50,
        'monthly_payment' => 150,
        'installments_count' => 6,
        'payment_due_day' => 10,
    ]);

    StudentTypingRecord::create([
        'contract_id' => $contract2->id,
        'student_id' => $student2->id,
        'typing_exercise_id' => $this->ex1->id,
        'wpm' => 60.0,
        'cpm' => 300.0,
        'accuracy_percentage' => 99.0,
        'stars' => 3,
        'passed' => true,
        'completed_at' => now(),
    ]);

    $rankings = StudentTypingRecord::globalSchoolRankings(10);
    expect($rankings)->toHaveCount(2);
    // Beatriz deve estar em 1º com 60 WPM
    expect($rankings->first()->student_name)->toBe('Beatriz Veloz')
        ->and((float) $rankings->first()->max_wpm)->toBe(60.0);
});

test('ao concluir o treino o aluno pode avançar diretamente para a aula', function () {
    $component = Livewire::actingAs($this->studentUser)
        ->test(StudentTypingWarmup::class, [
            'contract_id' => $this->contract->id,
            'lesson_id' => $this->lesson->id,
        ]);

    $component->call('startLesson')
        ->assertRedirect(route('lms.player', [
            'lesson' => $this->lesson->id,
            'contract_id' => $this->contract->id,
        ]));
});

test('acumulo de 15 minutos de digitacao marca warmup concluido', function () {
    $component = Livewire::actingAs($this->studentUser)
        ->test(StudentTypingWarmup::class, [
            'contract_id' => $this->contract->id,
            'lesson_id' => $this->lesson->id,
            'exercise_id' => $this->ex2->id,
        ]);

    // Submete 900 segundos (15 minutos)
    $component->call('submitTypingResult', 35.0, 175.0, 95.0, 200, 195, 5, 900);

    $component->assertSet('isWarmupCompleted', true)
        ->assertSet('remainingSeconds', 0);
});

test('educador pode autorizar a liberacao da aula pelo modal de autorizacao', function () {
    $profPerson = Person::create(['name' => 'Professor Silva', 'email' => 'prof@escola.com']);
    $profUser = User::create([
        'person_id' => $profPerson->id,
        'email' => 'prof@escola.com',
        'password' => bcrypt('prof123'),
    ]);
    $profRole = Role::firstOrCreate(['name' => 'professor', 'guard_name' => 'web']);
    $profUser->assignRole($profRole);

    $component = Livewire::actingAs($this->studentUser)
        ->test(StudentTypingWarmup::class, [
            'contract_id' => $this->contract->id,
            'lesson_id' => $this->lesson->id,
        ])
        ->set('educatorEmail', 'prof@escola.com')
        ->set('educatorPassword', 'prof123')
        ->call('authorizeByEducator');

    $component->assertSet('isEducatorAuthorized', true)
        ->assertSet('isWarmupCompleted', true)
        ->assertSet('remainingSeconds', 0);
});

test('aluno pode filtrar exercicios por categoria de pangramas palavras e textos', function () {
    $component = Livewire::actingAs($this->studentUser)
        ->test(StudentTypingWarmup::class, [
            'contract_id' => $this->contract->id,
        ]);

    $component->call('setCategoryFilter', 'all_letters');
    expect($component->get('exercisesList')->pluck('category')->unique()->all())->toBe(['all_letters']);

    $component->call('setCategoryFilter', 'short_text');
    expect($component->get('exercisesList')->pluck('category')->unique()->all())->toBe(['short_text']);
});

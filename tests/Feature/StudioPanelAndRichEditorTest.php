<?php

use App\Filament\Studio\Pages\LmsStudio;
use App\Models\Course;
use App\Models\LmsLesson;
use App\Models\LmsLessonStep;
use App\Models\LmsModule;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->person = Person::create([
        'name' => 'Professor Coordenador',
        'email' => 'coordenador.studio@aurosoft.com',
    ]);

    $this->user = User::create([
        'person_id' => $this->person->id,
        'email' => 'coordenador.studio@aurosoft.com',
        'password' => bcrypt('secret'),
        'panel_access' => true,
    ]);

    $this->user->givePermissionTo(\Spatie\Permission\Models\Permission::findOrCreate('View:LmsStudio', 'web'));
    \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('studio'));

    $this->course = Course::create([
        'name' => 'Curso de Design e Autoria',
        'slug' => 'curso-design-autoria',
        'category' => 'Tecnologia',
        'workload_hours' => 20,
    ]);

    $this->module = LmsModule::create([
        'course_id' => $this->course->id,
        'title' => 'Módulo 1: Introdução',
        'sort_order' => 1,
    ]);

    $this->lesson = LmsLesson::create([
        'module_id' => $this->module->id,
        'title' => 'Aula 1: Primeiros Passos',
        'sort_order' => 1,
    ]);
});

test('studio panel route /estudio is accessible and renders studio brand and navigation', function () {
    $this->actingAs($this->user)
        ->get('/estudio')
        ->assertRedirect('/estudio/lms-studio');

    $this->actingAs($this->user)
        ->get('/estudio/lms-studio')
        ->assertOk()
        ->assertSee('Estúdio LMS')
        ->assertSee('Ambiente de Autoria Pedagógica');
});

test('studio panel question bank route is accessible', function () {
    $this->actingAs($this->user)
        ->get('/estudio/lms-question-bank')
        ->assertOk();
});

test('lms studio uses rich editor and saves formatted rich content', function () {
    $component = Livewire::actingAs($this->user)
        ->test(LmsStudio::class, ['course_id' => $this->course->id])
        ->set('selectedLessonId', $this->lesson->id)
        ->call('openCreateStepModal', 'praelectio')
        ->set('stepTitle', 'Conceitos Fundamentais')
        ->fillForm([
            'body' => '<p>Este é um texto com <strong>negrito</strong> e <em>itálico</em> formatado via RichEditor.</p>',
        ])
        ->call('saveStep')
        ->assertHasNoErrors();

    $step = LmsLessonStep::where('lesson_id', $this->lesson->id)->first();
    expect($step)->not->toBeNull()
        ->and($step->content['body'])->toContain('<strong>negrito</strong>')
        ->and($step->content['body'])->toContain('<em>itálico</em>');
});

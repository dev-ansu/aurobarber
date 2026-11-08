<?php

use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function makeStudioUser(string $email): User
{
    $person = Person::create(['name' => 'Usuário '.$email, 'email' => $email]);

    $user = User::create([
        'person_id' => $person->id,
        'email' => $email,
        'password' => 'secret123',
        'panel_access' => true,
    ]);

    $school = \App\Models\School::first();
    if ($school) {
        $user->schools()->attach($school);
    }

    return $user;
}

test('usuario sem permissao nao acessa o painel /estudio nem suas paginas', function () {
    $user = makeStudioUser('semacesso@teste.com');
    $this->actingAs($user);

    $this->get('/estudio/lms-studio')->assertForbidden();
    $this->get('/estudio/lms-question-bank')->assertForbidden();
});

test('usuario com permissao View:LmsStudio acessa o painel /estudio', function () {
    $user = makeStudioUser('conteudista@teste.com');
    $user->givePermissionTo(Permission::findOrCreate('View:LmsStudio', 'web'));
    $this->actingAs($user);

    $this->get('/estudio/lms-studio')->assertOk();
    $this->get('/estudio/lms-question-bank')->assertOk();
});

test('usuario com permissao page_LmsStudio acessa o painel /estudio', function () {
    $user = makeStudioUser('pedagogico@teste.com');
    $user->givePermissionTo(Permission::findOrCreate('page_LmsStudio', 'web'));
    $this->actingAs($user);

    $this->get('/estudio/lms-studio')->assertOk();
});

test('rotas legadas do estudio no painel admin nao existem mais', function () {
    $admin = makeStudioUser('admin@teste.com');
    $admin->givePermissionTo(Permission::findOrCreate('View:LmsStudio', 'web'));
    $this->actingAs($admin);

    $this->get('/admin/lms-studio')->assertNotFound();
    $this->get('/admin/lms-question-bank')->assertNotFound();
});

test('atalho do estudio no painel admin fica oculto para quem nao tem acesso', function () {
    $this->actingAs(makeStudioUser('financeiro@teste.com'));
    $this->get(route('filament.admin.pages.user-guide'))->assertOk()->assertDontSee('/estudio"', false);
});

test('atalho do estudio no painel admin aparece para quem tem acesso', function () {
    $comAcesso = makeStudioUser('coord@teste.com');
    $comAcesso->givePermissionTo(Permission::findOrCreate('View:LmsStudio', 'web'));
    $this->actingAs($comAcesso);
    $this->get(route('filament.admin.pages.user-guide'))->assertOk()->assertSee('/estudio"', false);
});

test('player do LMS em modo preview volta para a rota /estudio/lms-studio', function () {
    $prof = makeStudioUser('prof.player@teste.com');
    $prof->givePermissionTo(Permission::findOrCreate('View:LmsStudio', 'web'));

    $course = \App\Models\Course::create(['name' => 'Curso de Teste Estúdio']);
    $module = \App\Models\LmsModule::create(['course_id' => $course->id, 'title' => 'Módulo 1']);
    $lesson = \App\Models\LmsLesson::create([
        'module_id' => $module->id,
        'title' => 'Aula 1',
        'is_published' => true,
    ]);

    \App\Models\LmsLessonStep::create([
        'lesson_id' => $lesson->id,
        'block_type' => 'intro',
        'title' => 'Passo 1',
        'content_type' => 'text',
        'content' => '<p>Conteúdo</p>',
        'sort_order' => 1,
    ]);

    $this->actingAs($prof);

    $response = $this->get(route('lms.player', ['lesson' => $lesson->id, 'preview' => 1]));
    $response->assertOk();
    $response->assertSee('/estudio/lms-studio', false);
    $response->assertDontSee('/admin/lms-studio', false);

    // Testar redirecionamento de saída em modo preview
    \Livewire\Livewire::test(\App\Livewire\Lms\LmsPlayer::class, ['lesson' => $lesson])
        ->call('exitLesson')
        ->assertRedirect(route('filament.studio.pages.lms-studio', ['course_id' => $course->id]));
});

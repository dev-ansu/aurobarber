<?php

use App\Filament\Pages\UserGuide;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $school = \App\Models\School::first() ?: \App\Models\School::create([
        'legal_name' => 'Escola Matriz',
        'trade_name' => 'Matriz',
    ]);

    $person = Person::create([
        'name' => 'Usuário Teste',
        'email' => 'teste.doc@aurosoft.com',
    ]);

    $this->user = User::create([
        'person_id' => $person->id,
        'email' => 'teste.doc@aurosoft.com',
        'password' => bcrypt('password'),
    ]);
    $this->user->schools()->attach($school);
});

it('allows authenticated users to access the documentation manual', function () {
    $this->actingAs($this->user)
        ->get(UserGuide::getUrl())
        ->assertSuccessful()
        ->assertSee('Central de Conhecimento Aurosoft')
        ->assertSee('O Ciclo de Vida do Aluno no Aurosoft')
        ->assertSee('Módulo Comercial')
        ->assertSee('Módulo Financeiro');
});

it('filters sections based on live search term', function () {
    Livewire::actingAs($this->user)
        ->test(UserGuide::class)
        ->assertSee('Módulo Comercial')
        ->assertSee('Módulo Financeiro')
        ->set('search', 'pix')
        ->assertSee('Módulo Financeiro')
        ->assertDontSee('Módulo Comercial');
});

it('filters sections based on active category tab', function () {
    Livewire::actingAs($this->user)
        ->test(UserGuide::class)
        ->set('activeCategory', 'kiosk')
        ->assertSee('Totem de Autoatendimento')
        ->assertSee('Pesquisa NPS')
        ->assertDontSee('Módulo Comercial')
        ->assertDontSee('Módulo Financeiro');
});

it('shows empty state when no section matches the search query', function () {
    Livewire::actingAs($this->user)
        ->test(UserGuide::class)
        ->set('search', 'buscaquenaoexiste999')
        ->assertSee('Nenhum tutorial encontrado')
        ->assertSee('Limpar Filtros e Ver Todos');
});

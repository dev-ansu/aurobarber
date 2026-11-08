<?php

namespace Tests\Feature;

use App\Filament\Pages\PermissionsGuide;
use App\Models\Person;
use App\Models\School;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PermissionsGuideTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private User $superAdmin;
    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'secretaria', 'guard_name' => 'web']);

        $this->school = School::create([
            'legal_name' => 'Escola Teste Guia LTDA',
            'trade_name' => 'Escola Teste',
            'cnpj' => '12.345.678/0001-99',
            'is_main' => true,
        ]);

        $adminPerson = Person::create(['name' => 'Diretor Geral', 'email' => 'diretor@escola.com']);
        $this->superAdmin = User::factory()->create([
            'person_id' => $adminPerson->id,
            'email' => 'diretor@escola.com',
        ]);
        $this->superAdmin->assignRole('super_admin');

        $staffPerson = Person::create(['name' => 'Atendente Recepcao', 'email' => 'atendente@escola.com']);
        $this->regularUser = User::factory()->create([
            'person_id' => $staffPerson->id,
            'email' => 'atendente@escola.com',
        ]);
        $this->regularUser->assignRole('secretaria');

        $this->actingAs($this->superAdmin);
        Filament::setTenant($this->school);
    }

    public function test_super_admin_can_access_permissions_guide(): void
    {
        $this->actingAs($this->superAdmin);
        expect(PermissionsGuide::canAccess())->toBeTrue();

        Livewire::test(PermissionsGuide::class)
            ->assertSuccessful()
            ->assertSee('Manual de Definição de Permissões')
            ->assertSee('Secretaria')
            ->assertSee('Educador / Professor de Laboratório')
            ->assertSee('Consultor Comercial')
            ->assertSee('Financeiro');
    }

    public function test_regular_user_cannot_access_permissions_guide(): void
    {
        $this->actingAs($this->regularUser);
        expect(PermissionsGuide::canAccess())->toBeFalse();
    }

    public function test_search_and_categories_in_permissions_guide(): void
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(PermissionsGuide::class)
            ->set('activeCategory', 'cargos')
            ->assertSee('Modelos Prontos por Função')
            ->set('activeCategory', 'all')
            ->set('search', 'multa')
            ->assertSee('Abonar / Alterar Juros e Multa');
    }
}

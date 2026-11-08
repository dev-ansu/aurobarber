<?php

namespace Tests\Feature;

use App\Models\ContractType;
use App\Models\Person;
use App\Models\School;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SchoolHeadquartersAndContractModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_headquarters_is_exclusive_across_the_network(): void
    {
        School::query()->delete();

        $school1 = School::create([
            'legal_name' => 'Escola Matriz Inicial',
            'trade_name' => 'Matriz Centro',
            'cnpj' => '11.111.111/0001-11',
            'is_headquarters' => true,
        ]);

        $school2 = School::create([
            'legal_name' => 'Escola Filial Y',
            'trade_name' => 'Filial Sul',
            'cnpj' => '22.222.222/0001-22',
            'is_headquarters' => false,
        ]);

        expect(School::getHeadquarters()->id)->toBe($school1->id)
            ->and($school1->fresh()->is_headquarters)->toBeTrue()
            ->and($school2->fresh()->is_headquarters)->toBeFalse();

        // Agora define a Escola 2 como Matriz
        $school2->update(['is_headquarters' => true]);

        // Escola 1 deve ter deixado de ser Matriz automaticamente
        expect($school2->fresh()->is_headquarters)->toBeTrue()
            ->and($school1->fresh()->is_headquarters)->toBeFalse()
            ->and(School::getHeadquarters()->id)->toBe($school2->id);
    }

    public function test_user_default_tenant_loads_the_configured_headquarters_school(): void
    {
        School::query()->delete();

        $school1 = School::create([
            'legal_name' => 'Escola 1',
            'trade_name' => 'Unidade 1',
            'cnpj' => '11.111.111/0001-11',
            'is_headquarters' => false,
        ]);

        $school2 = School::create([
            'legal_name' => 'Escola 2',
            'trade_name' => 'Unidade Matriz 2',
            'cnpj' => '22.222.222/0001-22',
            'is_headquarters' => true,
        ]);

        Role::findOrCreate('super_admin', 'web');

        $person = Person::create([
            'name' => 'Diretor Geral',
            'email' => 'diretor@example.com',
        ]);

        $user = User::create([
            'person_id' => $person->id,
            'email' => 'diretor@example.com',
            'password' => 'secret123',
        ]);

        $user->assignRole('super_admin');

        $panel = Filament::getPanel('admin');
        $defaultTenant = $user->getDefaultTenant($panel);

        expect($defaultTenant)->not->toBeNull()
            ->and($defaultTenant->id)->toBe($school2->id);
    }

    public function test_contract_type_resource_exclusivity_and_labels(): void
    {
        $type1 = ContractType::create([
            'name' => 'Minuta 2025',
            'version' => '2025.1',
            'content' => 'Conteúdo 2025',
            'is_active' => true,
        ]);

        $type2 = ContractType::create([
            'name' => 'Minuta 2026',
            'version' => '2026.1',
            'content' => 'Conteúdo 2026',
            'is_active' => true,
        ]);

        // Ao ativar Minuta 2026, Minuta 2025 deve ser inativada
        expect($type2->fresh()->is_active)->toBeTrue()
            ->and($type1->fresh()->is_active)->toBeFalse();
    }
}

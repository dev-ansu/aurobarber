<?php

use App\Enums\RelationshipEnum;
use App\Filament\Resources\Schools\SchoolResource;
use App\Models\Contract;
use App\Models\Person;
use App\Models\Responsible;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function makeSchoolSnapshotContract(string $suffix, Person $student, Responsible $responsible): Contract
{
    return Contract::create([
        'contract_number' => "SCHOOL-{$suffix}",
        'student_id' => $student->id,
        'responsible_id' => $responsible->id,
        'start_date' => '2026-10-01',
        'total_value' => 1200,
        'status' => 'draft',
    ]);
}

test('administration may register school and manage its partners', function () {
    $adminPerson = Person::create(['name' => 'Admin Escola', 'email' => 'admin-escola@example.com']);
    $admin = User::create(['person_id' => $adminPerson->id, 'email' => 'admin-escola@example.com', 'password' => 'secret']);
    $role = Role::findOrCreate('super_admin', 'web');
    $admin->assignRole($role);
    $this->actingAs($admin);

    expect(SchoolResource::canCreate())->toBeTrue();

    $school = School::create([
        'legal_name' => 'Escola Técnica de Demonstração Ltda.',
        'trade_name' => 'Escola Técnica Demo',
        'cnpj' => '11.222.333/0001-81',
        'city' => 'Recife',
        'state' => 'PE',
    ]);
    $partner = $school->partners()->create([
        'name' => 'Sócia Administradora',
        'cpf' => '111.444.777-35',
        'role' => 'Sócia-administradora',
        'ownership_percentage' => 100,
        'legal_representative' => true,
    ]);

    expect($school->partners()->count())->toBe(1)
        ->and($partner->legal_representative)->toBeTrue()
        ->and(SchoolResource::canDelete($school))->toBeFalse();
});

test('new contracts keep a snapshot of the school and partner details', function () {
    $school = School::create([
        'legal_name' => 'Escola Profissional Alfa Ltda.',
        'trade_name' => 'Alfa Profissional',
        'cnpj' => '11.222.333/0001-81',
        'city' => 'Recife',
        'state' => 'PE',
    ]);
    $school->partners()->create([
        'name' => 'Representante Legal',
        'role' => 'Sócia-administradora',
        'legal_representative' => true,
    ]);

    $student = Person::create([
        'name' => 'Aluno da escola',
        'email' => 'school-snapshot-student@example.com',
    ]);
    $responsiblePerson = Person::create([
        'name' => 'Responsável da escola',
        'email' => 'school-snapshot-responsible@example.com',
    ]);
    $responsible = Responsible::create([
        'person_id' => $responsiblePerson->id,
        'relationship' => RelationshipEnum::Mother,
        'financial_responsible' => true,
    ]);

    $contract = makeSchoolSnapshotContract('SNAPSHOT', $student, $responsible);
    $school->update(['trade_name' => 'Nome Atualizado']);
    $snapshot = $contract->fresh()->school_snapshot;

    expect($snapshot['trade_name'])->toBe('Alfa Profissional')
        ->and($snapshot['partners'][0]['name'])->toBe('Representante Legal');
});

<?php

use App\Enums\RelationshipEnum;
use App\Filament\Exports\ContractExporter;
use App\Filament\Resources\Contracts\Pages\ListContracts;
use App\Models\Collaborator;
use App\Models\Contract;
use App\Models\ContractType;
use App\Models\Person;
use App\Models\Responsible;
use App\Models\User;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

function createContractTableViewer(string $suffix): User
{
    $person = Person::create([
        'name' => "Operador {$suffix}",
        'email' => "contract-table-operator-{$suffix}@example.com",
    ]);
    $user = User::create([
        'person_id' => $person->id,
        'email' => "contract-table-operator-{$suffix}@example.com",
        'password' => 'password',
    ]);
    $user->givePermissionTo(Permission::findOrCreate('ViewAny:Contract', 'web'));

    return $user;
}

/**
 * @return array{contract: Contract, student: Person, responsible: Person}
 */
function createContractTableRecord(
    string $suffix,
    string $startDate,
    string $createdAt,
    string $status = 'active',
    ?ContractType $contractType = null,
    ?Collaborator $collaborator = null,
): array {
    $student = Person::create([
        'name' => "Aluno {$suffix}",
        'email' => "contract-table-student-{$suffix}@example.com",
    ]);
    $responsiblePerson = Person::create([
        'name' => "Responsável {$suffix}",
        'email' => "contract-table-responsible-{$suffix}@example.com",
    ]);
    $responsible = Responsible::create([
        'person_id' => $responsiblePerson->id,
        'relationship' => RelationshipEnum::Mother,
        'financial_responsible' => true,
    ]);
    $contract = Contract::create([
        'contract_number' => "CONTRACT-TABLE-{$suffix}",
        'student_id' => $student->id,
        'responsible_id' => $responsible->id,
        'collaborator_id' => $collaborator?->getKey(),
        'contract_type_id' => $contractType?->getKey(),
        'start_date' => $startDate,
        'total_value' => 1250,
        'discount_value' => 50,
        'status' => $status,
    ]);
    $contract->forceFill(['created_at' => Carbon::parse($createdAt)])->saveQuietly();

    return [
        'contract' => $contract,
        'student' => $student,
        'responsible' => $responsiblePerson,
    ];
}

test('contract table filters by start date range and status', function () {
    $this->actingAs(createContractTableViewer('START-FILTER'));
    $activeOctober = createContractTableRecord('ACTIVE-OCT', '2026-10-10', '2026-09-01')['contract'];
    $draftOctober = createContractTableRecord('DRAFT-OCT', '2026-10-15', '2026-10-01', 'draft')['contract'];
    $activeSeptember = createContractTableRecord('ACTIVE-SEP', '2026-09-30', '2026-09-01')['contract'];

    Livewire::test(ListContracts::class)
        ->assertCanSeeTableRecords([$activeOctober, $draftOctober, $activeSeptember])
        ->filterTable('start_date_range', [
            'from' => '2026-10-01',
            'until' => '2026-10-31',
        ])
        ->assertCanSeeTableRecords([$activeOctober, $draftOctober])
        ->assertCanNotSeeTableRecords([$activeSeptember])
        ->filterTable('status', 'active')
        ->assertCanSeeTableRecords([$activeOctober])
        ->assertCanNotSeeTableRecords([$draftOctober, $activeSeptember]);
});

test('contract table filters by registration date range', function () {
    $this->actingAs(createContractTableViewer('CREATED-FILTER'));
    $registeredInOctober = createContractTableRecord('CREATED-OCT', '2026-08-01', '2026-10-12')['contract'];
    $registeredInSeptember = createContractTableRecord('CREATED-SEP', '2026-10-01', '2026-09-30')['contract'];

    Livewire::test(ListContracts::class)
        ->filterTable('created_at_range', [
            'from' => '2026-10-01',
            'until' => '2026-10-31',
        ])
        ->assertCanSeeTableRecords([$registeredInOctober])
        ->assertCanNotSeeTableRecords([$registeredInSeptember]);
});

test('contract table can filter by contract type and responsible collaborator', function () {
    $this->actingAs(createContractTableViewer('RELATION-FILTER'));
    $selectedType = ContractType::create(['name' => 'Contrato selecionado', 'is_active' => false]);
    $otherType = ContractType::create(['name' => 'Outro contrato', 'is_active' => false]);
    $sellerPerson = Person::create([
        'name' => 'Vendedor selecionado',
        'email' => 'contract-table-seller@example.com',
    ]);
    $seller = Collaborator::create([
        'person_id' => $sellerPerson->id,
        'admission_date' => '2025-01-01',
        'role' => 'Vendedor',
        'active' => true,
    ]);
    $selected = createContractTableRecord('RELATION-MATCH', '2026-10-10', '2026-10-10', 'active', $selectedType, $seller)['contract'];
    $other = createContractTableRecord('RELATION-OTHER', '2026-10-10', '2026-10-10', 'active', $otherType)['contract'];

    Livewire::test(ListContracts::class)
        ->filterTable('contract_type_id', $selectedType->getKey())
        ->assertCanSeeTableRecords([$selected])
        ->assertCanNotSeeTableRecords([$other])
        ->filterTable('collaborator_id', $seller->getKey())
        ->assertCanSeeTableRecords([$selected])
        ->assertCanNotSeeTableRecords([$other]);
});

test('contract table exports CSV and XLSX with contract and party details', function () {
    $this->actingAs(createContractTableViewer('EXPORT'));
    $contractType = ContractType::create(['name' => 'Prestação de serviços', 'is_active' => false]);
    $sellerPerson = Person::create([
        'name' => 'Vendedora da Escola',
        'email' => 'contract-table-export-seller@example.com',
    ]);
    $seller = Collaborator::create([
        'person_id' => $sellerPerson->id,
        'admission_date' => '2025-01-01',
        'role' => 'Vendedora',
        'active' => true,
    ]);
    $record = createContractTableRecord(
        'EXPORT-ROW',
        '2026-10-10',
        '2026-10-09 14:30:00',
        'active',
        $contractType,
        $seller,
    );
    $contract = $record['contract']->fresh()->load([
        'student',
        'responsible.person',
        'contractType',
        'collaborator.person',
    ]);

    $livewire = Livewire::test(ListContracts::class)
        ->assertTableHeaderActionsExistInOrder(['export']);
    $exportAction = collect($livewire->instance()->getTable()->getHeaderActions())
        ->first(fn ($action): bool => $action instanceof ExportAction);
    $columnMap = collect(ContractExporter::getColumns())
        ->mapWithKeys(fn (ExportColumn $column): array => [$column->getName() => $column->getLabel()])
        ->all();
    $exporter = new ContractExporter(new Export, $columnMap, []);
    $exportedRow = $exporter($contract);

    expect($exportAction->getFormats())->toBe([ExportFormat::Csv, ExportFormat::Xlsx])
        ->and($exportedRow)->toContain(
            'CONTRACT-TABLE-EXPORT-ROW',
            'Aluno EXPORT-ROW',
            'Responsável EXPORT-ROW',
            'Prestação de serviços',
            'Vendedora da Escola',
            '10/10/2026',
            '1.250,00',
            '50,00',
            'Ativo',
        );
});

<?php

namespace Tests\Feature;

use App\Enums\ContactTypeEnum;
use App\Enums\DocumentTypeEnum;
use App\Filament\Exports\ContractExporter;
use App\Filament\Exports\PersonExporter;
use App\Models\Address;
use App\Models\City;
use App\Models\Classroom;
use App\Models\Contact;
use App\Models\Contract;
use App\Models\ContractCourse;
use App\Models\ContractItem;
use App\Models\Course;
use App\Models\Document;
use App\Models\FinancialType;
use App\Models\Formation;
use App\Models\Installment;
use App\Models\Person;
use App\Models\School;
use App\Models\State;
use App\Models\StudentSchedule;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportersWithRelationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_person_exporter_includes_contacts_addresses_and_documents(): void
    {
        $state = State::create(['name' => 'Ceará', 'acronym' => 'CE']);
        $city = City::create(['name' => 'Fortaleza', 'state_id' => $state->id, 'ibge_code' => 2304400]);

        $person = Person::create([
            'name' => 'Joãozinho da Silva',
            'social_name' => 'Jão Silva',
            'birth_date' => '2005-06-15',
            'email' => 'joaozinho@email.com',
            'cpf' => '123.456.789-00',
            'gender' => 'male',
        ]);

        Contact::create([
            'person_id' => $person->id,
            'type' => 'whatsapp',
            'value' => '(85) 98888-7777',
        ]);

        Contact::create([
            'person_id' => $person->id,
            'type' => 'phone',
            'value' => '(85) 3333-2222',
        ]);

        Address::create([
            'person_id' => $person->id,
            'type' => 'residential',
            'street' => 'Avenida Santos Dumont',
            'number' => '1500',
            'complement' => 'Apto 302',
            'neighborhood' => 'Aldeota',
            'city_id' => $city->id,
            'zip_code' => '60150-160',
        ]);

        Document::create([
            'person_id' => $person->id,
            'type' => 'rg',
            'value' => '20050102030',
        ]);

        $person->loadMissing(['contacts', 'addresses.city.state', 'documents', 'contracts']);

        $columnMap = collect(PersonExporter::getColumns())
            ->mapWithKeys(fn (ExportColumn $column): array => [$column->getName() => $column->getLabel()])
            ->all();

        $exporter = new PersonExporter(new Export, $columnMap, []);
        $row = $exporter($person);

        // Verifica que dados básicos, contatos, endereços e documentos estão no resultado
        $this->assertContains('Joãozinho da Silva', $row);
        $this->assertContains('15/06/2005', $row);
        $this->assertContains('123.456.789-00', $row);
        $this->assertContains('(85) 98888-7777', $row); // WhatsApp
        $this->assertContains('Avenida Santos Dumont, 1500 (Apto 302) - Aldeota - Fortaleza/CE (CEP: 60150-160)', $row); // Endereço Completo
        $this->assertContains('Fortaleza', $row);
        $this->assertContains('CE', $row);
        $this->assertContains('20050102030', $row); // RG
    }

    public function test_contract_exporter_includes_items_schedules_installments_and_party_relations(): void
    {
        $school = School::create([
            'legal_name' => 'Escola Export Teste LTDA',
            'trade_name' => 'Escola Export Matriz',
            'cnpj' => '11.222.333/0001-44',
            'is_main' => true,
        ]);

        $student = Person::create([
            'name' => 'Estudante Export da Silva',
            'cpf' => '999.888.777-66',
            'email' => 'estudante.export@email.com',
        ]);

        Contact::create([
            'person_id' => $student->id,
            'type' => 'whatsapp',
            'value' => '(85) 99111-2222',
        ]);

        $contract = Contract::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'contract_number' => 'CTR-EXP-2026',
            'status' => 'active',
            'start_date' => '2026-03-01',
            'end_date' => '2026-12-20',
            'total_value' => 3000.00,
            'discount_value' => 300.00,
        ]);

        $formation = Formation::create(['name' => 'Informática & Robótica']);
        $course = Course::create(['name' => 'Lógica de Programação', 'workload' => 40]);

        $item = ContractItem::create([
            'contract_id' => $contract->id,
            'formation_id' => $formation->id,
        ]);

        ContractCourse::create([
            'contract_item_id' => $item->id,
            'course_id' => $course->id,
        ]);

        $classroom = Classroom::create(['name' => 'Laboratório 01', 'capacity' => 30, 'school_id' => $school->id]);
        StudentSchedule::create([
            'contract_id' => $contract->id,
            'contract_item_id' => $item->id,
            'weekday' => 1,
            'start_hour' => '14:00:00',
            'end_hour' => '15:00:00',
            'classroom_id' => $classroom->id,
        ]);

        $contract->update([
            'status' => 'suspended',
            'suspended_at' => '2026-07-01',
            'suspension_expected_return_date' => '2026-08-01',
            'suspension_reason' => 'Pausa para cirurgia',
        ]);

        $finType = FinancialType::create([
            'code' => '1.1.1',
            'name' => 'Mensalidade',
            'slug' => 'mensalidade_exp',
            'type' => 'revenue',
            'is_synthetic' => false,
            'is_active' => true,
        ]);

        Installment::create([
            'school_id' => $school->id,
            'contract_id' => $contract->id,
            'financial_type_id' => $finType->id,
            'installment_number' => 1,
            'total_installments' => 2,
            'amount' => 1350.00,
            'due_date' => '2026-04-10',
            'status' => 'paid',
        ]);

        Installment::create([
            'school_id' => $school->id,
            'contract_id' => $contract->id,
            'financial_type_id' => $finType->id,
            'installment_number' => 2,
            'total_installments' => 2,
            'amount' => 1350.00,
            'due_date' => '2026-05-10',
            'status' => 'pending',
        ]);

        $contract->loadMissing([
            'student.documents',
            'student.contacts',
            'student.addresses.city.state',
            'responsible.person.documents',
            'responsible.person.contacts',
            'contractType',
            'collaborator.person',
            'items.formation',
            'items.contractCourses.course',
            'schedules.classroom',
            'installments',
        ]);

        $columnMap = collect(ContractExporter::getColumns())
            ->mapWithKeys(fn (ExportColumn $column): array => [$column->getName() => $column->getLabel()])
            ->all();

        $exporter = new ContractExporter(new Export, $columnMap, []);
        $row = $exporter($contract);

        $this->assertContains('CTR-EXP-2026', $row);
        $this->assertContains('Suspenso', $row);
        $this->assertContains('Estudante Export da Silva', $row);
        $this->assertContains('999.888.777-66', $row);
        $this->assertContains('(85) 99111-2222', $row);
        $this->assertContains('Informática & Robótica', $row); // Formações
        $this->assertContains('Lógica de Programação', $row); // Cursos
        $this->assertContains('40', $row); // Carga horária
        $this->assertContains('1.350,00', $row); // Pago e pendente
        $this->assertContains('01/07/2026', $row); // Data de trancamento
        $this->assertContains('01/08/2026', $row); // Previsão de volta
        $this->assertContains('Pausa para cirurgia', $row); // Motivo
    }
}

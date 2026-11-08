<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Contract;
use App\Models\Installment;
use App\Models\Person;
use App\Models\Responsible;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractAndPersonDeletionTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'legal_name' => 'Escola Audit Teste',
            'trade_name' => 'Unidade Teste',
        ]);

        $person = Person::create(['name' => 'Operador Auditoria', 'email' => 'operador@test.com']);
        $this->user = User::create([
            'person_id' => $person->id,
            'name' => 'Operador Auditoria',
            'email' => 'operador@test.com',
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($this->user);
    }

    public function test_contract_deletion_is_blocked_if_it_has_paid_installments(): void
    {
        $student = Person::create(['name' => 'Aluno Com Pagamento', 'is_student' => true]);
        $contract = Contract::create([
            'contract_number' => 'CT-PAID-01',
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'start_date' => today(),
            'total_value' => 500.00,
            'status' => 'active',
        ]);

        Installment::create([
            'contract_id' => $contract->id,
            'amount' => 250.00,
            'due_date' => today(),
            'status' => 'paid',
            'paid_at' => today(),
            'amount_paid' => 250.00,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Não é possível excluir este contrato pois ele possui parcela(s) com pagamento já registrado no caixa/financeiro');

        $contract->delete();
    }

    public function test_contract_deletion_deletes_pending_installments_and_creates_audit_logs(): void
    {
        $student = Person::create(['name' => 'Aluno Sem Pagamento', 'is_student' => true]);
        $contract = Contract::create([
            'contract_number' => 'CT-UNPAID-01',
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'start_date' => today(),
            'total_value' => 300.00,
            'status' => 'active',
        ]);

        $inst1 = Installment::create([
            'contract_id' => $contract->id,
            'installment_number' => 1,
            'total_installments' => 2,
            'amount' => 150.00,
            'due_date' => today()->addDays(10),
            'status' => 'pending',
        ]);

        $inst2 = Installment::create([
            'contract_id' => $contract->id,
            'installment_number' => 2,
            'total_installments' => 2,
            'amount' => 150.00,
            'due_date' => today()->addDays(40),
            'status' => 'pending',
        ]);

        // Deleta o contrato
        $contract->delete();

        // As parcelas devem ter sido excluídas
        expect(Installment::query()->where('contract_id', $contract->id)->count())->toBe(0);

        // Deve existir auditoria para as parcelas e para o contrato
        $installmentAudits = AuditLog::query()
            ->where('auditable_type', Installment::class)
            ->whereIn('auditable_id', [$inst1->id, $inst2->id])
            ->where('event', 'deleted')
            ->get();

        expect($installmentAudits)->toHaveCount(2)
            ->and($installmentAudits->first()->actionLabel())->toBe('Parcela removida');

        $contractAudit = AuditLog::query()
            ->where('auditable_type', Contract::class)
            ->where('auditable_id', $contract->id)
            ->where('event', 'deleted')
            ->first();

        expect($contractAudit)->not->toBeNull()
            ->and($contractAudit->actionLabel())->toBe('Contrato excluído');
    }

    public function test_person_deletion_is_blocked_when_active_contract_exists(): void
    {
        $student = Person::create(['name' => 'Aluno Com Contrato Ativo', 'is_student' => true]);
        Contract::create([
            'contract_number' => 'CT-ACTIVE-01',
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'start_date' => today(),
            'total_value' => 500.00,
            'status' => 'active',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Esta pessoa não pode ser excluída pois possui contrato(s) registrado(s)');

        $student->delete();
    }

    public function test_person_can_be_deleted_after_contract_has_been_deleted_and_creates_audit_log(): void
    {
        $student = Person::create(['name' => 'Aluno Para Excluir', 'is_student' => true]);
        $contract = Contract::create([
            'contract_number' => 'CT-TO-DELETE-01',
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'start_date' => today(),
            'total_value' => 200.00,
            'status' => 'active',
        ]);

        Installment::create([
            'contract_id' => $contract->id,
            'installment_number' => 1,
            'total_installments' => 1,
            'amount' => 200.00,
            'due_date' => today()->addDays(5),
            'status' => 'pending',
        ]);

        // 1. Exclui o contrato
        $contract->delete();
        expect(Installment::query()->where('contract_id', $contract->id)->count())->toBe(0);

        // 2. Agora exclui a pessoa
        $studentId = $student->id;
        $student->delete();

        // A pessoa deve ter sido removida do banco
        expect(Person::query()->find($studentId))->toBeNull();

        // O contrato soft-deletado foi limpo do banco para não violar chaves estrangeiras
        expect(Contract::withoutGlobalScopes()->where('student_id', $studentId)->count())->toBe(0);

        // Deve existir registro de auditoria para a pessoa excluída
        $personAudit = AuditLog::query()
            ->where('auditable_type', Person::class)
            ->where('auditable_id', $studentId)
            ->where('event', 'deleted')
            ->first();

        expect($personAudit)->not->toBeNull()
            ->and($personAudit->actionLabel())->toBe('Pessoa excluída');
    }

    public function test_contracts_table_trashed_filter_displays_soft_deleted_contracts(): void
    {
        \Filament\Facades\Filament::setTenant($this->school);
        $this->user->givePermissionTo(\Spatie\Permission\Models\Permission::findOrCreate('ViewAny:Contract', 'web'));

        $student = Person::create(['name' => 'Aluno Filtro Excluído', 'is_student' => true]);
        $contract = Contract::create([
            'contract_number' => 'CT-TRASHED-99',
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'start_date' => today(),
            'total_value' => 100.00,
            'status' => 'active',
        ]);

        $contract->delete();

        \Livewire\Livewire::actingAs($this->user)
            ->test(\App\Filament\Resources\Contracts\Pages\ListContracts::class)
            ->assertCanNotSeeTableRecords([$contract])
            ->filterTable('trashed', true)
            ->assertCanSeeTableRecords([$contract]);
    }
}

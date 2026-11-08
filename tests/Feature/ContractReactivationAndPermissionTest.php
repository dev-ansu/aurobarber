<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Contract;
use App\Models\Person;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ContractReactivationAndPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('super_admin', 'web');
        Permission::findOrCreate('Reactivate:Contract', 'web');
        Permission::findOrCreate('Restore:Contract', 'web');
    }

    private function createContract(School $school, string $status = 'finished'): Contract
    {
        $person = Person::create([
            'school_id' => $school->id,
            'name' => 'Aluno Teste Reativação',
            'is_student' => true,
        ]);

        return Contract::create([
            'contract_number' => 'CT-TEST-'.uniqid(),
            'school_id' => $school->id,
            'student_id' => $person->id,
            'start_date' => now()->subMonths(3),
            'total_value' => 1200.00,
            'status' => $status,
            'finished_at' => $status === 'finished' ? now()->subDay() : null,
            'cancelled_at' => $status === 'canceled' ? now()->subDay() : null,
        ]);
    }

    private function createUser(string $email = 'user@example.com'): User
    {
        $person = Person::create([
            'name' => 'Operador Teste',
            'email' => $email,
        ]);

        return User::create([
            'person_id' => $person->id,
            'email' => $email,
            'password' => bcrypt('secret123'),
        ]);
    }

    public function test_user_without_permission_cannot_reactivate_contract(): void
    {
        $school = School::create([
            'legal_name' => 'Escola Teste',
            'trade_name' => 'Escola Alpha',
            'cnpj' => '11.111.111/0001-11',
        ]);

        $contract = $this->createContract($school, 'finished');

        $user = $this->createUser('noperm@example.com');

        $this->assertFalse(Gate::forUser($user)->allows('reactivate', $contract));
        $this->assertFalse(Gate::forUser($user)->allows('restore', $contract));
    }

    public function test_user_with_reactivate_permission_can_reactivate_and_restore_contract(): void
    {
        $school = School::create([
            'legal_name' => 'Escola Teste',
            'trade_name' => 'Escola Alpha',
            'cnpj' => '11.111.111/0001-11',
        ]);

        $contract = $this->createContract($school, 'finished');

        $user = $this->createUser('perm@example.com');
        $user->givePermissionTo('Reactivate:Contract');

        $this->assertTrue(Gate::forUser($user)->allows('reactivate', $contract));
        $this->assertTrue(Gate::forUser($user)->allows('restore', $contract));
    }

    public function test_reactivating_finished_contract_reverts_status_and_clears_finished_at(): void
    {
        $school = School::create([
            'legal_name' => 'Escola Teste',
            'trade_name' => 'Escola Alpha',
            'cnpj' => '11.111.111/0001-11',
        ]);

        $contract = $this->createContract($school, 'finished');

        $user = $this->createUser('reverter@example.com');
        $this->actingAs($user);

        $contract->reactivate('Conclusão acidental realizada pela recepção');

        $contract->refresh();

        expect($contract->status)->toBe('active')
            ->and($contract->finished_at)->toBeNull()
            ->and($contract->cancelled_at)->toBeNull();

        // Verifica gravação no histórico de status
        $lastHistory = $contract->statusHistories()->first();
        expect($lastHistory)->not->toBeNull()
            ->and($lastHistory->from_status)->toBe('finished')
            ->and($lastHistory->to_status)->toBe('active')
            ->and($lastHistory->reason)->toContain('Reversão de status (Conclusão desfeita)')
            ->and($lastHistory->reason)->toContain('Conclusão acidental realizada pela recepção');

        // Verifica registro de auditoria
        $lastAudit = AuditLog::query()
            ->where('auditable_type', Contract::class)
            ->where('auditable_id', $contract->id)
            ->latest('id')
            ->first();

        expect($lastAudit)->not->toBeNull()
            ->and($lastAudit->actionLabel())->toBe('Reversão de status (Conclusão desfeita)');
    }

    public function test_reactivating_canceled_contract_reverts_status_and_clears_cancelled_at(): void
    {
        $school = School::create([
            'legal_name' => 'Escola Teste',
            'trade_name' => 'Escola Alpha',
            'cnpj' => '11.111.111/0001-11',
        ]);

        $contract = $this->createContract($school, 'canceled');

        $user = $this->createUser('cancel_reverter@example.com');
        $this->actingAs($user);

        $contract->reactivate('Aluno desistiu do cancelamento e retornará às aulas');

        $contract->refresh();

        expect($contract->status)->toBe('active')
            ->and($contract->cancelled_at)->toBeNull()
            ->and($contract->cancellation_reason)->toBeNull();

        $lastAudit = AuditLog::query()
            ->where('auditable_type', Contract::class)
            ->where('auditable_id', $contract->id)
            ->latest('id')
            ->first();

        expect($lastAudit)->not->toBeNull()
            ->and($lastAudit->actionLabel())->toBe('Reversão de status (Cancelamento desfeito)');
    }

    public function test_contract_soft_delete_and_restore_records_audit(): void
    {
        $school = School::create([
            'legal_name' => 'Escola Teste',
            'trade_name' => 'Escola Alpha',
            'cnpj' => '11.111.111/0001-11',
        ]);

        $contract = $this->createContract($school, 'active');

        $user = $this->createUser('soft_delete_user@example.com');
        $this->actingAs($user);

        $contractId = $contract->id;

        // Exclui acidentalmente
        $contract->delete();

        expect(Contract::query()->find($contractId))->toBeNull()
            ->and(Contract::withTrashed()->find($contractId))->not->toBeNull();

        // Restaura
        $trashedContract = Contract::withTrashed()->find($contractId);
        $trashedContract->restore();

        expect(Contract::query()->find($contractId))->not->toBeNull()
            ->and($trashedContract->fresh()->deleted_at)->toBeNull();

        // Verifica auditoria de restauração
        $restoreAudit = AuditLog::query()
            ->where('auditable_type', Contract::class)
            ->where('auditable_id', $contractId)
            ->where('event', 'restored')
            ->first();

        expect($restoreAudit)->not->toBeNull()
            ->and($restoreAudit->actionLabel())->toBe('Reversão de status (Contrato restaurado)');
    }
}

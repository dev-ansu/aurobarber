<?php

use App\Enums\RelationshipEnum;
use App\Models\AuditLog;
use App\Models\CashRegister;
use App\Models\CashTransaction;
use App\Models\Contract;
use App\Models\Installment;
use App\Models\Person;
use App\Models\Responsible;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

function makeAuditTestContract(string $suffix): Contract
{
    $student = Person::create([
        'name' => "Aluno {$suffix}",
        'email' => "audit-student-{$suffix}@example.com",
    ]);
    $responsiblePerson = Person::create([
        'name' => "Responsável {$suffix}",
        'email' => "audit-responsible-{$suffix}@example.com",
    ]);
    $responsible = Responsible::create([
        'person_id' => $responsiblePerson->id,
        'relationship' => RelationshipEnum::Mother,
        'financial_responsible' => true,
    ]);

    return Contract::create([
        'contract_number' => "AUDIT-{$suffix}",
        'student_id' => $student->id,
        'responsible_id' => $responsible->id,
        'start_date' => '2026-10-01',
        'total_value' => 1000,
        'status' => 'draft',
    ]);
}

function makeAuditTestUser(string $suffix): User
{
    $person = Person::create([
        'name' => "Operador {$suffix}",
        'email' => "audit-user-{$suffix}@example.com",
    ]);

    return User::create([
        'person_id' => $person->id,
        'email' => "audit-user-{$suffix}@example.com",
        'password' => Hash::make('password'),
    ]);
}

test('contract audit identifies its creator and records before and after values', function () {
    $user = makeAuditTestUser('CONTRACT');
    $this->actingAs($user);

    $contract = makeAuditTestContract('CONTRACT');
    $contract->update(['total_value' => 1250]);
    $createdLog = $contract->auditLogs()->where('event', 'created')->firstOrFail();
    $updatedLog = $contract->auditLogs()->where('event', 'updated')->firstOrFail();

    expect($contract->created_by_id)->toBe($user->id)
        ->and($contract->createdBy->is($user))->toBeTrue()
        ->and($createdLog->user_id)->toBe($user->id)
        ->and($createdLog->created_at)->not->toBeNull()
        ->and($updatedLog->changes['before']['total_value'])->toBe(1000)
        ->and($updatedLog->changes['after']['total_value'])->toBe(1250);
});

test('cash transaction audit keeps the creating operator and edited amounts', function () {
    $user = makeAuditTestUser('FINANCE');
    $this->actingAs($user);
    $register = CashRegister::create([
        'name' => 'Caixa de auditoria',
        'status' => 'open',
        'opening_balance' => 0,
        'current_balance' => 0,
        'user_id' => $user->id,
        'opened_at' => now(),
    ]);

    $transaction = CashTransaction::create([
        'cash_register_id' => $register->id,
        'user_id' => $user->id,
        'type' => 'input',
        'amount' => 100,
        'description' => 'Recebimento de teste',
    ]);
    $transaction->update(['amount' => 110]);

    $updatedLog = $transaction->auditLogs()->where('event', 'updated')->firstOrFail();

    expect($transaction->auditLogs()->where('event', 'created')->firstOrFail()->user_id)->toBe($user->id)
        ->and($updatedLog->user_id)->toBe($user->id)
        ->and($updatedLog->changes['before']['amount'])->toBe(100)
        ->and($updatedLog->changes['after']['amount'])->toBe(110);
});

test('deleting an installment records the operator and a readable snapshot', function () {
    $user = makeAuditTestUser('INSTALLMENT-DELETE');
    $this->actingAs($user);
    $contract = makeAuditTestContract('INSTALLMENT-DELETE');
    $installment = Installment::create([
        'contract_id' => $contract->id,
        'installment_number' => 2,
        'total_installments' => 6,
        'amount' => 150,
        'due_date' => '2026-10-20',
        'status' => 'pending',
    ]);
    $installmentId = $installment->id;

    $installment->delete();

    $auditLog = AuditLog::query()
        ->where('auditable_type', Installment::class)
        ->where('auditable_id', $installmentId)
        ->where('event', 'deleted')
        ->firstOrFail();

    expect($auditLog->user_id)->toBe($user->id)
        ->and($auditLog->contract_id)->toBe($contract->id)
        ->and((float) $auditLog->changes['before']['amount'])->toBe(150.0)
        ->and($auditLog->changes['before']['status'])->toBe('pending')
        ->and($auditLog->changes['after'])->toBe([])
        ->and($auditLog->actionLabel())->toBe('Parcela removida')
        ->and($auditLog->recordLabel())->toBe('Parcela 2 / 6')
        ->and($installment->exists)->toBeFalse();
});

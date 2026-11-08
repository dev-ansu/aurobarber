<?php

use App\Models\Installment;
use App\Models\User;
use App\Policies\InstallmentPolicy;
use Illuminate\Database\Eloquent\Relations\HasMany;

test('an installment can only be deleted with permission and when it has no payment history', function () {
    $user = Mockery::mock(User::class);
    $user->shouldReceive('can')->with('Delete:Installment')->andReturnTrue();

    $installment = Mockery::mock(Installment::class)->makePartial();
    $installment->shouldReceive('isDeletable')->andReturnTrue();

    expect((new InstallmentPolicy)->delete($user, $installment))->toBeTrue();

    $userWithoutPermission = Mockery::mock(User::class);
    $userWithoutPermission->shouldReceive('can')->with('Delete:Installment')->andReturnFalse();

    expect((new InstallmentPolicy)->delete($userWithoutPermission, $installment))->toBeFalse();

    $installmentWithHistory = Mockery::mock(Installment::class)->makePartial();
    $installmentWithHistory->shouldReceive('isDeletable')->andReturnFalse();

    expect((new InstallmentPolicy)->delete($user, $installmentWithHistory))->toBeFalse();
});

test('bulk installment deletion is not authorized', function () {
    $user = Mockery::mock(User::class);

    expect((new InstallmentPolicy)->deleteAny($user))->toBeFalse();
});

test('paid or transacted installments are not deletable', function () {
    $transactions = Mockery::mock(HasMany::class);
    $transactions->shouldReceive('exists')->andReturnFalse();

    $paidInstallment = Mockery::mock(Installment::class)->makePartial();
    $paidInstallment->status = 'paid';
    $paidInstallment->shouldReceive('transactions')->andReturn($transactions);

    expect($paidInstallment->isDeletable())->toBeFalse();

    $transactions = Mockery::mock(HasMany::class);
    $transactions->shouldReceive('exists')->andReturnTrue();

    $transactedInstallment = Mockery::mock(Installment::class)->makePartial();
    $transactedInstallment->status = 'pending';
    $transactedInstallment->shouldReceive('transactions')->andReturn($transactions);

    expect($transactedInstallment->isDeletable())->toBeFalse();

    $transactions = Mockery::mock(HasMany::class);
    $transactions->shouldReceive('exists')->andReturnFalse();

    $pendingInstallment = Mockery::mock(Installment::class)->makePartial();
    $pendingInstallment->status = 'pending';
    $pendingInstallment->shouldReceive('transactions')->andReturn($transactions);

    expect($pendingInstallment->isDeletable())->toBeTrue();
});

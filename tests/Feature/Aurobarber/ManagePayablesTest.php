<?php

use App\Filament\Resources\Payables\Pages\ManagePayables;
use App\Filament\Resources\Payables\PayableResource;
use App\Models\Barbershop;
use App\Models\Payable;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('payables are managed from one Filament resource page and created in the active tenant', function () {
    $barbershop = Barbershop::create([
        'name' => 'Barbearia Teste',
        'slug' => 'barbearia-teste',
    ]);
    $otherBarbershop = Barbershop::create([
        'name' => 'Outra Barbearia',
        'slug' => 'outra-barbearia',
    ]);

    $owner = User::create([
        'name' => 'Dono Teste',
        'email' => 'dono@example.test',
        'password' => 'password',
        'role' => 'owner',
        'barbershop_id' => $barbershop->id,
        'is_active' => true,
    ]);
    $owner->barbershops()->attach($barbershop->id, [
        'role' => 'owner',
        'is_primary' => true,
    ]);

    $otherPayable = Payable::create([
        'barbershop_id' => $otherBarbershop->id,
        'description' => 'Despesa de outra unidade',
        'amount' => 80,
        'due_date' => today()->addDay(),
    ]);

    Filament::setTenant($barbershop, isQuiet: true);
    $this->actingAs($owner);

    expect(array_keys(PayableResource::getPages()))->toBe(['index'])
        ->and(PayableResource::getPages()['index']->getPage())->toBe(ManagePayables::class);

    Livewire::test(ManagePayables::class)
        ->callAction('create', [
            'description' => 'Conta de energia',
            'amount' => 120,
            'due_date' => today()->addDays(3)->toDateString(),
        ])
        ->assertCanSeeTableRecords(Payable::where('barbershop_id', $barbershop->id)->get())
        ->assertCanNotSeeTableRecords([$otherPayable]);

    $payable = Payable::where('description', 'Conta de energia')->sole();

    expect($payable->barbershop_id)->toBe($barbershop->id)
        ->and($payable->created_by_id)->toBe($owner->id);
});

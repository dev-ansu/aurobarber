<?php

use App\Filament\Resources\ServiceResource;
use App\Filament\Resources\ServiceResource\Pages\CreateService;
use App\Filament\Resources\ServiceResource\Pages\ListServices;
use App\Models\Barbershop;
use App\Models\Service;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('service catalog only returns services for the authorized active tenant', function () {
    $currentBarbershop = Barbershop::create([
        'name' => 'Barbearia Atual',
        'slug' => 'barbearia-atual',
    ]);
    $otherBarbershop = Barbershop::create([
        'name' => 'Outra Barbearia',
        'slug' => 'outra-barbearia',
    ]);

    $owner = User::create([
        'name' => 'Dona Teste',
        'email' => 'dona@example.test',
        'password' => 'password',
        'role' => 'owner',
        'barbershop_id' => $currentBarbershop->id,
        'is_active' => true,
    ]);
    $owner->barbershops()->attach($currentBarbershop->id, [
        'role' => 'owner',
        'is_primary' => true,
    ]);

    $currentService = Service::create([
        'barbershop_id' => $currentBarbershop->id,
        'name' => 'Serviço da unidade atual',
        'price' => 40,
        'duration_minutes' => 30,
    ]);
    Service::create([
        'barbershop_id' => $otherBarbershop->id,
        'name' => 'Serviço de outra unidade',
        'price' => 50,
        'duration_minutes' => 30,
    ]);

    Filament::setTenant($currentBarbershop, isQuiet: true);
    $this->actingAs($owner);

    expect(ServiceResource::getEloquentQuery()->pluck('id')->all())
        ->toBe([$currentService->id]);

    Livewire::test(ListServices::class)
        ->assertCanSeeTableRecords([$currentService])
        ->assertCanNotSeeTableRecords(
            Service::where('barbershop_id', $otherBarbershop->id)->get(),
        );
});

test('creating a service uses the active tenant instead of the user fallback id', function () {
    Barbershop::create([
        'name' => 'Outra Barbearia',
        'slug' => 'outra-barbearia',
    ]);

    $barbershop = Barbershop::create([
        'name' => 'Barbearia da Cliente',
        'slug' => 'barbearia-da-cliente',
    ]);

    $owner = User::create([
        'name' => 'Dona Teste',
        'email' => 'dona@example.test',
        'password' => 'password',
        'role' => 'owner',
        'is_active' => true,
    ]);
    $owner->barbershops()->attach($barbershop->id, [
        'role' => 'owner',
        'is_primary' => true,
    ]);

    Filament::setTenant($barbershop, isQuiet: true);
    $this->actingAs($owner);

    $method = new ReflectionMethod(CreateService::class, 'mutateFormDataBeforeCreate');
    $method->setAccessible(true);
    $data = $method->invoke(new CreateService, [
        'name' => 'Serviço Teste',
        'price' => 40,
        'duration_minutes' => 30,
    ]);
    $service = Service::create($data);

    expect($service->barbershop_id)->toBe($barbershop->id);
});

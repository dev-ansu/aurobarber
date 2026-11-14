<?php

use App\Filament\Resources\ProfessionalResource\Pages\CreateProfessional;
use App\Models\Barbershop;
use App\Models\Professional;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('creating a professional uses the active tenant instead of the user fallback id', function () {
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

    $method = new ReflectionMethod(CreateProfessional::class, 'mutateFormDataBeforeCreate');
    $method->setAccessible(true);
    $data = $method->invoke(new CreateProfessional, ['name' => 'Barbeiro Teste']);
    $professional = Professional::create($data);

    expect($professional->barbershop_id)->toBe($barbershop->id);
});

test('professional catalog only returns professionals for the authorized active tenant', function () {
    $tenant1 = Barbershop::create(['name' => 'Barbearia 1', 'slug' => 'barbearia-1']);
    $tenant2 = Barbershop::create(['name' => 'Barbearia 2', 'slug' => 'barbearia-2']);

    $pro1 = Professional::create(['barbershop_id' => $tenant1->id, 'name' => 'Barbeiro 1']);
    $pro2 = Professional::create(['barbershop_id' => $tenant2->id, 'name' => 'Barbeiro 2']);

    Filament::setTenant($tenant1, isQuiet: true);

    expect(\App\Filament\Resources\ProfessionalResource::getEloquentQuery()->pluck('id')->all())
        ->toBe([$pro1->id]);
});

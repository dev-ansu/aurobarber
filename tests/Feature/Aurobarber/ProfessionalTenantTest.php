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

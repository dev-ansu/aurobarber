<?php

use App\Models\Barbershop;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('barbershop owners are not global admins and cannot access unrelated tenants', function () {
    $superAdmin = Role::firstOrCreate([
        'name' => 'super_admin',
        'guard_name' => 'web',
    ]);

    $owner = User::create([
        'name' => 'Dono Teste',
        'email' => 'owner@example.test',
        'password' => 'password',
        'role' => 'owner',
        'is_active' => true,
    ]);

    $testShop = Barbershop::create([
        'name' => 'Barbearia Teste',
        'slug' => 'barbearia-teste',
        'owner_id' => $owner->id,
    ]);

    $marinhoShop = Barbershop::create([
        'name' => 'Barbearia Marinho',
        'slug' => 'barbearia-marinho',
    ]);

    $owner->barbershops()->attach($testShop->id, [
        'role' => 'owner',
        'is_primary' => true,
    ]);
    $owner->assignRole($superAdmin);

    $migration = require database_path('migrations/2026_11_09_220000_restrict_global_admin_role_to_saas.php');
    $migration->up();

    $owner->refresh();
    Filament::setTenant($testShop, isQuiet: true);

    expect($owner->hasRole('super_admin'))->toBeFalse()
        ->and($owner->hasRole('barbershop_owner'))->toBeTrue()
        ->and($owner->canAccessTenant($testShop))->toBeTrue()
        ->and($owner->canAccessTenant($marinhoShop))->toBeFalse()
        ->and(Gate::forUser($owner)->allows('unassigned-ability'))->toBeTrue();

    Filament::setTenant($marinhoShop, isQuiet: true);

    expect(Gate::forUser($owner)->allows('unassigned-ability'))->toBeFalse();
});

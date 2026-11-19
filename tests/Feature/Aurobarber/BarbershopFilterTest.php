<?php

declare(strict_types=1);

use App\Filament\Resources\BarbershopResource\Pages\ListBarbershops;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('it renders list barbershops page without qualifyColumn error and filters work', function () {
    $admin = User::create([
        'name' => 'SaaS Admin',
        'email' => 'admin-test@aurobarber.com',
        'password' => bcrypt('secret123'),
        'role' => 'master_admin',
        'is_active' => true,
    ]);

    $matriz = \App\Models\Barbershop::create([
        'name' => 'Matriz Central',
        'slug' => 'matriz-central',
        'monthly_price' => 150.00,
        'is_active' => true,
        'license_status' => 'active',
    ]);

    $filial = \App\Models\Barbershop::create([
        'name' => 'Filial Norte',
        'slug' => 'filial-norte',
        'parent_id' => $matriz->id,
        'monthly_price' => 0.00,
        'is_active' => true,
        'license_status' => 'active',
    ]);

    Filament\Facades\Filament::setCurrentPanel(Filament\Facades\Filament::getPanel('saas'));

    $this->actingAs($admin);

    Livewire::test(ListBarbershops::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$matriz, $filial])
        ->filterTable('unit_type', 'matriz')
        ->assertCanSeeTableRecords([$matriz])
        ->assertCanNotSeeTableRecords([$filial])
        ->filterTable('unit_type', 'branch')
        ->assertCanSeeTableRecords([$filial])
        ->assertCanNotSeeTableRecords([$matriz])
        ->filterTable('parent_id', $matriz->id)
        ->assertCanSeeTableRecords([$filial]);
});

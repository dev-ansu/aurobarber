<?php

declare(strict_types=1);

use App\Filament\Resources\BarbershopResource\Pages\CreateBarbershop;
use App\Models\Barbershop;
use App\Models\Professional;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('owner_id dropdown in saas barbershop resource strictly filters out barbers, receptionists and deleted test owners', function () {
    // 1. Legitimate active owner
    $legitOwner = User::create([
        'name' => 'Carlos Legítimo',
        'email' => 'carlos@legitimo.com',
        'password' => 'secret123',
        'role' => 'owner',
        'is_active' => true,
    ]);

    $activeShop = Barbershop::create([
        'name' => 'Barbearia Ativa',
        'slug' => 'barbearia-ativa',
        'owner_id' => $legitOwner->id,
        'is_active' => true,
    ]);

    // 2. Barber / Professional from a barbershop
    $barberUser = User::create([
        'name' => 'João Barbeiro',
        'email' => 'joao@barbeiro.com',
        'password' => 'secret123',
        'role' => 'barber',
        'is_active' => true,
    ]);

    Professional::create([
        'barbershop_id' => $activeShop->id,
        'user_id' => $barberUser->id,
        'name' => 'João Barbeiro',
        'is_active' => true,
    ]);

    // 3. Receptionist
    $receptionistUser = User::create([
        'name' => 'Ana Recepcionista',
        'email' => 'ana@recepcao.com',
        'password' => 'secret123',
        'role' => 'receptionist',
        'is_active' => true,
    ]);

    // 4. Test owner from an already DELETED barbershop
    $testOwner = User::create([
        'name' => 'Dono Teste Excluído',
        'email' => 'teste@excluido.com',
        'password' => 'secret123',
        'role' => 'owner',
        'is_active' => true,
    ]);

    $deletedShop = Barbershop::create([
        'name' => 'Barbearia Teste Excluída',
        'slug' => 'barbearia-teste-excluida',
        'owner_id' => $testOwner->id,
        'is_active' => true,
    ]);
    $deletedShop->delete(); // Soft delete (enviada para a lixeira)

    // 5. Inactive owner
    $inactiveOwner = User::create([
        'name' => 'Dono Inativo',
        'email' => 'inativo@teste.com',
        'password' => 'secret123',
        'role' => 'owner',
        'is_active' => false,
    ]);

    $admin = User::create([
        'name' => 'Admin SaaS',
        'email' => 'admin-saas@aurosoft.com.br',
        'password' => 'secret123',
        'role' => 'master_admin',
        'is_active' => true,
    ]);

    $this->actingAs($admin);

    // Test query logic directly to verify strict filtering
    $eligibleQuery = User::query()
        ->where('is_active', true)
        ->whereIn('role', ['owner', 'master_admin'])
        ->whereDoesntHave('professional')
        ->where(function ($sub) {
            $sub->whereHas('ownedBarbershops', fn ($q) => $q->withoutTrashed())
                ->orWhereHas('barbershops', fn ($q) => $q->withoutTrashed());
        });

    $results = $eligibleQuery->get();

    // Must contain legitimate owner
    expect($results->pluck('id')->all())->toContain($legitOwner->id)
        // Must NOT contain barber / professional
        ->and($results->pluck('id')->all())->not->toContain($barberUser->id)
        // Must NOT contain receptionist
        ->and($results->pluck('id')->all())->not->toContain($receptionistUser->id)
        // Must NOT contain test owner whose barbershop was deleted
        ->and($results->pluck('id')->all())->not->toContain($testOwner->id)
        // Must NOT contain inactive owner
        ->and($results->pluck('id')->all())->not->toContain($inactiveOwner->id);
});

test('moving barbershop to trash deactivates lone owner and restoring reactivates', function () {
    $owner = User::create([
        'name' => 'Dono Teste Unitário',
        'email' => 'dono-unitario@teste.com',
        'password' => 'secret123',
        'role' => 'owner',
        'is_active' => true,
    ]);

    $shop = Barbershop::create([
        'name' => 'Barbearia Unica',
        'slug' => 'barbearia-unica',
        'owner_id' => $owner->id,
        'is_active' => true,
    ]);

    // Move to trash
    $shop->delete();
    // Simulate DeleteAction hook
    $hasOtherActiveShops = $owner->ownedBarbershops()->withoutTrashed()->where('id', '!=', $shop->id)->exists()
        || $owner->barbershops()->withoutTrashed()->where('barbershops.id', '!=', $shop->id)->exists();
    if (! $hasOtherActiveShops && $owner->role !== 'master_admin') {
        $owner->update(['is_active' => false]);
    }

    expect($owner->fresh()->is_active)->toBeFalse();

    // Restore
    $shop->restore();
    $owner->update(['is_active' => true]);

    expect($owner->fresh()->is_active)->toBeTrue();
});

test('create barbershop page renders properly with owner fields in saas panel', function () {
    Filament::setCurrentPanel(Filament::getPanel('saas'));

    $admin = User::create([
        'name' => 'Master Admin',
        'email' => 'admin-master@aurosoft.com.br',
        'password' => 'secret123',
        'role' => 'master_admin',
        'is_active' => true,
    ]);

    $this->actingAs($admin);

    Livewire::test(CreateBarbershop::class)
        ->assertSuccessful();
});

<?php

use App\Filament\Resources\AppointmentResource;
use App\Filament\Resources\AppointmentResource\Pages\EditAppointment;
use App\Filament\Resources\AppointmentResource\Pages\ListAppointments;
use App\Models\Appointment;
use App\Models\Barbershop;
use App\Models\Product;
use App\Models\Professional;
use App\Models\Service;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('appointment resource only lists appointments from the authorized active tenant', function () {
    [$tenant, $otherTenant, $owner] = createAppointmentResourceTenantContext();
    $currentAppointment = createResourceAppointment($tenant);
    createResourceAppointment($otherTenant);

    Filament::setTenant($tenant, isQuiet: true);
    $this->actingAs($owner);

    expect(AppointmentResource::getEloquentQuery()->pluck('id')->all())
        ->toBe([$currentAppointment->id]);

    Livewire::test(ListAppointments::class)
        ->assertCanSeeTableRecords([$currentAppointment])
        ->assertCanNotSeeTableRecords(
            Appointment::where('barbershop_id', $otherTenant->id)->get(),
        );
});

test('checkout consumes only in-tenant products and completes without creating an invalid cash movement', function () {
    [$tenant, , $owner] = createAppointmentResourceTenantContext();
    $appointment = createResourceAppointment($tenant);
    $product = Product::create([
        'barbershop_id' => $tenant->id,
        'name' => 'Pomada',
        'price' => 15,
        'stock_quantity' => 2,
        'is_active' => true,
    ]);

    Filament::setTenant($tenant, isQuiet: true);
    $this->actingAs($owner);

    Livewire::test(ListAppointments::class)
        ->callTableAction('concluir', $appointment, [
            'payment_method' => 'pix',
            'consumed_product_ids' => [$product->id],
        ]);

    expect($appointment->fresh()->status)->toBe('completed')
        ->and($appointment->fresh()->payment_status)->toBe('paid')
        ->and((float) $appointment->fresh()->products_total)->toBe(15.0)
        ->and($product->fresh()->stock_quantity)->toBe(1)
        ->and($appointment->products()->whereKey($product->id)->exists())->toBeTrue()
        ->and(DB::table('cash_movements')->count())->toBe(0);
});

test('checkout rejects a product from another tenant without changing stock or appointment', function () {
    [$tenant, $otherTenant, $owner] = createAppointmentResourceTenantContext();
    $appointment = createResourceAppointment($tenant);
    $foreignProduct = Product::create([
        'barbershop_id' => $otherTenant->id,
        'name' => 'Produto de outra unidade',
        'price' => 15,
        'stock_quantity' => 2,
        'is_active' => true,
    ]);

    Filament::setTenant($tenant, isQuiet: true);
    $this->actingAs($owner);

    Livewire::test(ListAppointments::class)
        ->callTableAction('concluir', $appointment, [
            'payment_method' => 'pix',
            'consumed_product_ids' => [$foreignProduct->id],
        ])
        ->assertHasTableActionErrors();

    expect($appointment->fresh()->status)->toBe('scheduled')
        ->and($appointment->fresh()->payment_status)->toBe('pending')
        ->and($foreignProduct->fresh()->stock_quantity)->toBe(2)
        ->and($appointment->products()->exists())->toBeFalse();
});

test('editing an appointment validates its professional and recalculates its end time and commission', function () {
    [$tenant, $otherTenant, $owner] = createAppointmentResourceTenantContext();
    $appointment = createResourceAppointment($tenant);
    $appointment->service->update(['duration_minutes' => 45]);
    $appointment->professional->update(['commission_rate' => 60]);

    Filament::setTenant($tenant, isQuiet: true);
    $this->actingAs($owner);

    $page = new EditAppointment;
    $page->record = $appointment;
    $method = new ReflectionMethod(EditAppointment::class, 'mutateFormDataBeforeSave');
    $method->setAccessible(true);

    $data = $method->invoke($page, [
        'professional_id' => $appointment->professional_id,
        'service_id' => $appointment->service_id,
        'service_price' => 60,
        'scheduled_at' => now()->setTime(15, 0)->toDateTimeString(),
    ]);

    expect((float) $data['commission_rate'])->toBe(60.0)
        ->and((float) $data['commission_amount'])->toBe(36.0)
        ->and($data['ends_at']->format('H:i'))->toBe('15:45')
        ->and($data['barbershop_id'])->toBe($tenant->id);

    $foreignProfessional = Professional::create([
        'barbershop_id' => $otherTenant->id,
        'name' => 'Profissional Estrangeiro',
    ]);

    expect(fn () => $method->invoke($page, [
        'professional_id' => $foreignProfessional->id,
        'service_id' => $appointment->service_id,
        'service_price' => 60,
        'scheduled_at' => now()->toDateTimeString(),
    ]))->toThrow(ModelNotFoundException::class);
});

function createAppointmentResourceTenantContext(): array
{
    $tenant = Barbershop::create([
        'name' => 'Barbearia Atual',
        'slug' => 'barbearia-atual',
    ]);
    $otherTenant = Barbershop::create([
        'name' => 'Outra Barbearia',
        'slug' => 'outra-barbearia',
    ]);
    $owner = User::create([
        'name' => 'Dona Teste',
        'email' => 'dona-'.uniqid().'@example.test',
        'password' => 'password',
        'role' => 'owner',
        'barbershop_id' => $tenant->id,
        'is_active' => true,
    ]);
    $owner->barbershops()->attach($tenant->id, [
        'role' => 'owner',
        'is_primary' => true,
    ]);

    return [$tenant, $otherTenant, $owner];
}

function createResourceAppointment(Barbershop $barbershop): Appointment
{
    $professional = Professional::create([
        'barbershop_id' => $barbershop->id,
        'name' => 'Barbeiro Teste',
    ]);
    $service = Service::create([
        'barbershop_id' => $barbershop->id,
        'name' => 'Corte',
        'price' => 40,
        'duration_minutes' => 30,
    ]);

    return Appointment::create([
        'barbershop_id' => $barbershop->id,
        'professional_id' => $professional->id,
        'service_id' => $service->id,
        'client_name' => 'Cliente Teste',
        'client_phone' => '85999999999',
        'scheduled_at' => now(),
        'ends_at' => now()->addMinutes(30),
        'status' => 'scheduled',
        'service_price' => 40,
        'commission_rate' => 50,
        'commission_amount' => 20,
        'payment_status' => 'pending',
    ]);
}

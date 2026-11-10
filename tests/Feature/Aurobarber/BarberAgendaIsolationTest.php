<?php

use App\Filament\Pages\DailySchedule;
use App\Filament\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Models\Barbershop;
use App\Models\Professional;
use App\Models\Service;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a restricted barber sees and can only act on their own appointments', function () {
    $barbershop = Barbershop::create([
        'name' => 'Barbearia Teste',
        'slug' => 'barbearia-teste',
    ]);

    $barber = User::create([
        'name' => 'Barbeiro Teste',
        'email' => 'barbeiro@example.test',
        'password' => 'password',
        'role' => 'barber',
        'barbershop_id' => $barbershop->id,
        'is_active' => true,
    ]);
    $barber->barbershops()->attach($barbershop->id, [
        'role' => 'barber',
        'is_primary' => true,
    ]);

    $barberProfessional = Professional::create([
        'barbershop_id' => $barbershop->id,
        'user_id' => $barber->id,
        'name' => $barber->name,
    ]);
    $otherProfessional = Professional::create([
        'barbershop_id' => $barbershop->id,
        'name' => 'Outro Barbeiro',
    ]);
    $service = Service::create([
        'barbershop_id' => $barbershop->id,
        'name' => 'Corte',
        'price' => 40,
        'duration_minutes' => 30,
    ]);

    $createAppointment = function (Professional $professional) use ($barbershop, $service): Appointment {
        return Appointment::create([
            'barbershop_id' => $barbershop->id,
            'professional_id' => $professional->id,
            'service_id' => $service->id,
            'client_name' => 'Cliente Teste',
            'client_phone' => '85999999999',
            'scheduled_at' => now(),
            'ends_at' => now()->addMinutes(30),
            'status' => 'confirmed',
            'service_price' => 40,
            'commission_rate' => 50,
            'commission_amount' => 20,
        ]);
    };

    $ownAppointment = $createAppointment($barberProfessional);
    $otherAppointment = $createAppointment($otherProfessional);

    Filament::setTenant($barbershop, isQuiet: true);
    $this->actingAs($barber);

    $page = new DailySchedule;
    $page->selectedDate = now()->format('Y-m-d');

    expect($barber->isRestrictedBarber())->toBeTrue()
        ->and($page->getTitle())->toBe('Minha Agenda Diária')
        ->and($page->getDailyAppointmentsProperty()->modelKeys())->toBe([$ownAppointment->id])
        ->and(AppointmentResource::getEloquentQuery()->pluck('id')->all())->toBe([$ownAppointment->id]);

    expect(fn () => $page->updateStatus($otherAppointment->id, 'completed'))
        ->toThrow(ModelNotFoundException::class);

    expect(fn () => $page->openCheckoutModal($otherAppointment->id))
        ->toThrow(ModelNotFoundException::class);

    $page->checkoutAppointmentId = $otherAppointment->id;

    expect(fn () => $page->confirmCheckout())
        ->toThrow(ModelNotFoundException::class);

    expect($otherAppointment->fresh()->status)->toBe('confirmed');

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

    $this->actingAs($owner);

    $teamPage = new DailySchedule;
    $teamPage->selectedDate = now()->format('Y-m-d');

    expect($owner->isRestrictedBarber())->toBeFalse()
        ->and($teamPage->getTitle())->toBe('Agenda Diária da Equipe')
        ->and($teamPage->getDailyAppointmentsProperty()->count())->toBe(2)
        ->and(AppointmentResource::getEloquentQuery()->count())->toBe(2);
});

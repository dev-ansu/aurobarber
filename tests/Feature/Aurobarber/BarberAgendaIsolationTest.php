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
use Livewire\Livewire;

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

test('a shop owner can create a quick appointment from the daily schedule using the Filament action', function () {
    $barbershop = Barbershop::create([
        'name' => 'Barbearia Teste',
        'slug' => 'barbearia-teste',
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

    $professional = Professional::create([
        'barbershop_id' => $barbershop->id,
        'name' => 'Barbeiro Teste',
    ]);
    $services = collect(range(1, 5))->map(fn (int $number): Service => Service::create([
        'barbershop_id' => $barbershop->id,
        'name' => "Serviço {$number}",
        'price' => 40 + $number,
        'duration_minutes' => 30,
        'is_active' => true,
    ]));
    $service = $services->last();

    Filament::setTenant($barbershop, isQuiet: true);
    $this->actingAs($owner);

    $page = new DailySchedule;
    $page->selectedDate = now()->format('Y-m-d');
    expect($page->services->pluck('id')->all())->toBe($services->pluck('id')->all());

    Livewire::test(DailySchedule::class)
        ->mountAction('quickAppointment', [
            'professionalId' => $professional->id,
            'time' => '14:00',
        ])
        ->fillForm([
            'professional_id' => $professional->id,
            'service_id' => $service->id,
            'scheduled_at' => now()->setTime(14, 0)->toDateTimeString(),
            'client_name' => 'Cliente Balcão',
            'client_phone' => '85999999999',
        ])
        ->callMountedAction();

    $appointment = Appointment::query()->where('client_name', 'Cliente Balcão')->sole();

    expect($appointment->barbershop_id)->toBe($barbershop->id)
        ->and($appointment->professional_id)->toBe($professional->id)
        ->and($appointment->service_id)->toBe($service->id)
        ->and($appointment->scheduled_at->format('H:i'))->toBe('14:00')
        ->and($appointment->ends_at->format('H:i'))->toBe('14:30')
        ->and($appointment->status)->toBe('confirmed');
});

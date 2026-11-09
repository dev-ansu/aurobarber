<?php

use App\Models\Appointment;
use App\Models\Barbershop;
use App\Models\BarbershopClient;
use App\Models\Professional;
use App\Models\Service;
use App\Models\User;
use App\Services\SensitiveClientData;
use App\Services\SensitiveClientDataAccess;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

function createAppointmentWithClientContacts(Barbershop $barbershop): Appointment
{
    $professional = Professional::create([
        'barbershop_id' => $barbershop->id,
        'name' => 'Profissional Teste',
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
        'client_phone' => '85999998888',
        'client_email' => 'cliente@example.test',
        'scheduled_at' => now()->addDay(),
        'ends_at' => now()->addDay()->addMinutes(30),
        'service_price' => 40,
        'commission_rate' => 50,
        'commission_amount' => 20,
    ]);
}

test('client and appointment contacts are encrypted at rest and remain readable through models', function () {
    $barbershop = Barbershop::create([
        'name' => 'Barbearia Teste',
        'slug' => 'barbearia-teste',
    ]);

    $client = BarbershopClient::create([
        'barbershop_id' => $barbershop->id,
        'name' => 'Cliente Teste',
        'phone' => '85999998888',
    ]);
    $appointment = createAppointmentWithClientContacts($barbershop);

    $storedClientPhone = DB::table('barbershop_clients')
        ->where('id', $client->id)
        ->value('phone');
    $storedAppointment = DB::table('appointments')
        ->where('id', $appointment->id)
        ->first();

    expect(SensitiveClientData::isEncrypted($storedClientPhone))->toBeTrue()
        ->and($storedClientPhone)->not->toBe('85999998888')
        ->and($client->fresh()->phone)->toBe('85999998888')
        ->and(SensitiveClientData::isEncrypted($storedAppointment->client_phone))->toBeTrue()
        ->and(SensitiveClientData::isEncrypted($storedAppointment->client_email))->toBeTrue()
        ->and($appointment->fresh()->client_phone)->toBe('85999998888')
        ->and($appointment->fresh()->client_email)->toBe('cliente@example.test')
        ->and($storedAppointment->client_phone_hash)->not->toBeNull()
        ->and($storedAppointment->client_email_hash)->not->toBeNull();
});

test('sensitive contact access is permission-gated and audited per tenant', function () {
    $barbershop = Barbershop::create([
        'name' => 'Barbearia Teste',
        'slug' => 'barbearia-teste',
    ]);
    $appointment = createAppointmentWithClientContacts($barbershop);
    $user = User::create([
        'name' => 'Dono Teste',
        'email' => 'owner@example.test',
        'password' => 'password',
        'role' => 'owner',
        'is_active' => true,
    ]);
    $user->barbershops()->attach($barbershop->id, [
        'role' => 'owner',
        'is_primary' => true,
    ]);
    $permission = Permission::findOrCreate(SensitiveClientDataAccess::PERMISSION, 'web');
    $user->givePermissionTo($permission);

    Filament::setTenant($barbershop, isQuiet: true);
    $this->actingAs($user);

    app(SensitiveClientDataAccess::class)->record($appointment, ['client_phone', 'client_email']);

    $log = DB::table('sensitive_data_access_logs')->first();

    expect($log)->not->toBeNull()
        ->and((int) $log->barbershop_id)->toBe($barbershop->id)
        ->and((int) $log->user_id)->toBe($user->id)
        ->and(json_decode($log->fields, true))->toBe(['client_phone', 'client_email']);
});

test('backfill command encrypts existing plaintext and is safe to rerun', function () {
    $barbershop = Barbershop::create([
        'name' => 'Barbearia Teste',
        'slug' => 'barbearia-teste',
    ]);
    $client = BarbershopClient::create([
        'barbershop_id' => $barbershop->id,
        'name' => 'Cliente Teste',
        'phone' => '85999998888',
    ]);
    $appointment = createAppointmentWithClientContacts($barbershop);

    DB::table('barbershop_clients')->where('id', $client->id)->update([
        'phone' => '85999998888',
        'phone_hash' => null,
    ]);
    DB::table('appointments')->where('id', $appointment->id)->update([
        'client_phone' => '85999998888',
        'client_phone_hash' => null,
        'client_email' => 'cliente@example.test',
        'client_email_hash' => null,
    ]);

    $this->artisan('aurobarber:encrypt-client-contact-data')->assertExitCode(0);
    $encryptedClientPhone = DB::table('barbershop_clients')
        ->where('id', $client->id)
        ->value('phone');
    $encryptedAppointment = DB::table('appointments')
        ->where('id', $appointment->id)
        ->first();

    expect(SensitiveClientData::isEncrypted($encryptedClientPhone))->toBeTrue()
        ->and(SensitiveClientData::isEncrypted($encryptedAppointment->client_phone))->toBeTrue()
        ->and(SensitiveClientData::isEncrypted($encryptedAppointment->client_email))->toBeTrue()
        ->and($encryptedAppointment->client_phone_hash)->not->toBeNull()
        ->and($encryptedAppointment->client_email_hash)->not->toBeNull();

    $this->artisan('aurobarber:encrypt-client-contact-data')->assertExitCode(0);

    expect(DB::table('barbershop_clients')->where('id', $client->id)->value('phone'))->toBe($encryptedClientPhone);
});

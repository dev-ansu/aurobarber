<?php

namespace App\Filament\Resources\BarbershopResource\Pages;

use App\Filament\Resources\BarbershopResource;
use App\Models\Service;
use App\Models\User;
use App\Notifications\NewBarbershopWelcomeNotification;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class CreateBarbershop extends CreateRecord
{
    protected static string $resource = BarbershopResource::class;

    protected ?string $generatedTempPassword = null;

    protected ?User $createdOwner = null;

    protected function handleRecordCreation(array $data): Model
    {
        $ownerType = $data['owner_type'] ?? 'existing';
        $sendEmail = ! isset($data['send_welcome_email']) || (bool) $data['send_welcome_email'];

        if ($ownerType === 'new') {
            // Gera senha temporária legível e segura (ex: Barber#8392!)
            $this->generatedTempPassword = 'Barber#'.rand(1000, 9999).'!';

            $owner = User::create([
                'name' => $data['owner_name'],
                'email' => $data['owner_email'],
                'phone' => $data['owner_phone'] ?? null,
                'password' => Hash::make($this->generatedTempPassword),
                'role' => 'owner',
                'must_change_password' => true,
                'email_verified_at' => now(),
                'is_active' => true,
            ]);

            $owner->assignRole('barbershop_owner');
            $data['owner_id'] = $owner->id;
            $this->createdOwner = $owner;
        }

        // Remove campos virtuais antes de salvar o modelo Barbershop
        unset($data['owner_type'], $data['owner_name'], $data['owner_email'], $data['owner_phone'], $data['send_welcome_email']);

        // Se horários não forem definidos, insere o padrão de mercado
        if (empty($data['opening_hours'])) {
            $data['opening_hours'] = [
                'days' => ['mon', 'tue', 'wed', 'thu', 'fri', 'sat'],
                'start_time' => '08:00',
                'end_time' => '19:30',
                'slot_interval' => 30,
            ];
        }

        $barbershop = parent::handleRecordCreation($data);

        // Vincula o proprietário à unidade (pivot de tenants)
        if ($barbershop->owner_id) {
            $barbershop->users()->syncWithoutDetaching([
                $barbershop->owner_id => [
                    'role' => 'owner',
                    'is_primary' => true,
                ],
            ]);
        }

        // Provisionamento inicial: Catálogo básico de serviços
        Service::firstOrCreate(
            ['barbershop_id' => $barbershop->id, 'name' => 'Corte Masculino'],
            [
                'description' => 'Degradê, social ou navalhado com finalização.',
                'price' => 40.00,
                'duration_minutes' => 35,
                'is_active' => true,
            ]
        );

        Service::firstOrCreate(
            ['barbershop_id' => $barbershop->id, 'name' => 'Barba Tradicional / Barboterapia'],
            [
                'description' => 'Alinhamento com toalha quente, navalha e óleo pós-barba.',
                'price' => 35.00,
                'duration_minutes' => 30,
                'is_active' => true,
            ]
        );

        Service::firstOrCreate(
            ['barbershop_id' => $barbershop->id, 'name' => 'Combo Cabelo + Barba'],
            [
                'description' => 'Experiência completa de corte e cuidados com a barba.',
                'price' => 65.00,
                'duration_minutes' => 55,
                'is_active' => true,
            ]
        );

        // Disparo do e-mail de boas-vindas com a senha temporária
        if ($this->createdOwner && $this->generatedTempPassword && $sendEmail) {
            try {
                $loginUrl = url('/admin/'.$barbershop->slug);
                $this->createdOwner->notify(new NewBarbershopWelcomeNotification(
                    $barbershop,
                    $this->generatedTempPassword,
                    $loginUrl
                ));
            } catch (\Throwable $e) {
                logger()->error('Falha ao enviar e-mail de boas-vindas para nova barbearia: '.$e->getMessage());
            }
        }

        return $barbershop;
    }

    protected function getCreatedNotification(): ?Notification
    {
        $body = 'A nova barbearia foi provisionada com serviços iniciais e horários de funcionamento.';
        if ($this->createdOwner && $this->generatedTempPassword) {
            $body .= " Proprietário: {$this->createdOwner->name} ({$this->createdOwner->email}). Senha temporária: {$this->generatedTempPassword} (Obrigatória troca no 1º login).";
        }

        return Notification::make()
            ->title('Barbearia Provisionada com Sucesso! 💈')
            ->body($body)
            ->success()
            ->persistent();
    }
}

<?php

namespace App\Filament\Resources\AppointmentResource\Pages;

use App\Filament\Resources\AppointmentResource;
use App\Models\Professional;
use App\Models\Service;
use Filament\Resources\Pages\CreateRecord;

class CreateAppointment extends CreateRecord
{
    protected static string $resource = AppointmentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Define a barbearia do usuário autenticado ou a primeira barbearia
        $barbershopId = auth()->user()?->barbershop_id ?: 1;
        $data['barbershop_id'] = $barbershopId;

        // Calcula comissão e horário de término automaticamente
        if (!empty($data['service_id']) && !empty($data['professional_id'])) {
            $service = Service::find($data['service_id']);
            $professional = Professional::find($data['professional_id']);
            
            $rate = $service?->custom_commission_rate ?: ($professional?->commission_rate ?: 50.00);
            $data['commission_rate'] = $rate;
            $data['commission_amount'] = ($data['service_price'] * $rate) / 100;

            if (!empty($data['scheduled_at'])) {
                $duration = $service?->duration_minutes ?: 30;
                $data['ends_at'] = \Carbon\Carbon::parse($data['scheduled_at'])->addMinutes($duration);
            }
        }

        return $data;
    }
}

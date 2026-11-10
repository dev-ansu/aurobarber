<?php

namespace App\Filament\Resources\AppointmentResource\Pages;

use App\Filament\Resources\AppointmentResource;
use App\Models\Barbershop;
use Carbon\Carbon;
use Filament\Resources\Pages\CreateRecord;

class CreateAppointment extends CreateRecord
{
    protected static string $resource = AppointmentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $tenant = filament()->getTenant();
        abort_unless(
            $tenant instanceof Barbershop && auth()->user()?->canAccessTenant($tenant),
            403,
        );

        if (auth()->user()?->isRestrictedBarber()) {
            $professionalId = auth()->user()->resolveProfessionalId($tenant->id);
            abort_unless(
                $professionalId !== null && (int) $data['professional_id'] === $professionalId,
                403,
            );
        }

        $data['barbershop_id'] = $tenant->getKey();

        // Calcula comissão e horário de término automaticamente
        if (! empty($data['service_id']) && ! empty($data['professional_id'])) {
            $service = $tenant->services()->findOrFail($data['service_id']);
            $professional = $tenant->professionals()->findOrFail($data['professional_id']);

            $rate = $service?->custom_commission_rate ?: ($professional?->commission_rate ?: 50.00);
            $data['commission_rate'] = $rate;
            $data['commission_amount'] = ($data['service_price'] * $rate) / 100;

            if (! empty($data['scheduled_at'])) {
                $duration = $service?->duration_minutes ?: 30;
                $data['ends_at'] = Carbon::parse($data['scheduled_at'])->addMinutes($duration);
            }
        }

        return $data;
    }
}

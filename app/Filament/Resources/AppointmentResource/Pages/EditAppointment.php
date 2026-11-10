<?php

namespace App\Filament\Resources\AppointmentResource\Pages;

use App\Filament\Resources\AppointmentResource;
use App\Models\Barbershop;
use App\Services\SensitiveClientDataAccess;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAppointment extends EditRecord
{
    protected static string $resource = AppointmentResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (app(SensitiveClientDataAccess::class)->canView()) {
            app(SensitiveClientDataAccess::class)->record($this->record, ['client_phone', 'client_email']);
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $tenant = filament()->getTenant();
        $user = auth()->user();

        abort_unless(
            $tenant instanceof Barbershop
                && $user?->canAccessTenant($tenant)
                && (int) $this->record->barbershop_id === (int) $tenant->getKey(),
            403,
        );

        if ($user->isRestrictedBarber()) {
            $professionalId = $user->resolveProfessionalId($tenant->id);
            abort_unless(
                $professionalId !== null
                    && (int) $this->record->professional_id === $professionalId
                    && (int) $data['professional_id'] === $professionalId,
                403,
            );
        }

        $tenant->services()->findOrFail($data['service_id']);

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

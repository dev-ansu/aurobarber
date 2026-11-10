<?php

namespace App\Filament\Resources\ProfessionalResource\Pages;

use App\Filament\Resources\ProfessionalResource;
use App\Models\Barbershop;
use Filament\Resources\Pages\CreateRecord;

class CreateProfessional extends CreateRecord
{
    protected static string $resource = ProfessionalResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $tenant = filament()->getTenant();
        abort_unless(
            $tenant instanceof Barbershop && auth()->user()?->canAccessTenant($tenant),
            403,
        );

        $data['barbershop_id'] = $tenant->getKey();

        return $data;
    }
}

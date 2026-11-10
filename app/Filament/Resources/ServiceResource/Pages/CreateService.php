<?php

namespace App\Filament\Resources\ServiceResource\Pages;

use App\Filament\Resources\ServiceResource;
use App\Models\Barbershop;
use Filament\Resources\Pages\CreateRecord;

class CreateService extends CreateRecord
{
    protected static string $resource = ServiceResource::class;

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

<?php

namespace App\Filament\Resources\BarbershopClientResource\Pages;

use App\Filament\Resources\BarbershopClientResource;
use App\Models\Barbershop;
use Filament\Resources\Pages\CreateRecord;

class CreateBarbershopClient extends CreateRecord
{
    protected static string $resource = BarbershopClientResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $tenant = filament()->getTenant();
        abort_unless(
            $tenant instanceof Barbershop && auth()->user()?->canAccessTenant($tenant),
            403,
        );

        $data['barbershop_id'] = $tenant->getKey();
        $data['phone'] = preg_replace('/\D/', '', (string) ($data['phone'] ?? ''));

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

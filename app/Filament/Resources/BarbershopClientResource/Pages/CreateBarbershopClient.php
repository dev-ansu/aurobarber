<?php

namespace App\Filament\Resources\BarbershopClientResource\Pages;

use App\Filament\Resources\BarbershopClientResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBarbershopClient extends CreateRecord
{
    protected static string $resource = BarbershopClientResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['barbershop_id'] = filament()->getTenant()?->id ?? auth()->user()?->barbershop_id ?? 1;
        $data['phone'] = preg_replace('/\D/', '', (string) ($data['phone'] ?? ''));

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

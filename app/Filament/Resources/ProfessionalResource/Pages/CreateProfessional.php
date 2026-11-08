<?php

namespace App\Filament\Resources\ProfessionalResource\Pages;

use App\Filament\Resources\ProfessionalResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProfessional extends CreateRecord
{
    protected static string $resource = ProfessionalResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['barbershop_id'] = auth()->user()?->barbershop_id ?: 1;
        return $data;
    }
}

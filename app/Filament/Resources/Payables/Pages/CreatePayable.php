<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payables\Pages;

use App\Filament\Resources\Payables\PayableResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Auth\Access\AuthorizationException;

class CreatePayable extends CreateRecord
{
    protected static string $resource = PayableResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $tenant = Filament::getTenant();

        if ($tenant === null) {
            throw new AuthorizationException('Selecione uma barbearia antes de registrar a conta.');
        }

        $data['barbershop_id'] = $tenant->getKey();
        $data['created_by_id'] = auth()->id();

        return $data;
    }
}

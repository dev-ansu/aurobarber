<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\Barbershop;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Auth\Access\AuthorizationException;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $tenant = filament()->getTenant();
        $user = auth()->user();

        if (! $tenant instanceof Barbershop || ! $user?->canAccessTenant($tenant)) {
            throw new AuthorizationException('Selecione uma barbearia autorizada antes de cadastrar o produto.');
        }

        $data['barbershop_id'] = $tenant->getKey();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

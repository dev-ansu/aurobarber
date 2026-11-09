<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $tenant = filament()->getTenant();
        if ($tenant) {
            $data['barbershop_id'] = $tenant->id;
        }
        $data['role'] = $data['role'] ?? 'barber';
        $data['email_verified_at'] = now();

        return $data;
    }

    protected function afterCreate(): void
    {
        $tenant = filament()->getTenant();
        if ($tenant && $this->record) {
            $tenant->users()->syncWithoutDetaching([
                $this->record->id => [
                    'role' => 'barber',
                    'is_primary' => true,
                ],
            ]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

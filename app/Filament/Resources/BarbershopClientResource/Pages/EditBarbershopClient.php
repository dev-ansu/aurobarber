<?php

namespace App\Filament\Resources\BarbershopClientResource\Pages;

use App\Filament\Resources\BarbershopClientResource;
use App\Services\SensitiveClientDataAccess;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBarbershopClient extends EditRecord
{
    protected static string $resource = BarbershopClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (app(SensitiveClientDataAccess::class)->canView()) {
            app(SensitiveClientDataAccess::class)->record($this->record, ['phone']);
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

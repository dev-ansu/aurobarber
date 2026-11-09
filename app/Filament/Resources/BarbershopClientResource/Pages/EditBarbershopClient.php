<?php

namespace App\Filament\Resources\BarbershopClientResource\Pages;

use App\Filament\Resources\BarbershopClientResource;
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

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

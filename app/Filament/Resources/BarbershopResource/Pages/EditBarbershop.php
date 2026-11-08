<?php

namespace App\Filament\Resources\BarbershopResource\Pages;

use App\Filament\Resources\BarbershopResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBarbershop extends EditRecord
{
    protected static string $resource = BarbershopResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

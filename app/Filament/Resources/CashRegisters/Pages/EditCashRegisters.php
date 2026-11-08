<?php

namespace App\Filament\Resources\CashRegisters\Pages;

use App\Filament\Resources\CashRegisters\CashRegistersResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCashRegisters extends EditRecord
{
    protected static string $resource = CashRegistersResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

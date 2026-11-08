<?php

namespace App\Filament\Resources\CashRegisters\Pages;

use App\Filament\Resources\CashRegisters\CashRegistersResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCashRegisters extends ListRecords
{
    protected static string $resource = CashRegistersResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

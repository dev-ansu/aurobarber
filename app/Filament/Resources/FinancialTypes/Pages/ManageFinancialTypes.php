<?php

namespace App\Filament\Resources\FinancialTypes\Pages;

use App\Filament\Resources\FinancialTypes\FinancialTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageFinancialTypes extends ManageRecords
{
    protected static string $resource = FinancialTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

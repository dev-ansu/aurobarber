<?php

namespace App\Filament\Resources\ContractHistoryTypes\Pages;

use App\Filament\Resources\ContractHistoryTypes\ContractHistoryTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageContractHistoryTypes extends ManageRecords
{
    protected static string $resource = ContractHistoryTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Novo tipo')];
    }
}

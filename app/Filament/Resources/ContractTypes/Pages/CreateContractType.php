<?php

namespace App\Filament\Resources\ContractTypes\Pages;

use App\Filament\Resources\ContractTypes\ContractTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateContractType extends CreateRecord
{
    protected static string $resource = ContractTypeResource::class;

    protected static bool $canCreateAnother = false;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

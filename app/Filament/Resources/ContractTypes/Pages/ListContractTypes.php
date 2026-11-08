<?php

namespace App\Filament\Resources\ContractTypes\Pages;

use App\Filament\Resources\ContractTypes\ContractTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListContractTypes extends ListRecords
{
    protected static string $resource = ContractTypeResource::class;

    public function getSubheading(): ?string
    {
        return 'Cadastre e personalize a minuta do contrato de prestação de serviços. O sistema permite manter um histórico de versões, garantindo que apenas um modelo ativo seja utilizado como padrão nas novas matrículas.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

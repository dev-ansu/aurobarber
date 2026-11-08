<?php

namespace App\Filament\Resources\CommercialVisits\Pages;

use App\Filament\Resources\CommercialVisits\CommercialVisitResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCommercialVisits extends ManageRecords
{
    protected static string $resource = CommercialVisitResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Registrar visita')];
    }
}

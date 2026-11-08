<?php

declare(strict_types=1);

namespace App\Filament\Saas\Resources\LandingLeads\Pages;

use App\Filament\Saas\Resources\LandingLeads\LandingLeadResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLandingLead extends EditRecord
{
    protected static string $resource = LandingLeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

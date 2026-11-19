<?php

declare(strict_types=1);

namespace App\Filament\Saas\Resources\LandingLeads\Pages;

use App\Filament\Saas\Resources\LandingLeads\LandingLeadResource;
use Filament\Resources\Pages\ListRecords;

class ListLandingLeads extends ListRecords
{
    protected static string $resource = LandingLeadResource::class;
}

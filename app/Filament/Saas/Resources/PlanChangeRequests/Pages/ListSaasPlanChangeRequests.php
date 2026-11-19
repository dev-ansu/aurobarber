<?php

declare(strict_types=1);

namespace App\Filament\Saas\Resources\PlanChangeRequests\Pages;

use App\Filament\Saas\Resources\PlanChangeRequests\SaasPlanChangeRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListSaasPlanChangeRequests extends ListRecords
{
    protected static string $resource = SaasPlanChangeRequestResource::class;
}

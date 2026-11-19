<?php

declare(strict_types=1);

namespace App\Filament\Saas\Resources\SaasPlans\Pages;

use App\Filament\Saas\Resources\SaasPlans\SaasPlanResource;
use Filament\Resources\Pages\ListRecords;

class ListSaasPlans extends ListRecords
{
    protected static string $resource = SaasPlanResource::class;
}

<?php

declare(strict_types=1);

namespace App\Filament\Saas\Resources\SaasPlans\Pages;

use App\Filament\Saas\Resources\SaasPlans\SaasPlanResource;
use Filament\Resources\Pages\EditRecord;

class EditSaasPlan extends EditRecord
{
    protected static string $resource = SaasPlanResource::class;
}

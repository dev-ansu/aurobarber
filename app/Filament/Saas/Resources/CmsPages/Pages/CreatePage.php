<?php

declare(strict_types=1);

namespace App\Filament\Saas\Resources\CmsPages\Pages;

use App\Filament\Saas\Resources\CmsPages\PageResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePage extends CreateRecord
{
    protected static string $resource = PageResource::class;
}

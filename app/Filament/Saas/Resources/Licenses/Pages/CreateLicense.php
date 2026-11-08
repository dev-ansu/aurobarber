<?php

declare(strict_types=1);

namespace App\Filament\Saas\Resources\Licenses\Pages;

use App\Filament\Saas\Resources\Licenses\LicenseResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLicense extends CreateRecord
{
    protected static string $resource = LicenseResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}


<?php

declare(strict_types=1);

namespace App\Filament\Saas\Resources\CmsPages\Pages;

use App\Filament\Saas\Resources\CmsPages\PageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPage extends ListRecords
{
    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nova Página'),
        ];
    }
}

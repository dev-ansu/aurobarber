<?php

namespace App\Filament\Resources\Schools\Pages;

use App\Filament\Resources\Schools\SchoolResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSchool extends CreateRecord
{
    protected static string $resource = SchoolResource::class;

    protected function afterCreate(): void
    {
        if (auth()->check()) {
            $this->record->users()->syncWithoutDetaching([auth()->id()]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', [
            'record' => $this->record,
        ]);
    }
}

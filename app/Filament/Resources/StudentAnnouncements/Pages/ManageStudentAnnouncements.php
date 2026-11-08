<?php

namespace App\Filament\Resources\StudentAnnouncements\Pages;

use App\Filament\Resources\StudentAnnouncements\StudentAnnouncementResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageStudentAnnouncements extends ManageRecords
{
    protected static string $resource = StudentAnnouncementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Novo aviso'),
        ];
    }
}

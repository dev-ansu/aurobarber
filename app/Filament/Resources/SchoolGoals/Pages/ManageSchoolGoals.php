<?php

namespace App\Filament\Resources\SchoolGoals\Pages;

use App\Filament\Resources\SchoolGoals\SchoolGoalResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageSchoolGoals extends ManageRecords
{
    protected static string $resource = SchoolGoalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nova Meta Mensal')
                ->icon('heroicon-o-plus'),
        ];
    }
}

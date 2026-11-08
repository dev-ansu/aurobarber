<?php

declare(strict_types=1);

namespace App\Filament\Resources\TypingExercises\Pages;

use App\Filament\Resources\TypingExercises\TypingExerciseResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTypingExercises extends ListRecords
{
    protected static string $resource = TypingExerciseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

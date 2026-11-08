<?php

declare(strict_types=1);

namespace App\Filament\Resources\TypingExercises\Pages;

use App\Filament\Resources\TypingExercises\TypingExerciseResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTypingExercise extends EditRecord
{
    protected static string $resource = TypingExerciseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

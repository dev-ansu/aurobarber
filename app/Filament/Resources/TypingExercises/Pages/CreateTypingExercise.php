<?php

declare(strict_types=1);

namespace App\Filament\Resources\TypingExercises\Pages;

use App\Filament\Resources\TypingExercises\TypingExerciseResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTypingExercise extends CreateRecord
{
    protected static string $resource = TypingExerciseResource::class;
}

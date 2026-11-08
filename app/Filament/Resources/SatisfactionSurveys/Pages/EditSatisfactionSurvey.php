<?php

declare(strict_types=1);

namespace App\Filament\Resources\SatisfactionSurveys\Pages;

use App\Filament\Resources\SatisfactionSurveys\SatisfactionSurveyResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditSatisfactionSurvey extends EditRecord
{
    protected static string $resource = SatisfactionSurveyResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', [
            'record' => $this->record,
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}

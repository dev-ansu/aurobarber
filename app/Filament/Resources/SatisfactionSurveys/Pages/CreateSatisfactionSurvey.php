<?php

declare(strict_types=1);

namespace App\Filament\Resources\SatisfactionSurveys\Pages;

use App\Filament\Resources\SatisfactionSurveys\SatisfactionSurveyResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateSatisfactionSurvey extends CreateRecord
{
    protected static string $resource = SatisfactionSurveyResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', [
            'record' => $this->record,
        ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by_id'] = Auth::id();

        return $data;
    }
}

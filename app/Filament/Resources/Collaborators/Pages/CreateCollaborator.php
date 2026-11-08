<?php

namespace App\Filament\Resources\Collaborators\Pages;

use App\Filament\Resources\Collaborators\CollaboratorResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateCollaborator extends CreateRecord
{
    protected static string $resource = CollaboratorResource::class;

    protected static bool $canCreateAnother = false;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', [
            'record' => $this->record,
        ]);
    }

    public function mount(): void
    {
        parent::mount();

        $personId = request()->query('person_id');
        if ($personId) {
            $this->form->fill([
                'person_id' => (int) $personId,
            ]);
        }
    }

    protected function afterCreate(): void
    {
        $tenantId = Filament::getTenant()?->id;
        if ($tenantId && $this->record) {
            $this->record->person?->schools()->syncWithoutDetaching([$tenantId]);
            $this->record->user?->schools()->syncWithoutDetaching([$tenantId]);
        }
    }
}

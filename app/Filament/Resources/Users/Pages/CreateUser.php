<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected static bool $canCreateAnother = false;

    public function mount(): void
    {
        parent::mount();

        $personId = request()->query('person_id');
        if ($personId) {
            $person = \App\Models\Person::find($personId);
            $this->form->fill([
                'person_id' => (int) $personId,
                'email' => $person?->email ?: ($person ? 'usuario.' . $person->id . '@aurosoft.local' : null),
            ]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['panel_access'] = true;

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->email_verified_at = now();
        $this->record->panel_access = true;
        $this->record->save();
    }
}

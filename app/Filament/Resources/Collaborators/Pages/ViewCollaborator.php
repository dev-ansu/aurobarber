<?php

namespace App\Filament\Resources\Collaborators\Pages;

use App\Filament\Resources\Collaborators\CollaboratorResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCollaborator extends ViewRecord
{
    protected static string $resource = CollaboratorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createUser')
                ->label('Criar Usuário')
                ->icon('heroicon-o-user-plus')
                ->color('primary')
                ->visible(fn (): bool => UserResource::canCreate() && ! User::where('person_id', $this->record->person_id)->exists())
                ->url(fn (): string => route('filament.admin.resources.users.create', [
                    'person_id' => $this->record->person_id,
                ])),

            Action::make('editUser')
                ->label('Editar Usuário')
                ->icon('heroicon-o-user')
                ->color('gray')
                ->visible(fn (): bool => ($u = User::where('person_id', $this->record->person_id)->first()) && UserResource::canEdit($u))
                ->url(fn (): string => route('filament.admin.resources.users.edit', [
                    'record' => User::where('person_id', $this->record->person_id)->first()->id,
                ])),

            EditAction::make(),
        ];
    }
}

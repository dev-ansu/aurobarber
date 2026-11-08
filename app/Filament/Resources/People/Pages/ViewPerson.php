<?php

namespace App\Filament\Resources\People\Pages;

use App\Filament\Resources\Collaborators\CollaboratorResource;
use App\Filament\Resources\Contracts\ContractsResource;
use App\Filament\Resources\People\PersonResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPerson extends ViewRecord
{
    protected static string $resource = PersonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createContract')
                ->label('Criar Contrato')
                ->icon('heroicon-o-document-plus')
                ->color('primary')
                ->visible(fn (): bool => ContractsResource::canCreate())
                ->url(fn (): string => route('filament.admin.resources.contracts.create', [
                    'student_id' => $this->record->id,
                ])),

            Action::make('createCollaborator')
                ->label('Criar Colaborador')
                ->icon('heroicon-o-briefcase')
                ->color('info')
                ->visible(fn (): bool => CollaboratorResource::canCreate())
                ->url(fn (): string => route('filament.admin.resources.collaborators.create', [
                    'person_id' => $this->record->id,
                ])),

            EditAction::make(),
        ];
    }
}

<?php

namespace App\Actions;

// use App\Models\Classroom;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ToggleStatusAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'toggleStatus';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(fn (Model $record): string => $record->active ? 'Desativar' : 'Ativar');

        $this->icon(fn (Model $record): string => $record->active ?
            'heroicon-o-x-circle' : 'heroicon-o-check-circle'
        );

        $this->color(fn (Model $record): string => $record->active ? 'danger' : 'success');

        $this->requiresConfirmation();

        $this->authorize(fn (Model $record): bool => Auth::user()->can('toggleStatus', $record)
        );

        $this->action(function (Model $record): void {

            $record->update(['active' => ! $record->active]);

            Notification::make()
                ->title($record->active ? 'Ativado.' : 'Desativado.')
                ->success()
                ->send();

        });
    }
}

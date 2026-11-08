<?php

declare(strict_types=1);

namespace App\Filament\Resources\Contracts\Actions;

use App\Models\Contract;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;

class FinishContractAction
{
    public static function make(string $name = 'finishContract'): Action
    {
        return Action::make($name)
            ->label('Concluir Contrato')
            ->color('success')
            ->icon('heroicon-m-check-badge')
            ->requiresConfirmation()
            ->modalHeading(fn (Contract $record): string => "Conclusão de Contrato #{$record->contract_number}")
            ->modalDescription('A secretaria confirma o encerramento com sucesso do contrato. A emissão de certificado pode ser feita em seguida.')
            ->modalSubmitActionLabel('Confirmar Conclusão')
            ->form([
                Textarea::make('reason')
                    ->label('Observação de Conclusão')
                    ->placeholder('Ex: Aluno cumpriu toda a carga horária e foi aprovado nos exames finais.')
                    ->rows(2)
                    ->default('Conclusão de curso regular com aproveitamento satisfatório.')
                    ->required(),
            ])
            ->action(function (Contract $record, array $data): void {
                $record->changeStatus('finished', $data['reason'] ?? 'Conclusão de curso regular');

                Notification::make()
                    ->title('Contrato concluído!')
                    ->body('O status do contrato foi alterado para finalizado com sucesso.')
                    ->success()
                    ->send();
            })
            ->visible(fn (Contract $record): bool => $record->status === 'active');
    }
}


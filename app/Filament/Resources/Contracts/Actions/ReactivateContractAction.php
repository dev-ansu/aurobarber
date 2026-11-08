<?php

declare(strict_types=1);

namespace App\Filament\Resources\Contracts\Actions;

use App\Models\Contract;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;

class ReactivateContractAction
{
    public static function make(string $name = 'reactivateContract'): Action
    {
        return Action::make($name)
            ->label(fn (Contract $record): string => match ($record->status) {
                'suspended' => 'Reativar Contrato (Retorno de Trancamento)',
                'finished' => 'Reativar Contrato (Desfazer Conclusão)',
                'canceled' => 'Reativar Contrato (Desfazer Cancelamento)',
                default => 'Reativar Contrato',
            })
            ->tooltip('Reverter status do contrato para Ativo')
            ->icon('heroicon-m-arrow-path')
            ->color('success')
            ->visible(fn (Contract $record): bool => in_array($record->status, ['suspended', 'finished', 'canceled'], true))
            ->authorize(fn (Contract $record): bool => (auth()->user()?->can('Reactivate:Contract') ?? false)
                || (method_exists(auth()->user(), 'hasRole') && auth()->user()->hasRole('super_admin')))
            ->requiresConfirmation()
            ->modalHeading(fn (Contract $record): string => match ($record->status) {
                'finished' => "Reverter Conclusão do Contrato #{$record->contract_number}",
                'canceled' => "Reverter Cancelamento do Contrato #{$record->contract_number}",
                'suspended' => "Reativar Matrícula (Retorno de Trancamento) #{$record->contract_number}",
                default => "Reativar Contrato #{$record->contract_number}",
            })
            ->modalDescription(fn (Contract $record): string => match ($record->status) {
                'finished' => 'Esta ação desfaz a conclusão do contrato, revertendo o status para "Ativo", limpando o término e sincronizando o acesso do aluno ao LMS. A operação é registrada na auditoria como reversão de status.',
                'canceled' => 'Esta ação desfaz o cancelamento do contrato, revertendo o status para "Ativo", limpando o registro de cancelamento e reativando a matrícula do aluno.',
                'suspended' => 'Confirme o retorno do aluno às atividades regulares. O status do contrato será reativado para "Ativo".',
                default => 'Confirme a reativação do contrato para o status "Ativo". Registre a justificativa para a auditoria.',
            })
            ->modalSubmitActionLabel(fn (Contract $record): string => match ($record->status) {
                'finished' => 'Confirmar Reversão de Conclusão',
                'canceled' => 'Confirmar Reversão de Cancelamento',
                'suspended' => 'Confirmar Retorno do Aluno',
                default => 'Confirmar Reativação',
            })
            ->form([
                Textarea::make('reason')
                    ->label('Motivo / Justificativa da Reativação (Auditoria)')
                    ->placeholder(fn (Contract $record): string => match ($record->status) {
                        'finished' => 'Ex: Conclusão informada acidentalmente pela recepção...',
                        'canceled' => 'Ex: Cancelamento acidental ou aluno solicitou continuidade...',
                        'suspended' => 'Ex: Aluno retornou no horário normal...',
                        default => 'Ex: Reativação autorizada pela gerência...',
                    })
                    ->rows(3)
                    ->required(),
            ])
            ->action(function (Contract $record, array $data): void {
                $reason = trim((string) ($data['reason'] ?? ''));

                if ($record->status === 'suspended') {
                    $notes = filled($reason) ? "\n[Retorno de Trancamento em " . now()->format('d/m/Y H:i') . ']: ' . $reason : '';
                    $record->update([
                        'observations' => trim(($record->observations ?? '') . $notes),
                    ]);
                }

                $record->reactivate($reason);

                Notification::make()
                    ->title('Contrato reativado com sucesso!')
                    ->body("O contrato #{$record->contract_number} foi revertido para Ativo e registrado na auditoria.")
                    ->success()
                    ->send();
            });
    }
}

<?php

namespace App\Filament\Resources\Contracts\Pages;

use App\Filament\Resources\Contracts\ContractsResource;
use App\Models\Contract;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateContracts extends CreateRecord
{
    protected static string $resource = ContractsResource::class;

    // Desativa o botão "Salvar e Criar Outro" para focar o fluxo do operador
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

        $studentId = request()->query('student_id');
        $responsibleId = request()->query('responsible_id');
        $commercialLeadId = request()->query('commercial_lead_id');
        $collaboratorId = request()->query('collaborator_id');

        $fillData = [];

        if ($studentId) {
            $fillData['student_id'] = (int) $studentId;

            $responsible = $responsibleId
                ? \App\Models\Responsible::find($responsibleId)
                : \App\Models\Responsible::where('person_id', $studentId)
                    ->where('financial_responsible', true)
                    ->first();

            if ($responsible) {
                $fillData['responsible_id'] = $responsible->id;
            }
        }

        if ($commercialLeadId) {
            $fillData['commercial_lead_id'] = (int) $commercialLeadId;
        }

        if ($collaboratorId) {
            $fillData['collaborator_id'] = (int) $collaboratorId;
        }

        if (! empty($fillData)) {
            $this->form->fill(array_filter($fillData));
        }
    }

    /**
     * Gera o código do contrato simulando o padrão de uma placa de veículo
     * (4 letras - 3 números).
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        do {
            // Gera 4 letras maiúsculas aleatórias (ex: AURO)
            $letras = strtoupper(Str::random(4));

            // Gera 3 números aleatórios entre 100 e 999
            $numeros = rand(100, 999);

            $codigoGerado = "{$letras}-{$numeros}";

            // Verifica se o código já existe no banco
            $existe = Contract::where(
                'contract_number',
                $codigoGerado
            )->exists();

        } while ($existe);

        // Injeta o código único gerado
        $data['contract_number'] = $codigoGerado;
        $data['total_value'] = $data['total_value'] ?? 0;
        $data['discount_value'] = $data['discount_value'] ?? 0;

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var Contract $contract */
        $contract = $this->record;

        $importedCourses = 0;
        if ($contract->student?->ouro_moderno_cod_int && $contract->items()->doesntExist()) {
            try {
                $importedCourses = app(\App\Services\OuroModernoContractSyncService::class)->importStudentCoursesFromLms($contract);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Erro ao importar cursos do LMS no contrato {$contract->id}: {$e->getMessage()}");
            }
        }

        $courseSuffix = $importedCourses > 0
            ? " ({$importedCourses} curso(s) da Ouro Moderno vinculados automaticamente)"
            : '';

        if ($contract->status === 'active') {
            $contract->statusHistories()->create([
                'user_id' => \Illuminate\Support\Facades\Auth::id(),
                'from_status' => 'draft',
                'to_status' => 'active',
                'reason' => 'Contrato cadastrado diretamente como Ativo.',
            ]);

            Notification::make()
                ->title('Contrato Criado e Ativado!')
                ->body("O contrato {$contract->contract_number} foi cadastrado e já está Ativo{$courseSuffix}.")
                ->success()
                ->send();

            return;
        }

        $additionalText = $importedCourses > 0
            ? "Foram vinculados {$importedCourses} curso(s) da Ouro Moderno. "
            : 'Adicione os cursos/formações pela visualização do contrato. ';

        Notification::make()
            ->title('Contrato criado como Rascunho!')
            ->body(
                "O contrato número {$contract->contract_number} foi salvo. ".
                $additionalText.
                'Deseja alterar o status para ATIVO agora?'
            )
            ->warning()
            ->persistent()
            ->actions([
                Action::make('ativar')
                    ->button()
                    ->color('success')
                    ->label('Sim, Ativar Contrato')
                    ->dispatch('ativarContratoCriado', [
                        'contractId' => $contract->id,
                    ]),

                Action::make('manterRascunho')
                    ->button()
                    ->color('gray')
                    ->label('Manter como Rascunho')
                    ->close(),
            ])
            ->send();
    }

    /**
     * Recebe o evento da Action da notificação e ativa o contrato.
     */
}

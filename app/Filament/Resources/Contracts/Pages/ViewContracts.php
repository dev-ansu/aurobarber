<?php

namespace App\Filament\Resources\Contracts\Pages;

use App\Filament\Resources\Contracts\Concerns\HasContractHeaderActions;
use App\Filament\Resources\Contracts\ContractsResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewContracts extends ViewRecord
{
    use HasContractHeaderActions;

    protected static string $resource = ContractsResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->record->loadMissing([
            'auditLogs.user.person',
            'installments.financialType',
            'installments.auditLogs.user.person',
        ]);
    }

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    public function getContentTabLabel(): ?string
    {
        return 'Informações do contrato';
    }

    public function getContentTabIcon(): string
    {
        return 'heroicon-o-document-text';
    }

    protected function getHeaderActions(): array
    {
        return $this->getUnifiedContractHeaderActions(isView: true);
    }

    public function getTitle(): string|Htmlable
    {
        // Garante o carregamento do relacionamento do aluno
        $this->record->loadMissing('student');

        $nomeAluno = $this->record->student?->name ?? 'Contrato';

        return "{$nomeAluno}";
    }
}

<?php

namespace App\Filament\Resources\Contracts\Pages;

use App\Filament\Resources\Contracts\Concerns\HasContractHeaderActions;
use App\Filament\Resources\Contracts\ContractsResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditContracts extends EditRecord
{
    use HasContractHeaderActions;

    protected static string $resource = ContractsResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', [
            'record' => $this->record,
        ]);
    }

    public function getTitle(): string|Htmlable
    {
        // Garante o carregamento do relacionamento do aluno
        $this->record->loadMissing('student');

        $nomeAluno = $this->record->student?->name ?? 'Contrato';

        return "{$nomeAluno}";
    }

    protected function getHeaderActions(): array
    {
        return $this->getUnifiedContractHeaderActions(isView: false);
    }
}

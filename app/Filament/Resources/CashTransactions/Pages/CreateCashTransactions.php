<?php

namespace App\Filament\Resources\CashTransactions\Pages;

use App\Filament\Resources\CashTransactions\CashTransactionsResource;
use App\Models\CashRegister;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreateCashTransactions extends CreateRecord
{
    protected static string $resource = CashTransactionsResource::class;

    // Desativa o botão "Salvar e Criar Outro" para focar o fluxo do operador
    protected static bool $canCreateAnother = false;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', [
            'record' => $this->record,
        ]);
    }

    /**
     * Vincula o usuário logado antes de salvar no banco
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id(); // Injeta o operador logado automaticamente

        return $data;
    }

    /**
     * Cria a movimentação e atualiza o saldo do caixa de forma estritamente atômica.
     */
    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            /** @var \App\Models\CashTransaction $record */
            $record = static::getModel()::create($data);

            $cashRegister = CashRegister::lockForUpdate()->find($record->cash_register_id);
            if ($cashRegister) {
                if ($record->type === 'input') {
                    $cashRegister->increment('current_balance', $record->amount);
                } else {
                    $cashRegister->decrement('current_balance', $record->amount);
                }
            }

            return $record;
        });
    }
}

<?php

namespace App\Filament\Resources\CashRegisters\Pages;

use App\Filament\Resources\CashRegisters\CashRegistersResource;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;

class CreateCashRegisters extends CreateRecord
{
    protected static string $resource = CashRegistersResource::class;

    protected static bool $canCreateAnother = false;

    // Lógica executada segundos antes de persistir o caixa no banco de dados
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id(); // Vincula o caixa à secretária que está logada
        $data['opened_at'] = now();     // Grava o horário exato da abertura
        $data['status'] = 'open';       // Garante que nasce aberto
        $data['current_balance'] = $data['opening_balance']; // O saldo atual começa igual ao inicial
        if (Filament::getTenant()) {
            $data['school_id'] = Filament::getTenant()->id;
        }

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return parent::handleRecordCreation($data);
        } catch (QueryException $e) {
            // Verifica se o erro é a violação do índice único de caixa aberto (PostgreSQL code: 23505)
            if ($e->getCode() === '23505' || str_contains($e->getMessage(), 'unique_user_open_cash_register')) {
                Notification::make()
                    ->title('Ação Bloqueada!')
                    ->body('Você já possui um Caixa Diário aberto. Feche o caixa atual antes de abrir um novo.')
                    ->danger()
                    ->persistent()
                    ->send();

                $this->halt();
            }

            // Se for qualquer outro erro de banco de dados, deixa a exceção subir normalmente
            throw $e;
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

<?php

namespace App\Filament\Resources\Installments\Pages;

use App\Filament\Resources\Installments\InstallmentsResource;
use App\Filament\Resources\Installments\Tables\InstallmentsTable;
use App\Models\CashRegister;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EditInstallments extends EditRecord
{
    protected static string $resource = InstallmentsResource::class;

    protected function beforeFill(): void
    {
        $this->verificarCaixaAberto();
    }

    protected function getHeaderActions(): array
    {
        return [
            InstallmentsTable::makeRefundAction()
                ->after(fn () => $this->redirect($this->getResource()::getUrl('index'))),
            ViewAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (in_array($data['status'], ['paid', 'partial'])) {
            $this->verificarCaixaAberto();
        }

        return $data;
    }

    /**
     * Delega a execução da baixa para o Model e impede o comportamento nativo padrão de save
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Executa toda a nossa regra contábil e de caixa encapsulada no Model
        $record->processarBaixa($data);

        return $record;
    }

    private function verificarCaixaAberto(): void
    {
        $tenantId = Filament::getTenant()?->id;
        $isSuperAdmin = Auth::user()?->hasRole('super_admin');

        $query = CashRegister::query()
            ->where('status', 'open')
            ->when($tenantId, fn ($q) => $q->where('school_id', $tenantId));

        $caixaAberto = (clone $query)->where('user_id', Auth::id())->exists()
            || ($isSuperAdmin && (clone $query)->exists());

        if (! $caixaAberto) {
            Notification::make()
                ->title('Acesso Bloqueado / Caixa Fechado!')
                ->body('Você precisa abrir um Caixa Diário nesta escola antes de gerenciar recebimentos.')
                ->danger()
                ->persistent()
                ->send();

            $this->redirect(InstallmentsResource::getUrl('index'));
        }
    }
}

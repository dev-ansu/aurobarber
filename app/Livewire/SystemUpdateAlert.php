<?php

namespace App\Livewire;

use App\Filament\Pages\SystemUpdate;
use App\Services\LocalBackupManager;
use App\Services\SystemUpdateService;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class SystemUpdateAlert extends Component
{
    public bool $dismissed = false;

    public bool $modalOpen = false;

    public bool $isUpdating = false;

    public ?array $status = null;

    public ?array $backupStatus = null;

    public bool $backupDismissed = false;

    public bool $isCreatingBackup = false;

    public function mount(): void
    {
        $this->dismissed = (bool) session()->get('system_update_alert_dismissed', false);
        $this->backupDismissed = (bool) session()->get('backup_alert_dismissed', false);

        $user = Auth::user();
        if ($user && ($user->can('View:SystemUpdate') || $user->hasRole('super_admin'))) {
            $this->status = app(SystemUpdateService::class)->checkForUpdates();

            if (! empty($this->status['has_update']) && ! $this->dismissed) {
                // Abre o modal automaticamente se ainda não foi visto nesta sessão
                $this->modalOpen = ! session()->get('system_update_modal_seen', false);
            }
        }

        if ($user && ($user->can('View:BackupManagement') || $user->hasRole('super_admin'))) {
            $this->checkBackupHealth();
        }
    }

    public function autoCheck(): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        if ($user->can('View:SystemUpdate') || $user->hasRole('super_admin')) {
            $this->status = app(SystemUpdateService::class)->checkForUpdates(force: false);

            if (! empty($this->status['has_update']) && ! $this->dismissed) {
                if (! session()->get('system_update_modal_seen', false)) {
                    $this->modalOpen = true;
                }
            }
        }

        if ($user->can('View:BackupManagement') || $user->hasRole('super_admin')) {
            $this->checkBackupHealth();
        }
    }

    protected function checkBackupHealth(): void
    {
        // Em ambiente Cloud SaaS ou com alerta desativado, não exibe alerta de backup local para o cliente
        if (
            (bool) env('SAAS_ENABLED', false) ||
            app()->bound('current_tenant_license') ||
            env('BACKUP_ALERT_ENABLED') === false ||
            config('backup.alert_enabled') === false
        ) {
            $this->backupStatus = null;
            return;
        }

        try {
            $this->backupStatus = app(LocalBackupManager::class)->status();
        } catch (\Throwable $e) {
            $this->backupStatus = null;
        }
    }

    public function openModal(): void
    {
        $this->modalOpen = true;
    }

    public function closeModal(): void
    {
        $this->modalOpen = false;
        session()->put('system_update_modal_seen', true);
    }

    public function dismiss(): void
    {
        $this->dismissed = true;
        $this->modalOpen = false;
        session()->put('system_update_alert_dismissed', true);
        session()->put('system_update_modal_seen', true);
    }

    public function dismissBackupAlert(): void
    {
        $this->backupDismissed = true;
        session()->put('backup_alert_dismissed', true);
    }

    public function createBackupNow(): void
    {
        $this->isCreatingBackup = true;

        try {
            $filename = app(LocalBackupManager::class)->create();
            $this->checkBackupHealth();
            $this->isCreatingBackup = false;

            Notification::make()
                ->title('🛡️ Backup Criado com Sucesso!')
                ->body("Arquivo gerado com segurança: {$filename}")
                ->success()
                ->send();
        } catch (\Throwable $e) {
            $this->isCreatingBackup = false;
            Notification::make()
                ->title('Falha ao Criar Backup')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function applyUpdate(): void
    {
        abort_unless(Auth::user()?->can('Apply:SystemUpdate') || Auth::user()?->hasRole('super_admin'), 403);

        $this->isUpdating = true;

        try {
            $result = app(SystemUpdateService::class)->applyUpdate();

            Notification::make()
                ->title('🎉 Sistema Atualizado com Sucesso!')
                ->body("Aurosoft atualizado para a versão {$result['new_commit']['short_hash']}.")
                ->success()
                ->persistent()
                ->send();

            $targetUrl = \Illuminate\Support\Facades\Route::has('filament.admin.pages.system-update')
                ? SystemUpdate::getUrl()
                : url('/admin/system-update');

            $this->js("window.location.href = " . json_encode($targetUrl) . ";");
        } catch (\Throwable $e) {
            $this->isUpdating = false;
            Notification::make()
                ->title('Falha na Atualização')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }
    }

    public function render()
    {
        return view('livewire.system-update-alert');
    }
}

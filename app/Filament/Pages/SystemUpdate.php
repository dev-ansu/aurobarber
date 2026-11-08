<?php

namespace App\Filament\Pages;

use App\Services\SystemUpdateService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class SystemUpdate extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Ajuda e suporte';

    protected static ?string $navigationLabel = 'Atualizações do Sistema';

    protected static ?string $title = 'Atualização do Sistema';

    protected static ?string $slug = 'system-update';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    protected static ?int $navigationSort = 30;

    protected string $view = 'filament.pages.system-update';

    public bool $isUpdating = false;

    public bool $isDowngrading = false;

    public bool $restoreDatabaseOnDowngrade = false;

    public ?string $envEncryptionKey = null;

    public function getIsEncryptedEnvProperty(): bool
    {
        return app(SystemUpdateService::class)->isEncryptedEnvDeployment();
    }

    public function getHasEnvKeyProperty(): bool
    {
        return app(SystemUpdateService::class)->hasEnvEncryptionKey();
    }

    public function getRecentVersionsProperty(): array
    {
        return app(SystemUpdateService::class)->getRecentVersions();
    }

    public function getLastUpdateSnapshotProperty(): ?array
    {
        return app(SystemUpdateService::class)->getLastUpdateSnapshot();
    }

    public function getPreviousVersionProperty(): ?array
    {
        return app(SystemUpdateService::class)->getPreviousVersionInfo();
    }

    public static function canAccess(): bool
    {
        // No ambiente SaaS Cloud, atualizações são gerenciadas centralmente no servidor via VPS
        if ((bool) env('SAAS_ENABLED', false)) {
            return false;
        }

        return Auth::user()?->can('View:SystemUpdate') || Auth::user()?->hasRole('super_admin');
    }

    public function getUpdateStatusProperty(): array
    {
        return app(SystemUpdateService::class)->checkForUpdates();
    }

    public function checkNow(): void
    {
        try {
            app(SystemUpdateService::class)->checkForUpdates(force: true);
            Notification::make()
                ->title('Verificação concluída')
                ->body('Status de atualização consultado com o repositório oficial.')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Erro ao verificar')
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
            $result = app(SystemUpdateService::class)->applyUpdate($this->envEncryptionKey ?: null);

            Notification::make()
                ->title('🎉 Sistema Atualizado com Sucesso!')
                ->body("Aurosoft atualizado para a versão {$result['new_commit']['short_hash']}. O sistema está pronto para uso.")
                ->success()
                ->persistent()
                ->send();

            $this->js("window.location.reload();");
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

    public function applyDowngrade(?string $commitHash = null): void
    {
        abort_unless(Auth::user()?->can('Apply:SystemUpdate') || Auth::user()?->hasRole('super_admin'), 403);

        $targetCommit = $commitHash ?: $this->selectedDowngradeCommit;
        if (! $targetCommit) {
            $prev = app(SystemUpdateService::class)->getPreviousVersionInfo();
            $targetCommit = $prev['full_hash'] ?? ($prev['short_hash'] ?? null);
        }

        if (! $targetCommit) {
            Notification::make()
                ->title('Versão anterior não encontrada')
                ->body('Não há commit anterior identificado no histórico do Git.')
                ->warning()
                ->send();

            return;
        }

        $this->isDowngrading = true;

        try {
            $result = app(SystemUpdateService::class)->downgradeToVersion(
                $targetCommit,
                $this->restoreDatabaseOnDowngrade,
                $this->envEncryptionKey ?: null
            );

            Notification::make()
                ->title('🔄 Reversão Concluída!')
                ->body("Aurosoft revertido com sucesso para a versão {$result['current_commit']['short_hash']}.")
                ->success()
                ->persistent()
                ->send();

            $this->js("window.location.reload();");
        } catch (\Throwable $e) {
            $this->isDowngrading = false;
            Notification::make()
                ->title('Falha ao Reverter Versão')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('checkUpdates')
                ->label('Verificar Agora')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action('checkNow'),
        ];
    }
}


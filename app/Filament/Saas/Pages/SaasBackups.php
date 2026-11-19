<?php

declare(strict_types=1);

namespace App\Filament\Saas\Pages;

use App\Models\Barbershop;
use App\Models\SaasRestoreTestRun;
use App\Services\LocalBackupManager;
use App\Services\SaasGoogleDriveBackupService;
use App\Services\SaasRestoreTestService;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class SaasBackups extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-server-stack';

    protected static ?string $navigationLabel = 'Backups & Restauração';

    protected static ?string $title = 'Quadro Geral de Backups das Barbearias';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'backups';

    protected string $view = 'filament.saas.pages.backups';

    public static function canAccess(): bool
    {
        return auth()->user()?->role === 'master_admin';
    }

    public string $filterScope = 'all'; // 'all', 'global', 'tenants'

    public ?int $selectedSandboxId = null;

    public string $validationStatus = 'approved';

    public ?string $validationNotes = null;

    public bool $showAuditModal = false;

    public function getHeaderActions(): array
    {
        return [
            Action::make('configureGoogleDrive')
                ->label(fn (): string => app(SaasGoogleDriveBackupService::class)->isEnabled() ? 'Google Drive: Ativo' : 'Configurar Google Drive')
                ->icon('heroicon-o-cloud-arrow-up')
                ->color(fn (): string => app(SaasGoogleDriveBackupService::class)->isEnabled() ? 'success' : 'gray')
                ->url(fn (): string => SaasBackupCloudSettings::getUrl()),

            Action::make('restoreCloudBackup')
                ->label('Restaurar do Google Drive')
                ->icon('heroicon-o-cloud-arrow-down')
                ->color('danger')
                ->visible(fn (): bool => app(SaasGoogleDriveBackupService::class)->isConfigured())
                ->form([
                    Select::make('drive_file')
                        ->label('Backup Criptografado no Google Drive')
                        ->options(function (): array {
                            try {
                                $files = app(SaasGoogleDriveBackupService::class)->listEncryptedBackups();
                                $options = [];
                                foreach ($files as $file) {
                                    $sizeMb = round(($file['size'] ?? 0) / 1048576, 2).' MB';
                                    $date = ! empty($file['created_at'])
                                        ? Carbon::parse($file['created_at'])->format('d/m/Y H:i')
                                        : 'Data n/d';
                                    $label = ($file['is_global'] ? '🌐 GLOBAL' : "💈 Barbearia ({$file['scope']})")." — {$date} ({$sizeMb})";
                                    $options[$file['id'].'|'.$file['name']] = $label;
                                }

                                return $options;
                            } catch (\Throwable $e) {
                                return [];
                            }
                        })
                        ->searchable()
                        ->required(),
                    TextInput::make('confirmation')
                        ->label('Confirmação obrigatória')
                        ->placeholder('RESTAURAR BACKUP DA NUVEM')
                        ->helperText('Digite exatamente RESTAURAR BACKUP DA NUVEM para confirmar a operação.')
                        ->required(),
                ])
                ->modalHeading('Restaurar Backup Criptografado do Google Drive')
                ->modalDescription('O backup selecionado será baixado do Google Drive e descriptografado usando o APP_KEY deste servidor. Antes da restauração, uma cópia preventiva de segurança local será gerada automaticamente.')
                ->modalSubmitActionLabel('Baixar, Descriptografar e Restaurar')
                ->action(function (array $data, LocalBackupManager $backupManager, SaasGoogleDriveBackupService $driveService): void {
                    if (trim((string) ($data['confirmation'] ?? '')) !== 'RESTAURAR BACKUP DA NUVEM') {
                        Notification::make()
                            ->title('Confirmação textual incorreta')
                            ->body('A restauração foi cancelada por segurança. Nenhuma alteração foi realizada.')
                            ->danger()
                            ->send();

                        return;
                    }

                    [$fileId, $filename] = array_pad(explode('|', (string) ($data['drive_file'] ?? ''), 2), 2, '');
                    if (empty($fileId) || empty($filename)) {
                        Notification::make()->title('Arquivo inválido selecionado')->danger()->send();

                        return;
                    }

                    try {
                        $decryptedPath = $driveService->downloadAndDecryptBackup($fileId, $filename);
                        $backupManager->restoreFromPath($decryptedPath);

                        Notification::make()
                            ->title('Backup do Google Drive restaurado com sucesso!')
                            ->body("O arquivo '{$filename}' foi baixado, autenticado e restaurado com sucesso.")
                            ->success()
                            ->persistent()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Falha ao restaurar backup do Google Drive')
                            ->body($e->getMessage())
                            ->danger()
                            ->persistent()
                            ->send();
                    }
                }),

            Action::make('createBackup')
                ->label('Gerar Novo Backup')
                ->icon('heroicon-o-plus-circle')
                ->color('primary')
                ->form([
                    Select::make('scope')
                        ->label('Escopo do Backup')
                        ->options(function (): array {
                            $options = [
                                'GLOBAL' => '🌐 Base Completa do Aurobarber (aurobarber_prod)',
                            ];
                            $shops = Barbershop::query()->with('parent')->orderBy('name')->get();
                            foreach ($shops as $shop) {
                                if ($shop->isBranch()) {
                                    $options[$shop->slug] = "💈 Filial: {$shop->name} (Matriz: {$shop->parent?->name}) (/{$shop->slug})";
                                } else {
                                    $options[$shop->slug] = "💈 Barbearia: {$shop->name} (/{$shop->slug})";
                                }
                            }

                            return $options;
                        })
                        ->default('GLOBAL')
                        ->required(),
                ])
                ->action(function (array $data, LocalBackupManager $backupManager): void {
                    try {
                        $scope = (string) $data['scope'];
                        $schema = null;
                        if ($scope !== 'GLOBAL') {
                            $shop = Barbershop::where('slug', $scope)->first();
                            $schema = $shop?->schema_name ?: null;
                        }
                        $filename = $backupManager->create(false, $schema);

                        Notification::make()
                            ->title('Backup gerado com sucesso!')
                            ->body("Arquivo criado: {$filename}")
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Erro ao gerar backup')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getBackupsProperty(): Collection
    {
        $backupManager = app(LocalBackupManager::class);
        $files = $backupManager->list();
        $shops = Barbershop::query()->get()->keyBy('slug');

        $all = collect($files)->map(function (array $item) use ($shops): array {
            $fn = $item['name'];
            $isGlobal = ! str_contains($fn, '-tenant-');
            $scope = 'global';
            if (preg_match('/-tenant-(?:tenant_)?([a-zA-Z0-9_]+)-/', $fn, $matches)) {
                $scope = $matches[1];
            }
            $createdAt = Carbon::parse($item['created_at']);
            $expiresAt = (clone $createdAt)->addDays(7);
            $daysRemaining = max(0, (int) now()->diffInDays($expiresAt, false));
            $sizeBytes = (int) ($item['size'] ?? 0);
            $sizeFormatted = $sizeBytes < 1048576
                ? round($sizeBytes / 1024, 1).' KB'
                : round($sizeBytes / 1048576, 2).' MB';

            $shop = $shops->get($scope);
            $barbershopName = $isGlobal
                ? 'Base Completa (Todas as Barbearias / Global)'
                : ($shop ? $shop->name : "Barbearia: {$scope}");

            return [
                'filename' => $fn,
                'scope' => $scope,
                'is_global' => $isGlobal,
                'barbershop_name' => $barbershopName,
                'size_bytes' => $sizeBytes,
                'size_formatted' => $sizeFormatted,
                'created_at' => $createdAt,
                'created_at_formatted' => $createdAt->format('d/m/Y H:i:s'),
                'expires_at_formatted' => $expiresAt->format('d/m/Y H:i'),
                'days_remaining' => $daysRemaining,
                'is_expired' => now()->greaterThan($expiresAt),
            ];
        });

        return match ($this->filterScope) {
            'global' => $all->filter(fn ($b) => $b['is_global'])->values(),
            'tenants' => $all->filter(fn ($b) => ! $b['is_global'])->values(),
            default => $all,
        };
    }

    public function simulateBackup(string $filename): void
    {
        abort_unless(auth()->user()?->role === 'master_admin', 403);

        try {
            $backupManager = app(LocalBackupManager::class);
            $report = $backupManager->simulateRestore($filename);

            Notification::make()
                ->title('🧪 Simulação de Restauração Concluída!')
                ->body($report['message'])
                ->success()
                ->persistent()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Falha na Simulação de Restauração')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }
    }

    public function restoreBackup(string $filename): void
    {
        abort_unless(auth()->user()?->role === 'master_admin', 403);

        try {
            $backupManager = app(LocalBackupManager::class);
            $backupManager->restore($filename);

            Notification::make()
                ->title('Backup restaurado com sucesso!')
                ->body("O arquivo '{$filename}' foi restaurado. Uma cópia pré-restauração preventiva foi gerada automaticamente.")
                ->success()
                ->persistent()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Erro ao restaurar backup')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }
    }

    public function createSandbox(string $filename, SaasRestoreTestService $service): void
    {
        abort_unless(auth()->user()?->role === 'master_admin', 403);

        try {
            $run = $service->createSandbox($filename, auth()->id());

            $tableCount = $run->automated_check_results['table_count'] ?? 0;

            Notification::make()
                ->title('🛡️ Sandbox de Restauração Criada com Sucesso!')
                ->body("Ambiente isolado '{$run->target_schema}' provisionado. {$tableCount} tabelas restauradas sem alterar dados de produção.")
                ->success()
                ->persistent()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Falha ao Criar Sandbox')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }
    }

    public function cleanupSandbox(int $runId, SaasRestoreTestService $service): void
    {
        abort_unless(auth()->user()?->role === 'master_admin', 403);

        try {
            $run = SaasRestoreTestRun::findOrFail($runId);
            $service->cleanupSandbox($run);

            Notification::make()
                ->title('Sandbox Destruída com Sucesso')
                ->body("O schema '{$run->target_schema}' foi removido e recursos liberados.")
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Erro ao Limpar Sandbox')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function openAuditModal(int $runId): void
    {
        $this->selectedSandboxId = $runId;
        $run = SaasRestoreTestRun::find($runId);
        $this->validationStatus = $run?->human_validation_status === 'pending' ? 'approved' : ($run?->human_validation_status ?? 'approved');
        $this->validationNotes = $run?->human_validation_notes;
        $this->showAuditModal = true;
    }

    public function closeAuditModal(): void
    {
        $this->showAuditModal = false;
        $this->selectedSandboxId = null;
    }

    public function submitValidation(SaasRestoreTestService $service): void
    {
        abort_unless(auth()->user()?->role === 'master_admin', 403);

        if (! $this->selectedSandboxId) {
            return;
        }

        try {
            $run = SaasRestoreTestRun::findOrFail($this->selectedSandboxId);
            $service->submitHumanValidation($run, $this->validationStatus, $this->validationNotes, auth()->id());

            Notification::make()
                ->title('Laudo de Auditoria Registrado!')
                ->body('O status de validação humana foi gravado no histórico de auditoria.')
                ->success()
                ->send();

            $this->closeAuditModal();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Erro ao Salvar Laudo')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function getActiveSandboxRunsProperty(): Collection
    {
        return SaasRestoreTestRun::query()
            ->latest()
            ->take(8)
            ->get();
    }

    public function getSelectedSandboxProperty(): ?SaasRestoreTestRun
    {
        return $this->selectedSandboxId ? SaasRestoreTestRun::find($this->selectedSandboxId) : null;
    }

    public function deleteBackup(string $filename): void
    {
        abort_unless(auth()->user()?->role === 'master_admin', 403);

        try {
            $path = storage_path('app/private/backups/'.$filename);
            if (file_exists($path)) {
                unlink($path);
                Notification::make()->title('Backup excluído')->success()->send();
            }
        } catch (\Throwable $e) {
            Notification::make()->title('Erro ao excluir: '.$e->getMessage())->danger()->send();
        }
    }
}

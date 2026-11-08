<?php

namespace App\Filament\Pages;

use App\Services\LocalBackupManager;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use UnitEnum;

class BackupManagement extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Ajuda e suporte';

    protected static ?string $navigationLabel = 'Backup e restauração';

    protected static ?string $title = 'Backup e restauração';

    protected static ?string $slug = 'backup-management';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-circle-stack';

    protected static ?int $navigationSort = 20;

    protected string $view = 'filament.pages.backup-management';

    public static function canAccess(): bool
    {
        // No ambiente SaaS Cloud, backups locais são desativados para evitar riscos e corrupção de banco;
        // a VPS gerencia rotinas diárias automatizadas de backup no PostgreSQL
        if ((bool) env('SAAS_ENABLED', false)) {
            return false;
        }

        return Auth::user()?->can('View:BackupManagement') ?? false;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createBackup')
                ->label('Criar backup agora')
                ->icon('heroicon-o-archive-box-arrow-down')
                ->color('primary')
                ->visible(fn (): bool => Auth::user()?->can('Create:BackupManagement') ?? false)
                ->action(function (): void {
                    try {
                        $filename = app(LocalBackupManager::class)->create();
                        $driveService = app(\App\Services\GoogleDriveBackupService::class);
                        $driveStatus = '';

                        if ($driveService->isConfigured()) {
                            try {
                                $filePath = storage_path('app/private/backups/' . $filename);
                                $uploadResult = $driveService->uploadFile($filePath, $filename);

                                \App\Models\School::query()->update([
                                    'google_drive_last_backup_at' => now(),
                                    'google_drive_last_backup_status' => 'Concluído com sucesso (ID: ' . $uploadResult['file_id'] . ')',
                                ]);

                                $driveStatus = ' e sincronizado na nuvem (Google Drive ☁️)';
                            } catch (\Throwable $cloudEx) {
                                report($cloudEx);
                                $driveStatus = ' (aviso: salvo localmente, mas falhou ao enviar para o Drive: ' . $cloudEx->getMessage() . ')';
                            }
                        }

                        Notification::make()
                            ->title('Backup criado com sucesso!')
                            ->body($filename . $driveStatus)
                            ->success()
                            ->send();
                    } catch (\Throwable $exception) {
                        report($exception);
                        Notification::make()->title('Não foi possível criar o backup')->body($exception->getMessage())->danger()->send();
                    }
                }),
            Action::make('importBackup')
                ->label('Importar do Pen Drive / Arquivo')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->visible(fn (): bool => Auth::user()?->can('Create:BackupManagement') || Auth::user()?->can('Restore:BackupManagement') || Auth::user()?->hasRole('super_admin'))
                ->schema([
                    Radio::make('source')
                        ->label('Como deseja importar o backup?')
                        ->options([
                            'upload' => 'Carregar arquivo .zip do computador ou Pen Drive',
                            'path' => 'Informar caminho do arquivo no Pen Drive / Disco',
                        ])
                        ->default('upload')
                        ->live()
                        ->required(),
                    FileUpload::make('backup_file')
                        ->label('Arquivo de backup (.zip)')
                        ->helperText('Selecione o arquivo .zip de backup gerado pelo Aurosoft.')
                        ->disk('local')
                        ->directory('private/backups/uploads')
                        ->acceptedFileTypes(['application/zip', 'application/x-zip-compressed', 'application/octet-stream', 'multipart/x-zip'])
                        ->maxSize(512000)
                        ->visible(fn (Get $get): bool => ($get('source') ?? 'upload') === 'upload')
                        ->required(fn (Get $get): bool => ($get('source') ?? 'upload') === 'upload'),
                    Select::make('detected_external_file')
                        ->label('Backups detectados no Pen Drive / Discos')
                        ->options(fn (): array => app(LocalBackupManager::class)->scanExternalDrives())
                        ->placeholder('Selecione um backup encontrado no Pen Drive ou informe abaixo')
                        ->visible(fn (Get $get): bool => $get('source') === 'path' && ! empty(app(LocalBackupManager::class)->scanExternalDrives()))
                        ->live()
                        ->afterStateUpdated(fn ($state, callable $set) => $state ? $set('external_path', $state) : null),
                    TextInput::make('external_path')
                        ->label('Caminho no Pen Drive ou Disco')
                        ->placeholder(PHP_OS_FAMILY === 'Windows' ? 'Ex: D:\aurosoft-backup-20261027-185631.zip ou E:\backup.zip' : 'Ex: /media/pendrive/backup.zip')
                        ->helperText('Informe o caminho completo do arquivo de backup.')
                        ->visible(fn (Get $get): bool => $get('source') === 'path')
                        ->required(fn (Get $get): bool => $get('source') === 'path'),
                ])
                ->modalHeading('Importar Backup Externo')
                ->modalDescription('O arquivo de backup será verificado e copiado para a lista de backups locais do sistema.')
                ->modalSubmitActionLabel('Importar arquivo')
                ->action(function (array $data): void {
                    $manager = app(LocalBackupManager::class);
                    $source = $data['source'] ?? 'upload';

                    try {
                        if ($source === 'upload') {
                            $uploaded = $data['backup_file'] ?? null;
                            if (! $uploaded) {
                                throw new RuntimeException('Nenhum arquivo de backup foi selecionado.');
                            }

                            $fullPath = Storage::disk('local')->path($uploaded);
                            if (! File::isFile($fullPath)) {
                                throw new RuntimeException('O arquivo enviado não foi encontrado.');
                            }

                            $filename = $manager->importFromExternalPath($fullPath);
                            @File::delete($fullPath);
                        } else {
                            $path = trim($data['external_path'] ?? ($data['detected_external_file'] ?? ''));
                            if ($path === '' || ! File::isFile($path)) {
                                throw new RuntimeException("O arquivo de backup '{$path}' não foi encontrado.");
                            }

                            $filename = $manager->importFromExternalPath($path);
                        }

                        Notification::make()
                            ->title('Backup Importado com Sucesso')
                            ->body("O arquivo '{$filename}' foi verificado e adicionado aos backups locais.")
                            ->success()
                            ->send();
                    } catch (\Throwable $exception) {
                        report($exception);
                        Notification::make()
                            ->title('Falha ao Importar Backup')
                            ->body($exception->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            Action::make('restoreBackup')
                ->label('Restaurar backup')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('danger')
                ->visible(fn (): bool => Auth::user()?->can('Restore:BackupManagement') ?? false)
                ->schema([
                    Radio::make('source')
                        ->label('Origem do arquivo de backup')
                        ->options([
                            'local' => 'Backup salvo no sistema',
                            'upload' => 'Carregar arquivo .zip (Pen Drive ou computador)',
                            'path' => 'Caminho de arquivo / Pen Drive conectado',
                        ])
                        ->default('local')
                        ->live()
                        ->required(),
                    Select::make('filename')
                        ->label('Arquivo de backup local')
                        ->options(fn (): array => collect(app(LocalBackupManager::class)->list())->mapWithKeys(fn (array $backup): array => [$backup['name'] => $backup['name'].' — '.number_format($backup['size'] / 1048576, 2, ',', '.').' MB'])->all())
                        ->visible(fn (Get $get): bool => ($get('source') ?? 'local') === 'local')
                        ->required(fn (Get $get): bool => ($get('source') ?? 'local') === 'local'),
                    FileUpload::make('backup_file')
                        ->label('Arquivo de backup (.zip)')
                        ->helperText('Selecione o arquivo .zip de backup do seu computador ou Pen Drive.')
                        ->disk('local')
                        ->directory('private/backups/uploads')
                        ->acceptedFileTypes(['application/zip', 'application/x-zip-compressed', 'application/octet-stream', 'multipart/x-zip'])
                        ->maxSize(512000)
                        ->visible(fn (Get $get): bool => $get('source') === 'upload')
                        ->required(fn (Get $get): bool => $get('source') === 'upload'),
                    Select::make('detected_external_file')
                        ->label('Backups detectados no Pen Drive / Discos')
                        ->options(fn (): array => app(LocalBackupManager::class)->scanExternalDrives())
                        ->placeholder('Selecione um backup encontrado no Pen Drive ou informe abaixo')
                        ->visible(fn (Get $get): bool => $get('source') === 'path' && ! empty(app(LocalBackupManager::class)->scanExternalDrives()))
                        ->live()
                        ->afterStateUpdated(fn ($state, callable $set) => $state ? $set('external_path', $state) : null),
                    TextInput::make('external_path')
                        ->label('Caminho no Pen Drive ou Disco')
                        ->placeholder(PHP_OS_FAMILY === 'Windows' ? 'Ex: D:\aurosoft-backup-20261027-185631.zip ou E:\backup.zip' : 'Ex: /media/pendrive/backup.zip')
                        ->helperText('Informe o caminho completo do arquivo de backup no Pen Drive ou disco.')
                        ->visible(fn (Get $get): bool => $get('source') === 'path')
                        ->required(fn (Get $get): bool => $get('source') === 'path'),
                    TextInput::make('confirmation')
                        ->label('Digite RESTAURAR para confirmar')
                        ->helperText('Esta ação substituirá o banco de dados e arquivos atuais. Um backup de segurança será criado antes.')
                        ->required()
                        ->rules(['in:RESTAURAR']),
                ])
                ->modalHeading('Substituir os dados atuais?')
                ->modalDescription('A restauração substitui o banco de dados e os arquivos enviados. Antes disso, o sistema criará automaticamente um backup de segurança. Mantenha esta página aberta até a conclusão.')
                ->modalSubmitActionLabel('Restaurar dados')
                ->requiresConfirmation()
                ->action(function (array $data): void {
                    abort_unless(($data['confirmation'] ?? null) === 'RESTAURAR', 422);

                    $manager = app(LocalBackupManager::class);
                    $source = $data['source'] ?? 'local';

                    try {
                        if ($source === 'local') {
                            $filename = $data['filename'] ?? null;
                            if (! $filename) {
                                throw new RuntimeException('Selecione um arquivo de backup local.');
                            }
                            $manager->restore($filename);
                        } elseif ($source === 'upload') {
                            $uploaded = $data['backup_file'] ?? null;
                            if (! $uploaded) {
                                throw new RuntimeException('Nenhum arquivo de backup foi selecionado.');
                            }

                            $fullPath = Storage::disk('local')->path($uploaded);
                            if (! File::isFile($fullPath)) {
                                throw new RuntimeException('O arquivo enviado não foi encontrado.');
                            }

                            $importedFilename = $manager->importFromExternalPath($fullPath);
                            @File::delete($fullPath);

                            $manager->restore($importedFilename);
                        } elseif ($source === 'path') {
                            $path = trim($data['external_path'] ?? ($data['detected_external_file'] ?? ''));
                            if ($path === '' || ! File::isFile($path)) {
                                throw new RuntimeException("O arquivo de backup '{$path}' não foi encontrado no Pen Drive ou disco.");
                            }

                            $importedFilename = $manager->importFromExternalPath($path);
                            $manager->restore($importedFilename);
                        }

                        Notification::make()->title('Restauração concluída')->body('Os dados foram restaurados com sucesso.')->success()->persistent()->send();
                    } catch (\Throwable $exception) {
                        report($exception);
                        Notification::make()->title('A restauração falhou')->body($exception->getMessage())->danger()->persistent()->send();
                    }
                }),
        ];
    }

    public bool $showExportModal = false;

    public string $exportFilename = '';

    public string $exportDestination = '/media';

    public function backups(): array
    {
        try {
            return app(LocalBackupManager::class)->list();
        } catch (RuntimeException $exception) {
            return [];
        }
    }

    public function backupStatus(): array
    {
        try {
            $status = app(LocalBackupManager::class)->status();
            $school = \App\Models\School::query()
                ->whereNotNull('google_drive_last_backup_at')
                ->orderByDesc('google_drive_last_backup_at')
                ->first();

            $status['cloud_backup_at'] = $school?->google_drive_last_backup_at?->format('d/m/Y H:i:s');
            $status['cloud_backup_status'] = $school?->google_drive_last_backup_status;

            return $status;
        } catch (\Throwable $e) {
            return [
                'total_backups' => 0,
                'is_healthy' => false,
                'badge_color' => 'danger',
                'message' => 'Status não disponível.',
            ];
        }
    }

    public function openExportModal(string $filename): void
    {
        $this->exportFilename = $filename;
        $this->showExportModal = true;
    }

    public function closeExportModal(): void
    {
        $this->showExportModal = false;
        $this->exportFilename = '';
    }

    public function uploadToGoogleDrive(string $filename): void
    {
        abort_unless(Auth::user()?->can('Create:BackupManagement') || Auth::user()?->hasRole('super_admin'), 403);

        $driveService = app(\App\Services\GoogleDriveBackupService::class);
        if (! $driveService->isConfigured()) {
            Notification::make()->title('Google Drive não configurado')->body('Configure as credenciais no arquivo .env.')->warning()->send();

            return;
        }

        try {
            $filePath = storage_path('app/private/backups/' . $filename);
            $uploadResult = $driveService->uploadFile($filePath, $filename);

            \App\Models\School::query()->update([
                'google_drive_last_backup_at' => now(),
                'google_drive_last_backup_status' => 'Concluído com sucesso (ID: ' . $uploadResult['file_id'] . ')',
            ]);

            Notification::make()
                ->title('Enviado para o Google Drive!')
                ->body("O backup {$filename} foi copiado com sucesso para a pasta da nuvem.")
                ->success()
                ->send();
        } catch (\Throwable $e) {
            report($e);
            Notification::make()
                ->title('Falha ao Enviar para Google Drive')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function submitExport(): void
    {
        abort_unless(Auth::user()?->can('Download:BackupManagement'), 403);

        if (trim($this->exportDestination) === '') {
            Notification::make()->title('Destino obrigatório')->body('Informe a pasta do Pen Drive ou disco externo.')->warning()->send();

            return;
        }

        try {
            $copiedPath = app(LocalBackupManager::class)->copyToExternalPath($this->exportFilename, trim($this->exportDestination));
            Notification::make()
                ->title('Backup Copiado com Sucesso!')
                ->body("Arquivo copiado com segurança para: {$copiedPath}")
                ->success()
                ->send();

            $this->closeExportModal();
        } catch (\Throwable $e) {
            report($e);
            Notification::make()
                ->title('Falha ao Copiar para Destino')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function deleteBackup(string $filename): void
    {
        abort_unless(Auth::user()?->can('Restore:BackupManagement') || Auth::user()?->hasRole('super_admin'), 403);

        try {
            app(LocalBackupManager::class)->delete($filename);
            Notification::make()->title('Backup Excluído')->success()->send();
        } catch (\Throwable $e) {
            report($e);
            Notification::make()->title('Erro ao excluir')->body($e->getMessage())->danger()->send();
        }
    }

    public function downloadBackup(string $filename)
    {
        abort_unless(Auth::user()?->can('Download:BackupManagement') || Auth::user()?->hasRole('super_admin'), 403);

        return redirect()->route('admin.backups.download', ['filename' => $filename]);
    }
}

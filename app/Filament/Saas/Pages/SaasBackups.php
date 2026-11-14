<?php

declare(strict_types=1);

namespace App\Filament\Saas\Pages;

use App\Models\Barbershop;
use App\Services\LocalBackupManager;
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

    public function getHeaderActions(): array
    {
        return [
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
                            $shops = Barbershop::query()->orderBy('name')->get();
                            foreach ($shops as $shop) {
                                $options[$shop->slug] = "💈 Barbearia: {$shop->name} (/{$shop->slug})";
                            }

                            return $options;
                        })
                        ->default('GLOBAL')
                        ->required(),
                ])
                ->action(function (array $data, LocalBackupManager $backupManager): void {
                    try {
                        $scope = (string) $data['scope'];
                        $schema = $scope === 'GLOBAL' ? null : "tenant_{$scope}";
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

<?php

declare(strict_types=1);

namespace App\Filament\Saas\Pages;

use App\Services\SaasBackupManager;
use App\Services\TenantService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use UnitEnum;

class SaasBackups extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-server-stack';

    protected static ?string $navigationLabel = 'Quadro Geral de Backups';

    protected static ?string $title = 'Quadro Geral de Backups e Retenção';

    protected static string|UnitEnum|null $navigationGroup = 'Infraestrutura e Dados';

    protected static ?int $navigationSort = 10;

    protected static string $routePath = '/backups';

    protected string $view = 'filament.saas.pages.backups';

    public string $filterScope = 'all'; // 'all', 'global', 'tenants'

    public function getHeaderActions(): array
    {
        return [
            Action::make('createAll')
                ->label('Gerar Backup Completo (Global + Todos os Clientes)')
                ->icon('heroicon-o-arrow-path-rounded-square')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Confirmar Execução de Backups')
                ->modalDescription('Isso gerará o dump da base global e os dumps individuais de cada tenant ativo, aplicando a retenção de 7 dias.')
                ->action(function (SaasBackupManager $backupManager): void {
                    try {
                        $generated = $backupManager->createAllBackups();
                        $count = count($generated);
                        Notification::make()
                            ->title('Backups gerados com sucesso!')
                            ->body("Foram gerados {$count} arquivos (1 Global + " . ($count - 1) . " por cliente).")
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Erro ao gerar backups')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('createSpecific')
                ->label('Gerar Backup Individual')
                ->icon('heroicon-o-plus-circle')
                ->color('gray')
                ->form([
                    Select::make('scope')
                        ->label('Selecione o Cliente / Escopo')
                        ->options(function (TenantService $tenantService): array {
                            $options = [
                                'GLOBAL' => '🌐 Base Completa (Todos os Clientes / Global)',
                            ];
                            foreach ($tenantService->listTenants() as $tenant) {
                                $options[$tenant['name']] = "🏫 {$tenant['name']} (Tamanho: {$tenant['size']})";
                            }
                            return $options;
                        })
                        ->required()
                        ->default('GLOBAL'),
                ])
                ->action(function (array $data, SaasBackupManager $backupManager): void {
                    try {
                        $scope = $data['scope'];
                        if ($scope === 'GLOBAL') {
                            $file = $backupManager->createGlobalBackup();
                        } else {
                            $file = $backupManager->createTenantBackup($scope);
                        }

                        Notification::make()
                            ->title('Backup gerado com sucesso!')
                            ->body("Arquivo gerado: {$file}")
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

            Action::make('purgeOld')
                ->label('Limpar Vencidos (> 7 dias)')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function (SaasBackupManager $backupManager): void {
                    $pruned = $backupManager->applyRetention(7);
                    Notification::make()
                        ->title('Rotina de retenção executada')
                        ->body($pruned > 0 ? "{$pruned} backup(s) antigos foram removidos com sucesso." : 'Nenhum backup expirado encontrado no disco.')
                        ->info()
                        ->send();
                }),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getBackupsProperty(): Collection
    {
        $all = app(SaasBackupManager::class)->list();

        return match ($this->filterScope) {
            'global' => $all->filter(fn ($b) => $b['is_global'])->values(),
            'tenants' => $all->filter(fn ($b) => ! $b['is_global'])->values(),
            default => $all,
        };
    }

    public function deleteBackup(string $filename): void
    {
        $deleted = app(SaasBackupManager::class)->delete($filename);

        if ($deleted) {
            Notification::make()
                ->title('Backup excluído com sucesso!')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Não foi possível excluir o arquivo')
                ->danger()
                ->send();
        }
    }
}

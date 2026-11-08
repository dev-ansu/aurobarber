<?php

declare(strict_types=1);

namespace App\Filament\Developer\Pages;

use App\Services\SystemDiagnosticService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class DeveloperDashboard extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cpu-chip';

    protected static ?string $navigationLabel = 'Visão Geral & Saúde';

    protected static ?string $title = 'Painel do Desenvolvedor';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.developer.pages.developer-dashboard';

    public array $serverHealth = [];

    public array $dbHealth = [];

    public array $apisHealth = [];

    public array $licenseHealth = [];

    public array $codeIntegrity = [];

    public function mount(SystemDiagnosticService $diagnosticService): void
    {
        $this->loadDiagnostics($diagnosticService);
    }

    public function refreshDiagnostics(SystemDiagnosticService $diagnosticService): void
    {
        $this->loadDiagnostics($diagnosticService);

        Notification::make()
            ->title('Diagnóstico atualizado com sucesso!')
            ->success()
            ->send();
    }

    public function forceRefreshLicense(SystemDiagnosticService $diagnosticService): void
    {
        $this->licenseHealth = $diagnosticService->getLicenseHealth(true);

        Notification::make()
            ->title('Status da licença consultado diretamente na nuvem!')
            ->body($this->licenseHealth['message'] ?? 'Verificação concluída.')
            ->info()
            ->send();
    }

    protected function loadDiagnostics(SystemDiagnosticService $diagnosticService): void
    {
        $this->serverHealth = $diagnosticService->getServerHealth();
        $this->dbHealth = $diagnosticService->getDatabaseHealth();
        $this->apisHealth = $diagnosticService->getApisHealth();
        $this->licenseHealth = $diagnosticService->getLicenseHealth();
        $this->codeIntegrity = $diagnosticService->getCodeIntegrity();
    }
}

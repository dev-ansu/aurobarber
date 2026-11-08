<?php

declare(strict_types=1);

namespace App\Filament\Developer\Pages;

use App\Services\SystemDiagnosticService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class LicenseInspection extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationLabel = 'Status da Licença';

    protected static ?string $title = 'Auditoria & Validação de Licença';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.developer.pages.license-inspection';

    public array $license = [];

    public function mount(SystemDiagnosticService $diagnosticService): void
    {
        $this->license = $diagnosticService->getLicenseHealth();
    }

    public function forceCheck(SystemDiagnosticService $diagnosticService): void
    {
        $this->license = $diagnosticService->getLicenseHealth(true);

        Notification::make()
            ->title('Validação de licença consultada na nuvem!')
            ->body($this->license['message'] ?? 'Concluído.')
            ->info()
            ->send();
    }
}

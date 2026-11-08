<?php

declare(strict_types=1);

namespace App\Filament\Developer\Pages;

use App\Services\SystemDiagnosticService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class CodeIntegrity extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Integridade do Código';

    protected static ?string $title = 'Integridade do Código & Anti-Adulteração';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.developer.pages.code-integrity';

    public array $integrity = [];

    public function mount(SystemDiagnosticService $diagnosticService): void
    {
        $this->refreshIntegrity($diagnosticService);
    }

    public function refreshIntegrity(SystemDiagnosticService $diagnosticService): void
    {
        $this->integrity = $diagnosticService->getCodeIntegrity();

        Notification::make()
            ->title('Status do código atualizado!')
            ->success()
            ->send();
    }
}

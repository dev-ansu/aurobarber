<?php

declare(strict_types=1);

namespace App\Filament\Developer\Pages;

use App\Services\SystemDiagnosticService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class SystemLogs extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Logs do Sistema';

    protected static ?string $title = 'Logs & Exceções da Aplicação';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.developer.pages.system-logs';

    public array $logs = [];

    public string $levelFilter = 'ALL';

    public string $search = '';

    public function mount(SystemDiagnosticService $diagnosticService): void
    {
        $this->loadLogs($diagnosticService);
    }

    public function filterByLevel(string $level, SystemDiagnosticService $diagnosticService): void
    {
        $this->levelFilter = $level;
        $this->loadLogs($diagnosticService);
    }

    public function clearAllLogs(SystemDiagnosticService $diagnosticService): void
    {
        $diagnosticService->clearLogs();
        $this->loadLogs($diagnosticService);

        Notification::make()
            ->title('Arquivo de logs limpo com sucesso!')
            ->success()
            ->send();
    }

    public function loadLogs(SystemDiagnosticService $diagnosticService): void
    {
        $level = match ($this->levelFilter) {
            'ERROR' => 'ERROR',
            'WARNING' => 'WARNING',
            'INFO' => 'INFO',
            default => null,
        };

        $rawLogs = $diagnosticService->getRecentLogs(100, $level);

        if (! empty($this->search)) {
            $term = mb_strtolower($this->search);
            $rawLogs = array_filter($rawLogs, fn (array $l) => str_contains(mb_strtolower($l['message']), $term)
                || str_contains(mb_strtolower($l['trace']), $term)
            );
        }

        $this->logs = array_values($rawLogs);
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Developer\Pages;

use App\Services\SystemDiagnosticService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ApiDiagnostics extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-signal';

    protected static ?string $navigationLabel = 'Diagnóstico de APIs';

    protected static ?string $title = 'Diagnóstico de Conexões & APIs';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.developer.pages.api-diagnostics';

    public array $apis = [];

    public array $db = [];

    public function mount(SystemDiagnosticService $diagnosticService): void
    {
        $this->runAllTests($diagnosticService);
    }

    public function runAllTests(SystemDiagnosticService $diagnosticService): void
    {
        $this->apis = $diagnosticService->getApisHealth();
        $this->db = $diagnosticService->getDatabaseHealth();

        Notification::make()
            ->title('Testes de conectividade executados com sucesso!')
            ->success()
            ->send();
    }

    public function testOuroModerno(SystemDiagnosticService $diagnosticService): void
    {
        $this->apis['ouro_moderno'] = $diagnosticService->checkOuroModernoApi();

        Notification::make()
            ->title('API Ouro Moderno testada')
            ->body($this->apis['ouro_moderno']['message'] ?? '')
            ->send();
    }

    public function testTailscale(SystemDiagnosticService $diagnosticService): void
    {
        $this->apis['tailscale'] = $diagnosticService->checkTailscale();

        Notification::make()
            ->title('Tailscale verificado')
            ->body($this->apis['tailscale']['message'] ?? '')
            ->send();
    }

    public function testSmtp(SystemDiagnosticService $diagnosticService): void
    {
        $this->apis['smtp'] = $diagnosticService->checkSmtpConnection();

        Notification::make()
            ->title('Servidor SMTP testado')
            ->body($this->apis['smtp']['message'] ?? '')
            ->send();
    }

    public function testInternet(SystemDiagnosticService $diagnosticService): void
    {
        $this->apis['internet'] = $diagnosticService->checkInternetConnectivity();

        Notification::make()
            ->title('Conexão externa testada')
            ->body($this->apis['internet']['message'] ?? '')
            ->send();
    }

    public function refreshBackup(SystemDiagnosticService $diagnosticService): void
    {
        $this->apis['backup'] = $diagnosticService->getBackupHealth();

        Notification::make()
            ->title('Status de backups atualizado')
            ->body($this->apis['backup']['message'] ?? '')
            ->info()
            ->send();
    }

    public function dispatchBackupReminder(): void
    {
        try {
            $users = \App\Models\User::all();
            Notification::make()
                ->title('⚠️ Aviso de Segurança: Realize o Backup do Aurosoft')
                ->body('Lembrete de rotina: por segurança, realize o backup dos dados da escola e copie uma cópia para o Pen Drive para evitar perda de dados.')
                ->warning()
                ->persistent()
                ->sendToDatabase($users);

            Notification::make()
                ->title('Notificação de backup enviada!')
                ->body('O aviso de backup preventivo foi emitido para todos os usuários do sistema.')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Erro ao disparar aviso')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}

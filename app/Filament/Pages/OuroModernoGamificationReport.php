<?php

namespace App\Filament\Pages;

use App\Services\OuroModernoService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;
use UnitEnum;

class OuroModernoGamificationReport extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Relatórios';

    protected static ?string $navigationParentItem = 'Pedagógico';

    protected static ?string $navigationLabel = 'Gamificação Ouro Moderno';

    protected static ?string $title = 'Relatório de gamificação';

    protected static ?string $slug = 'ouro-moderno-gamification-report';

    protected static ?int $navigationSort = 7;

    protected string $view = 'filament.pages.ouro-moderno-gamification-report';

    public string $search = '';

    public string $status = 'todos';

    public int $playersPage = 1;

    public array $players = [];

    public array $ranking = [];

    public array $pagination = [];

    public ?string $lastUpdated = null;

    // Student profile modal & history
    public ?int $selectedStudentId = null;

    public ?array $selectedStudentProfile = null;

    public ?array $selectedStudentHistory = null;

    public string $historyTab = 'points'; // 'points' or 'coins'

    // Adjustment modal (Pontos / Moedas)
    public bool $showAdjustmentModal = false;

    public ?int $adjustStudentId = null;

    public string $adjustStudentName = '';

    public string $adjustType = 'points_add'; // 'points_add', 'points_remove', 'coins_add', 'coins_remove'

    public int $adjustAmount = 10;

    public string $adjustDescription = '';

    // Regras de pontos
    public bool $showRulesModal = false;

    public ?array $gameActions = null;

    public static function canAccess(): bool
    {
        return app(OuroModernoService::class)->isConfigured()
            && (auth()->user()?->can('View:OuroModernoGamificationReport') ?? false);
    }

    public function loadReport(): void
    {
        try {
            $service = app(OuroModernoService::class);
            $players = $service->gamificationPlayers($this->playersPage, 50, trim($this->search), $this->status);
            $ranking = $service->gamificationRanking(50);

            $this->players = is_array($players['players'] ?? null)
                ? array_values(array_filter($players['players'], 'is_array'))
                : [];
            $this->ranking = is_array($ranking['ranking'] ?? null)
                ? array_values(array_filter($ranking['ranking'], 'is_array'))
                : [];
            $this->pagination = [
                'current_page' => (int) ($players['current_page'] ?? $this->playersPage),
                'total_pages' => (int) ($players['total_pages'] ?? 1),
                'total_records' => (int) ($players['total_records'] ?? count($this->players)),
                'has_previous' => (bool) ($players['has_previous'] ?? false),
                'has_next' => (bool) ($players['has_next'] ?? false),
            ];
            $this->lastUpdated = now()->format('d/m/Y H:i');
        } catch (Throwable $exception) {
            report($exception);
            Notification::make()->title('Não foi possível carregar a gamificação')->body($exception->getMessage())->danger()->persistent()->send();
        }
    }

    public function goToPage(int $page): void
    {
        $this->playersPage = max(1, $page);
        $this->loadReport();
    }

    public function viewStudentProfile(int $studentId): void
    {
        try {
            $service = app(OuroModernoService::class);
            $this->selectedStudentId = $studentId;
            $this->selectedStudentProfile = $service->gamificationStudentProfile($studentId);
            $this->loadStudentHistory();
        } catch (Throwable $exception) {
            report($exception);
            Notification::make()->title('Erro ao carregar perfil do jogador')->body($exception->getMessage())->danger()->send();
        }
    }

    public function loadStudentHistory(): void
    {
        if (! $this->selectedStudentId) {
            return;
        }

        try {
            $service = app(OuroModernoService::class);
            if ($this->historyTab === 'coins') {
                $this->selectedStudentHistory = $service->gamificationCoinsHistory($this->selectedStudentId);
            } else {
                $this->selectedStudentHistory = $service->gamificationPointsHistory($this->selectedStudentId);
            }
        } catch (Throwable $exception) {
            report($exception);
            $this->selectedStudentHistory = ['error' => $exception->getMessage()];
        }
    }

    public function switchHistoryTab(string $tab): void
    {
        $this->historyTab = in_array($tab, ['points', 'coins'], true) ? $tab : 'points';
        $this->loadStudentHistory();
    }

    public function closeStudentProfile(): void
    {
        $this->selectedStudentId = null;
        $this->selectedStudentProfile = null;
        $this->selectedStudentHistory = null;
    }

    public function openAdjustmentModal(int $studentId, string $studentName, string $type = 'points_add'): void
    {
        $this->adjustStudentId = $studentId;
        $this->adjustStudentName = $studentName;
        $this->adjustType = $type;
        $this->adjustAmount = str_contains($type, 'coins') ? 20 : 10;
        $this->adjustDescription = '';
        $this->showAdjustmentModal = true;
    }

    public function closeAdjustmentModal(): void
    {
        $this->showAdjustmentModal = false;
        $this->adjustStudentId = null;
    }

    public function submitAdjustment(): void
    {
        if (! $this->adjustStudentId) {
            return;
        }

        if ($this->adjustAmount <= 0) {
            Notification::make()->title('Quantidade inválida')->body('Informe um valor maior que zero.')->warning()->send();

            return;
        }

        if (trim($this->adjustDescription) === '') {
            Notification::make()->title('Justificativa obrigatória')->body('Informe o motivo ou descrição do lançamento.')->warning()->send();

            return;
        }

        try {
            $service = app(OuroModernoService::class);
            $desc = trim($this->adjustDescription);

            match ($this->adjustType) {
                'points_add' => $service->gamificationAddPoints($this->adjustStudentId, $this->adjustAmount, $desc),
                'points_remove' => $service->gamificationRemovePoints($this->adjustStudentId, $this->adjustAmount, $desc),
                'coins_add' => $service->gamificationAddCoins($this->adjustStudentId, $this->adjustAmount, $desc),
                'coins_remove' => $service->gamificationRemoveCoins($this->adjustStudentId, $this->adjustAmount, $desc),
            };

            $label = match ($this->adjustType) {
                'points_add' => "+{$this->adjustAmount} pontos adicionados",
                'points_remove' => "-{$this->adjustAmount} pontos removidos",
                'coins_add' => "+{$this->adjustAmount} moedas adicionadas",
                'coins_remove' => "-{$this->adjustAmount} moedas removidas",
            };

            Notification::make()
                ->title('Ajuste Realizado!')
                ->body("{$label} para {$this->adjustStudentName}.")
                ->success()
                ->send();

            $this->closeAdjustmentModal();

            // Atualiza relatório
            $this->loadReport();

            if ($this->selectedStudentId === $this->adjustStudentId) {
                $this->viewStudentProfile($this->selectedStudentId);
            }
        } catch (Throwable $exception) {
            report($exception);
            Notification::make()->title('Erro ao processar ajuste')->body($exception->getMessage())->danger()->send();
        }
    }

    public function openRulesModal(): void
    {
        try {
            $service = app(OuroModernoService::class);
            $this->gameActions = $service->gamificationActions();
            $this->showRulesModal = true;
        } catch (Throwable $exception) {
            report($exception);
            Notification::make()->title('Erro ao carregar regras')->body($exception->getMessage())->danger()->send();
        }
    }

    public function closeRulesModal(): void
    {
        $this->showRulesModal = false;
    }

    public function exportReport(): StreamedResponse
    {
        abort_unless(auth()->user()?->can('Export:OuroModernoGamificationReport') ?? false, 403);
        abort_if($this->lastUpdated === null, 422, 'Carregue o relatório antes de exportar.');

        return response()->streamDownload(function (): void {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, ['Jogadores (página '.$this->playersPage.')'], ';', '"', '');
            fputcsv($stream, ['ID LMS', 'Aluno', 'Pontos', 'Moedas', 'Conquistas', 'Nível', 'Gamificação ativa'], ';', '"', '');

            foreach ($this->players as $player) {
                fputcsv($stream, [
                    $player['user_id'] ?? '', $player['user_name'] ?? '', $player['total_points'] ?? '',
                    $player['total_coins'] ?? '', $player['total_achievements'] ?? '',
                    data_get($player, 'level.current_level', data_get($player, 'level.name', '')),
                    $player['gamificacao_label'] ?? ($player['gamificacao_efetiva'] ?? ''),
                ], ';', '"', '');
            }

            fputcsv($stream, [], ';', '"', '');
            fputcsv($stream, ['Ranking (até 50)'], ';', '"', '');
            fputcsv($stream, ['Posição', 'Aluno', 'Pontos', 'Atividades em sala', 'Nível'], ';', '"', '');

            foreach ($this->ranking as $player) {
                fputcsv($stream, [
                    $player['ranking_position'] ?? '', $player['user_name'] ?? '',
                    $player['total_points'] ?? '', $player['sala_activities'] ?? '',
                    data_get($player, 'level.name', ''),
                ], ';', '"', '');
            }

            fclose($stream);
        }, 'relatorio-gamificacao-ouro-moderno-'.now()->format('Y-m-d-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}

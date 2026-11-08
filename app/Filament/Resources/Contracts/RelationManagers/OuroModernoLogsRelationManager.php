<?php

namespace App\Filament\Resources\Contracts\RelationManagers;

use App\Models\Contract;
use App\Models\User;
use App\Services\OuroModernoService;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class OuroModernoLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Atividade no LMS';

    protected static ?string $modelLabel = 'log do LMS';

    protected static ?string $pluralModelLabel = 'logs do LMS';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-list-bullet';

    public string $fromDate = '';

    public string $untilDate = '';

    public string $filter = '';

    public string $filterValue = '';

    public array $logs = [];

    public int $totalLogs = 0;

    public ?string $lastUpdated = null;

    public bool $hasQueried = false;

    public ?array $selectedTest = null;

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        $user = auth()->user();

        return $ownerRecord instanceof Contract
            && app(OuroModernoService::class)->forSchool($ownerRecord->school_id)->isConfigured()
            && $user instanceof User
            && ($user->hasRole('super_admin') || $user->can('View:OuroModernoStudentLogsReport'));
    }

    public function mount(): void
    {
        $contract = $this->getOwnerRecord();
        if ($contract instanceof Contract && $contract->student?->ouro_moderno_cod_int) {
            $this->loadLogs(notifySuccess: false);
        }
    }

    public function clearFilters(): void
    {
        $this->fromDate = '';
        $this->untilDate = '';
        $this->filter = '';
        $this->filterValue = '';
        $this->loadLogs(notifySuccess: false);
    }

    public function loadLogs(bool $notifySuccess = true): void
    {
        $this->validate([
            'fromDate' => ['nullable', 'date_format:Y-m-d'],
            'untilDate' => ['nullable', 'date_format:Y-m-d'],
            'filter' => ['nullable', 'in:,tipo,curso,aula'],
            'filterValue' => ['nullable', 'integer', 'min:0'],
        ]);

        if (filled($this->fromDate) && filled($this->untilDate)) {
            if (Carbon::parse($this->fromDate)->isAfter(Carbon::parse($this->untilDate))) {
                Notification::make()
                    ->title('Período inválido')
                    ->body('A data inicial não pode ser posterior à data final.')
                    ->warning()
                    ->send();

                return;
            }

            if (Carbon::parse($this->fromDate)->diffInDays(Carbon::parse($this->untilDate)) > 365) {
                Notification::make()
                    ->title('Período muito longo')
                    ->body('Selecione um período de até 366 dias.')
                    ->warning()
                    ->send();

                return;
            }
        }

        $contract = $this->getOwnerRecord();
        if (! $contract instanceof Contract) {
            return;
        }

        $student = $contract->student;
        if (! $student || ! $student->ouro_moderno_cod_int) {
            Notification::make()
                ->title('Aluno sem código no LMS')
                ->body('O aluno não possui código de integração (cod_int) cadastrado. Sincronize o contrato com o LMS primeiro.')
                ->warning()
                ->send();

            return;
        }

        try {
            $service = app(OuroModernoService::class)->forSchool($contract->school_id);

            $filters = [];

            if (filled($this->fromDate)) {
                $filters['inicio'] = Carbon::parse($this->fromDate)->toDateString();
            }

            if (filled($this->untilDate)) {
                $filters['fim'] = Carbon::parse($this->untilDate)->toDateString();
            }

            if (filled($this->filter)) {
                $filters['filtro'] = $this->filter;
            }

            if (filled($this->filterValue)) {
                $filters['valor'] = (int) $this->filterValue;
            }

            $response = $service->studentLogs((int) $student->ouro_moderno_cod_int, $filters);
            $rawLogs = data_get($response, 'Logs.Itens', data_get($response, 'logs.items', data_get($response, 'itens', [])));
            $this->logs = is_array($rawLogs) ? array_values(array_filter($rawLogs, 'is_array')) : [];
            $this->totalLogs = (int) data_get($response, 'Logs.Total', data_get($response, 'logs.total', count($this->logs)));
            $this->lastUpdated = now()->format('d/m/Y H:i');
            $this->hasQueried = true;
            $this->selectedTest = null;

            if ($notifySuccess) {
                Notification::make()
                    ->title('Logs do aluno carregados')
                    ->body("{$this->totalLogs} registro(s) encontrado(s).")
                    ->success()
                    ->send();
            }
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('Erro ao consultar logs do LMS')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Mapeamento dos nomes de cursos locais pelo idcurso da Ouro Moderno
     *
     * @return array<string, string>
     */
    public function courseNames(): array
    {
        $contract = $this->getOwnerRecord();
        if (! $contract instanceof Contract) {
            return [];
        }

        return $contract->items()
            ->with('contractCourses.course')
            ->get()
            ->flatMap(fn ($item) => $item->contractCourses)
            ->filter(fn ($cc) => $cc->course?->ouro_moderno_id !== null)
            ->mapWithKeys(fn ($cc) => [
                (string) $cc->course->ouro_moderno_id => $cc->course->name,
            ])
            ->all();
    }

    public function showTestDetails(int $index): void
    {
        $log = $this->logs[$index] ?? null;
        if (! $log) {
            return;
        }

        $rawMetadata = $log['metadata'] ?? null;
        $questions = [];

        if (is_string($rawMetadata)) {
            $decoded = json_decode($rawMetadata, true);
            if (is_array($decoded)) {
                $questions = $decoded;
            }
        } elseif (is_array($rawMetadata)) {
            $questions = $rawMetadata;
        }

        $this->selectedTest = [
            'date' => $log['data'] ?? '',
            'course' => $this->resolveCourseName($log['curso'] ?? '0'),
            'lesson' => $log['aula'] ?? '—',
            'logtxt' => $this->cleanText($log['logtxt'] ?? ''),
            'questions' => $questions,
            'total' => count($questions),
            'correct' => count(array_filter($questions, fn ($q) => (int) ($q['acertou'] ?? 0) === 1)),
        ];
    }

    public function closeTestDetails(): void
    {
        $this->selectedTest = null;
    }

    public function resolveCourseName(string|int $courseId): string
    {
        $cid = (string) $courseId;
        if ($cid === '0' || $cid === '') {
            return 'Sistema / Método Geral';
        }

        $names = $this->courseNames();

        return $names[$cid] ?? "Curso {$cid}";
    }

    public function cleanText(mixed $text): string
    {
        return html_entity_decode((string) $text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    public function formatDuration(mixed $seconds): string
    {
        if (! is_numeric($seconds) || (int) $seconds <= 0) {
            return '—';
        }

        $sec = (int) $seconds;
        if ($sec < 60) {
            return "{$sec}s";
        }

        $min = intdiv($sec, 60);
        $rem = $sec % 60;

        return $rem > 0 ? "{$min}min {$rem}s" : "{$min}min";
    }

    public function exportReport(): StreamedResponse
    {
        $user = auth()->user();
        $canExport = $user instanceof User
            && ($user->hasRole('super_admin') || $user->can('Export:OuroModernoStudentLogsReport'));

        abort_unless($canExport, 403);
        abort_if(empty($this->logs), 422, 'Consulte os logs antes de exportar.');

        $contract = $this->getOwnerRecord();
        $studentName = $contract instanceof Contract ? ($contract->student?->name ?? 'aluno') : 'aluno';
        $period = ($this->fromDate ?: 'inicio').'-a-'.($this->untilDate ?: 'hoje');
        $filename = 'logs-lms-'.str($studentName)->slug().'-'.$period.'.csv';

        return response()->streamDownload(function (): void {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, ['Data', 'Aluno', 'Curso (ID LMS)', 'Curso (Nome)', 'Aula', 'Evento / Descrição', 'Autor', 'Duração (segundos)', 'Início', 'Fim'], ';', '"', '');

            foreach ($this->logs as $log) {
                $courseId = $log['curso'] ?? '0';
                $courseName = $this->resolveCourseName($courseId);

                fputcsv($stream, [
                    $log['data'] ?? '',
                    $log['aluno'] ?? '',
                    $courseId,
                    $courseName,
                    $log['aula'] ?? '',
                    $this->cleanText($log['logtxt'] ?? ''),
                    $this->cleanText($log['autor'] ?? ''),
                    data_get($log, 'atividade.tempo', ''),
                    data_get($log, 'atividade.inicio', ''),
                    data_get($log, 'atividade.fim', ''),
                ], ';', '"', '');
            }

            fclose($stream);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render(): View
    {
        return view('filament.resources.contracts.relation-managers.ouro-moderno-logs', [
            'contract' => $this->getOwnerRecord(),
        ]);
    }
}

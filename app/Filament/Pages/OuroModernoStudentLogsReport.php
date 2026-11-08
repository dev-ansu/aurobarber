<?php

namespace App\Filament\Pages;

use App\Models\LmsStudentLessonProgress;
use App\Models\Person;
use App\Models\User;
use App\Services\OuroModernoService;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;
use UnitEnum;

class OuroModernoStudentLogsReport extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Relatórios';

    protected static ?string $navigationParentItem = 'Pedagógico';

    protected static ?string $navigationLabel = 'Atividade dos alunos no LMS';

    protected static ?string $title = 'Atividade dos alunos no LMS';

    protected static ?string $slug = 'ouro-moderno-student-logs-report';

    protected static ?int $navigationSort = 8;

    protected string $view = 'filament.pages.ouro-moderno-student-logs-report';

    public string $personId = '';

    public string $fromDate = '';

    public string $untilDate = '';

    public string $filter = '';

    public string $filterValue = '';

    public array $logs = [];

    public int $totalLogs = 0;

    public ?string $lastUpdated = null;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && ($user->hasRole('super_admin') || $user->can('View:OuroModernoStudentLogsReport'));
    }

    public function mount(): void
    {
        $this->fromDate = '';
        $this->untilDate = '';
    }

    public function students(): array
    {
        $tenantId = Filament::getTenant()?->id;

        return Person::query()
            ->whereNotNull('ouro_moderno_cod_int')
            ->when($tenantId, function ($q) use ($tenantId): void {
                $q->where(function ($sub) use ($tenantId): void {
                    $sub->whereHas('schools', fn ($sq) => $sq->where('schools.id', $tenantId))
                        ->orWhereHas('contracts', fn ($cq) => $cq->where('school_id', $tenantId));
                });
            })
            ->with(['contracts' => fn ($query) => $query->latest('start_date')])
            ->orderBy('name')
            ->get()
            ->mapWithKeys(function (Person $person): array {
                $contractNumber = $person->contracts->first()?->contract_number;
                $label = $person->name.($contractNumber ? " — contrato {$contractNumber}" : '');

                return [(string) $person->getKey() => $label];
            })
            ->all();
    }

    public function loadLogs(): void
    {
        $this->validate([
            'personId' => ['required', 'integer', 'exists:people,id'],
            'fromDate' => ['nullable', 'date_format:Y-m-d'],
            'untilDate' => ['nullable', 'date_format:Y-m-d'],
            'filter' => ['nullable', 'in:,tipo,curso,aula'],
            'filterValue' => ['nullable', 'integer', 'min:0'],
        ]);

        if (filled($this->fromDate) && filled($this->untilDate)) {
            abort_if(Carbon::parse($this->fromDate)->isAfter(Carbon::parse($this->untilDate)), 422, 'A data inicial não pode ser posterior à data final.');
            abort_if(Carbon::parse($this->fromDate)->diffInDays(Carbon::parse($this->untilDate)) > 365, 422, 'Selecione um período de até 366 dias.');
        }

        $person = Person::query()->findOrFail($this->personId);

        if (! $person->ouro_moderno_cod_int) {
            // Consulta os logs do Aurosoft LMS
            $aurosoftLogs = LmsStudentLessonProgress::query()
                ->whereHas('contract', fn ($q) => $q->where('student_id', $person->id))
                ->with(['lesson.module.course', 'contract'])
                ->orderByDesc('id')
                ->get()
                ->map(fn ($p) => [
                    'data' => $p->updated_at?->format('d/m/Y H:i') ?? '—',
                    'aluno' => $person->name,
                    'curso' => $p->lesson?->module?->course?->name ?? 'Aurosoft LMS',
                    'aula' => $p->lesson?->title ?? 'Lição #'.$p->lesson_id,
                    'evento' => match ($p->status) {
                        'completed' => 'Lição concluída (Aprovado)',
                        'failed' => 'Tentativa finalizada (Abaixo da média)',
                        'in_progress' => 'Em andamento na lição',
                        default => $p->status,
                    },
                    'autor' => 'Aurosoft LMS Player',
                    'tempo' => $p->duration_seconds ? sprintf('%02d:%02d min', intdiv($p->duration_seconds, 60), $p->duration_seconds % 60) : '—',
                    'inicio' => $p->created_at?->format('d/m/Y H:i') ?? '—',
                    'fim' => $p->completed_at?->format('d/m/Y H:i') ?? ($p->exited_at?->format('d/m/Y H:i') ?? '—'),
                    'nota' => $p->score !== null ? number_format((float) $p->score, 1) : '—',
                ])
                ->all();

            $this->logs = $aurosoftLogs;
            $this->totalLogs = count($aurosoftLogs);
            $this->lastUpdated = now()->format('d/m/Y H:i');

            Notification::make()->title('Logs do Aurosoft LMS carregados')->body("{$this->totalLogs} registro(s) de lições e atividades encontrado(s).")->success()->send();

            return;
        }

        try {
            $service = app(OuroModernoService::class);

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

            $response = $service->studentLogs((int) $person->ouro_moderno_cod_int, $filters);
            $logs = data_get($response, 'Logs.Itens', data_get($response, 'logs.items', data_get($response, 'itens', [])));
            $this->logs = is_array($logs) ? array_values(array_filter($logs, 'is_array')) : [];
            $this->totalLogs = (int) data_get($response, 'Logs.Total', data_get($response, 'logs.total', count($this->logs)));
            $this->lastUpdated = now()->format('d/m/Y H:i');

            Notification::make()->title('Logs do aluno carregados')->body("{$this->totalLogs} registro(s) encontrado(s).")->success()->send();
        } catch (Throwable $exception) {
            report($exception);
            Notification::make()->title('Não foi possível consultar os logs do LMS')->body($exception->getMessage())->danger()->persistent()->send();
        }
    }

    public function exportReport(): StreamedResponse
    {
        $user = auth()->user();
        abort_unless(
            $user instanceof User && ($user->hasRole('super_admin') || $user->can('Export:OuroModernoStudentLogsReport')),
            403
        );
        abort_if(empty($this->logs), 422, 'Consulte os logs antes de exportar.');

        return response()->streamDownload(function (): void {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, ['Data', 'Aluno', 'Curso (ID LMS)', 'Aula (ID LMS)', 'Evento', 'Autor', 'Tempo na atividade', 'Início', 'Fim'], ';', '"', '');

            foreach ($this->logs as $log) {
                fputcsv($stream, [
                    $log['data'] ?? '',
                    $log['aluno'] ?? '',
                    $log['curso'] ?? '',
                    $log['aula'] ?? '',
                    $this->cleanText($log['logtxt'] ?? ''),
                    $this->cleanText($log['autor'] ?? ''),
                    data_get($log, 'atividade.tempo', ''),
                    data_get($log, 'atividade.inicio', ''),
                    data_get($log, 'atividade.fim', ''),
                ], ';', '"', '');
            }

            fclose($stream);
        }, 'logs-lms-'.today()->toDateString().'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
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
}

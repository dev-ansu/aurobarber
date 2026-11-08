<?php

namespace App\Filament\Pages;

use App\Models\ContractCourse;
use App\Models\User;
use App\Services\AurosoftLmsProgressSyncService;
use App\Services\OuroModernoProgressSyncService;
use App\Services\OuroModernoService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class OuroModernoCourseStatusReport extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Relatórios';

    protected static ?string $navigationParentItem = 'Pedagógico';

    protected static ?string $navigationLabel = 'Progresso dos alunos no LMS';

    protected static ?string $title = 'Progresso dos alunos no LMS';

    protected static ?string $slug = 'ouro-moderno-course-status-report';

    protected static ?int $navigationSort = 6;

    protected string $view = 'filament.pages.ouro-moderno-course-status-report';

    public string $search = '';

    public string $progressStatus = '';

    public string $lmsConsultation = '';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && (
            $user->hasRole('super_admin')
            || $user->can('View:OuroModernoCourseStatusReport')
        );
    }

    public function rows()
    {
        return $this->query()->paginate(25);
    }

    public function summary(): array
    {
        $query = $this->query();

        return [
            'courses' => (clone $query)->count(),
            'checked' => (clone $query)->whereNotNull('ouro_moderno_status_checked_at')->count(),
            'not_checked' => (clone $query)->whereNull('ouro_moderno_status_checked_at')->count(),
            'completed' => (clone $query)->where('status', 'completed')->count(),
            'in_progress' => (clone $query)->where('status', 'in_progress')->count(),
            'not_started' => (clone $query)->where('status', 'not_started')->count(),
            'average' => (clone $query)->where('progress_source', 'ouro_moderno')->whereNotNull('grade')->avg('grade'),
        ];
    }

    public function exportReport(): StreamedResponse
    {
        abort_unless(auth()->user()?->can('Export:OuroModernoCourseStatusReport') ?? false, 403);

        $rows = $this->query()->get();

        return response()->streamDownload(function () use ($rows): void {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, ['Aluno', 'Contrato', 'Formação', 'Curso', 'Progresso Aurosoft', 'Situação LMS', 'Aula atual', 'Aulas concluídas', 'Total de aulas', 'Média do curso', 'Última consulta LMS', 'Situação do curso (LMS)', 'Atividades (aulas e testes)'], ';', '"', '');

            foreach ($rows as $row) {
                $feedback = $row->course?->isAurosoftLms()
                    ? app(AurosoftLmsProgressSyncService::class)->feedback($row)
                    : app(OuroModernoProgressSyncService::class)->feedback($row);

                fputcsv($stream, [
                    $row->contractItem?->contract?->student?->name,
                    $row->contractItem?->contract?->contract_number,
                    $row->contractItem?->formation?->name,
                    $row->course?->name,
                    $row->status,
                    $feedback['course_status_label'],
                    $feedback['current_lesson'],
                    $feedback['completed_lessons'],
                    $feedback['total_lessons'],
                    $feedback['average'],
                    $row->ouro_moderno_status_checked_at?->format('d/m/Y H:i'),
                    json_encode($row->ouro_moderno_status, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    json_encode([
                        'aulas' => $row->ouro_moderno_lesson_status,
                        'testes' => $row->ouro_moderno_test_status,
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ], ';', '"', '');
            }

            fclose($stream);
        }, 'situacao-cursos-lms-'.today()->toDateString().'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public string $provider = '';

    public function refreshSelectedCourse(int $contractCourseId): void
    {
        abort_unless(auth()->user()?->can('Sync:OuroModernoStudentStatus') ?? false, 403);

        $link = ContractCourse::query()
            ->with(['course.lmsModules.lessons.steps', 'contractItem.contract.student'])
            ->whereKey($contractCourseId)
            ->firstOrFail();

        if ($link->course?->isAurosoftLms()) {
            $summary = app(AurosoftLmsProgressSyncService::class)->sync($link);
            Notification::make()
                ->title('Progresso Aurosoft LMS sincronizado')
                ->body("Situação: {$summary['status']}. Média: ".number_format((float) ($summary['grade'] ?? 0), 1))
                ->success()
                ->send();

            return;
        }

        $integrationCode = $link->contractItem?->contract?->student?->ouro_moderno_cod_int;

        if (! $integrationCode) {
            Notification::make()->title('Aluno sem código de integração no LMS')->warning()->send();

            return;
        }

        $service = app(OuroModernoService::class);
        $summary = app(OuroModernoProgressSyncService::class)->sync($link, (int) $integrationCode, $service);

        $average = $summary['average'] !== null ? ' Média calculada: '.number_format($summary['average'], 2, ',', '').'.' : '';
        Notification::make()
            ->title('Progresso importado para o Aurosoft')
            ->body('Situação: '.$summary['course_status_label'].'.'.$average.' A conclusão do contrato continua manual.')
            ->success()
            ->send();
    }

    private function query(): Builder
    {
        return ContractCourse::query()
            ->whereHas('contractItem.contract')
            ->with(['course.lmsModules.lessons.steps', 'contractItem.formation', 'contractItem.contract.student'])
            ->when($this->provider === 'aurosoft', fn (Builder $query) => $query->whereHas('course', fn ($q) => $q->whereNull('ouro_moderno_id')))
            ->when($this->provider === 'ouro_moderno', fn (Builder $query) => $query->whereHas('course', fn ($q) => $q->whereNotNull('ouro_moderno_id')))
            ->when(empty($this->provider), fn (Builder $query) => $query->whereHas('course'))
            ->when(filled($this->search), function (Builder $query): void {
                $term = '%'.addcslashes(trim($this->search), '%_\\').'%';
                $query->where(function (Builder $query) use ($term): void {
                    $query->whereHas('course', fn (Builder $course) => $course->where('name', 'like', $term))
                        ->orWhereHas('contractItem.contract', fn (Builder $contract) => $contract
                            ->where('contract_number', 'like', $term)
                            ->orWhereHas('student', fn (Builder $student) => $student->where('name', 'like', $term)));
                });
            })
            ->when(filled($this->progressStatus), fn (Builder $query) => $query->where('status', $this->progressStatus))
            ->when($this->lmsConsultation === 'checked', fn (Builder $query) => $query->whereNotNull('ouro_moderno_status_checked_at'))
            ->when($this->lmsConsultation === 'pending', fn (Builder $query) => $query->whereNull('ouro_moderno_status_checked_at'))
            ->latest('id');
    }
}

<?php

namespace App\Filament\Pages;

use App\Models\Contract;
use App\Models\ContractCourse;
use App\Models\ContractItem;
use App\Models\Course;
use App\Models\Formation;
use App\Services\FinancialCalculationService;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class StudentsByCourseAndFormationReport extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Relatórios';

    protected static ?string $navigationParentItem = 'Pedagógico';

    protected static ?string $navigationLabel = 'Alunos por curso e formação';

    protected static ?string $title = 'Relatório de Alunos por Curso e Formação';

    protected static ?string $slug = 'students-by-course-and-formation-report';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.students-by-course-and-formation-report';

    public string $activeTab = 'formations'; // 'formations' | 'courses'

    public string $search = '';

    public string $statusFilter = 'all'; // 'all', 'active', 'concluded', 'suspended', 'canceled'

    public ?string $fromDate = null;

    public ?string $untilDate = null;

    public ?int $viewingFormationId = null;

    public ?int $viewingCourseId = null;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return true;
        }

        return $user->getAllPermissions()->contains('name', 'View:StudentsByCourseAndFormationReport')
            || $user->getAllPermissions()->contains('name', 'View:Contract')
            || $user->getAllPermissions()->contains('name', 'View:CompletionReports');
    }

    public function setTab(string $tab): void
    {
        if (in_array($tab, ['formations', 'courses'], true)) {
            $this->activeTab = $tab;
            $this->closeDetailsModal();
        }
    }

    public function openFormationDetails(int $formationId): void
    {
        $this->viewingFormationId = $formationId;
        $this->viewingCourseId = null;
    }

    public function openCourseDetails(int $courseId): void
    {
        $this->viewingCourseId = $courseId;
        $this->viewingFormationId = null;
    }

    public function closeDetailsModal(): void
    {
        $this->viewingFormationId = null;
        $this->viewingCourseId = null;
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->statusFilter = 'all';
        $this->fromDate = null;
        $this->untilDate = null;
        $this->closeDetailsModal();
    }

    public function getFormationStats(): array
    {
        $formations = $this->getFormationsData();

        $topFormation = $formations[0] ?? null;
        $totalContracts = array_sum(array_column($formations, 'total_contracts'));
        $totalActive = array_sum(array_column($formations, 'active_count'));
        $totalRevenue = array_sum(array_column($formations, 'total_revenue'));

        return [
            'top_formation_name' => $topFormation ? $topFormation['name'] : 'Nenhuma cadastrada',
            'top_formation_count' => $topFormation ? $topFormation['total_contracts'] : 0,
            'total_enrollments' => $totalContracts,
            'total_active' => $totalActive,
            'total_revenue' => $totalRevenue,
        ];
    }

    public function getCourseStats(): array
    {
        $courses = $this->getCoursesData();

        $topCourse = $courses[0] ?? null;
        $totalEnrollments = array_sum(array_column($courses, 'total_enrollments'));
        $totalInProgress = array_sum(array_column($courses, 'in_progress_count'));
        $totalCompleted = array_sum(array_column($courses, 'completed_count'));
        $globalCompletionRate = $totalEnrollments > 0
            ? round(($totalCompleted / $totalEnrollments) * 100, 1)
            : 0.0;

        return [
            'top_course_name' => $topCourse ? $topCourse['name'] : 'Nenhum cadastrado',
            'top_course_count' => $topCourse ? $topCourse['total_enrollments'] : 0,
            'total_enrollments' => $totalEnrollments,
            'total_in_progress' => $totalInProgress,
            'total_completed' => $totalCompleted,
            'global_completion_rate' => $globalCompletionRate,
        ];
    }

    public function getFormationsData(): array
    {
        $tenantId = Filament::getTenant()?->id;
        $search = mb_strtolower(trim($this->search));

        return Formation::query()
            ->with(['courses'])
            ->when($search !== '', fn ($q) => $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]))
            ->get()
            ->map(function (Formation $formation) use ($tenantId): array {
                $items = ContractItem::query()
                    ->where('formation_id', $formation->id)
                    ->whereHas('contract', fn ($q) => $q->withoutGlobalScopes()
                        ->when($tenantId, fn ($sq) => $sq->where('school_id', $tenantId))
                        ->when($this->statusFilter && $this->statusFilter !== 'all', fn ($sq) => $sq->where('status', $this->statusFilter))
                        ->when($this->fromDate, fn ($sq) => $sq->whereDate('start_date', '>=', $this->fromDate))
                        ->when($this->untilDate, fn ($sq) => $sq->whereDate('start_date', '<=', $this->untilDate))
                    )
                    ->with(['contract'])
                    ->get();

                $totalStudents = $items->pluck('contract.student_id')->unique()->filter()->count();
                $totalContracts = $items->count();

                $active = $items->filter(fn ($it) => $it->contract?->status === 'active')->count();
                $concluded = $items->filter(fn ($it) => $it->contract?->status === 'concluded' || $it->formation_completed_at !== null)->count();
                $suspended = $items->filter(fn ($it) => $it->contract?->status === 'suspended')->count();
                $canceled = $items->filter(fn ($it) => $it->contract?->status === 'canceled')->count();
                $totalRevenue = (float) $items->sum(fn ($it) => (float) ($it->contract?->total_value ?? 0));
                $workload = (int) $formation->courses->sum('workload');

                return [
                    'id' => $formation->id,
                    'name' => $formation->name,
                    'description' => $formation->description,
                    'courses_count' => $formation->courses->count(),
                    'workload' => $workload,
                    'total_students' => $totalStudents,
                    'total_contracts' => $totalContracts,
                    'active_count' => $active,
                    'concluded_count' => $concluded,
                    'suspended_count' => $suspended,
                    'canceled_count' => $canceled,
                    'total_revenue' => $totalRevenue,
                ];
            })
            ->sortByDesc('total_contracts')
            ->values()
            ->map(function (array $item, int $index): array {
                $item['rank'] = $index + 1;

                return $item;
            })
            ->toArray();
    }

    public function getCoursesData(): array
    {
        $tenantId = Filament::getTenant()?->id;
        $search = mb_strtolower(trim($this->search));

        return Course::query()
            ->when($search !== '', fn ($q) => $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]))
            ->get()
            ->map(function (Course $course) use ($tenantId): array {
                $contractCourses = ContractCourse::query()
                    ->where('course_id', $course->id)
                    ->whereHas('contractItem.contract', fn ($q) => $q->withoutGlobalScopes()
                        ->when($tenantId, fn ($sq) => $sq->where('school_id', $tenantId))
                        ->when($this->statusFilter && $this->statusFilter !== 'all', fn ($sq) => $sq->where('status', $this->statusFilter))
                        ->when($this->fromDate, fn ($sq) => $sq->whereDate('start_date', '>=', $this->fromDate))
                        ->when($this->untilDate, fn ($sq) => $sq->whereDate('start_date', '<=', $this->untilDate))
                    )
                    ->with(['contractItem.contract'])
                    ->get();

                $totalStudents = $contractCourses->pluck('contractItem.contract.student_id')->unique()->filter()->count();
                $totalEnrollments = $contractCourses->count();

                $inProgress = $contractCourses->where('status', 'in_progress')->count();
                $completed = $contractCourses->where('status', 'completed')->count();
                $notStarted = $contractCourses->where('status', 'not_started')->count();

                $completionRate = $totalEnrollments > 0
                    ? round(($completed / $totalEnrollments) * 100, 1)
                    : 0.0;

                $graded = $contractCourses->whereNotNull('grade')->filter(fn ($c) => (float) $c->grade > 0);
                $averageGrade = $graded->isNotEmpty() ? round((float) $graded->avg('grade'), 1) : null;

                return [
                    'id' => $course->id,
                    'name' => $course->name,
                    'workload' => (int) $course->workload,
                    'is_ouro_moderno' => $course->ouro_moderno_id !== null,
                    'total_students' => $totalStudents,
                    'total_enrollments' => $totalEnrollments,
                    'in_progress_count' => $inProgress,
                    'completed_count' => $completed,
                    'not_started_count' => $notStarted,
                    'completion_rate' => $completionRate,
                    'average_grade' => $averageGrade,
                ];
            })
            ->sortByDesc('total_enrollments')
            ->values()
            ->map(function (array $item, int $index): array {
                $item['rank'] = $index + 1;

                return $item;
            })
            ->toArray();
    }

    public function getViewingFormation(): ?array
    {
        if (! $this->viewingFormationId) {
            return null;
        }

        $formation = Formation::find($this->viewingFormationId);

        return $formation ? [
            'id' => $formation->id,
            'name' => $formation->name,
            'students' => $this->getSelectedFormationStudents(),
        ] : null;
    }

    public function getViewingCourse(): ?array
    {
        if (! $this->viewingCourseId) {
            return null;
        }

        $course = Course::find($this->viewingCourseId);

        return $course ? [
            'id' => $course->id,
            'name' => $course->name,
            'students' => $this->getSelectedCourseStudents(),
        ] : null;
    }

    public function getSelectedFormationStudents(): array
    {
        if (! $this->viewingFormationId) {
            return [];
        }

        $tenantId = Filament::getTenant()?->id;
        $calcService = app(FinancialCalculationService::class);

        return ContractItem::query()
            ->where('formation_id', $this->viewingFormationId)
            ->whereHas('contract', fn ($q) => $q->withoutGlobalScopes()
                ->when($tenantId, fn ($sq) => $sq->where('school_id', $tenantId))
                ->when($this->statusFilter && $this->statusFilter !== 'all', fn ($sq) => $sq->where('status', $this->statusFilter))
                ->when($this->fromDate, fn ($sq) => $sq->whereDate('start_date', '>=', $this->fromDate))
                ->when($this->untilDate, fn ($sq) => $sq->whereDate('start_date', '<=', $this->untilDate))
            )
            ->with(['contract.student.contacts', 'formation'])
            ->get()
            ->map(function (ContractItem $item) use ($calcService): array {
                $contract = $item->contract;
                $student = $contract?->student;
                $phone = $contract ? $calcService->resolveContractContactPhone($contract) : null;
                $phoneDigits = $phone ? preg_replace('/\D+/', '', $phone) : null;

                return [
                    'contract_id' => $contract?->id,
                    'contract_number' => $contract?->contract_number ?? '—',
                    'student_name' => $student?->name ?? 'Aluno não identificado',
                    'student_cpf' => $student?->cpf ?? '—',
                    'status' => $contract?->status ?? '—',
                    'status_label' => match ($contract?->status) {
                        'active' => 'Ativo',
                        'concluded' => 'Concluído',
                        'suspended' => 'Trancado',
                        'canceled' => 'Cancelado',
                        'draft' => 'Rascunho',
                        default => $contract?->status ?? '—',
                    },
                    'start_date' => $contract?->start_date ? Carbon::parse($contract->start_date)->format('d/m/Y') : '—',
                    'end_date' => $contract?->end_date ? Carbon::parse($contract->end_date)->format('d/m/Y') : '—',
                    'formation_completed_at' => $item->formation_completed_at ? $item->formation_completed_at->format('d/m/Y') : null,
                    'phone' => $phone,
                    'whatsapp_url' => $phoneDigits ? "https://wa.me/55{$phoneDigits}" : null,
                ];
            })
            ->toArray();
    }

    public function getSelectedCourseStudents(): array
    {
        if (! $this->viewingCourseId) {
            return [];
        }

        $tenantId = Filament::getTenant()?->id;
        $calcService = app(FinancialCalculationService::class);

        return ContractCourse::query()
            ->where('course_id', $this->viewingCourseId)
            ->whereHas('contractItem.contract', fn ($q) => $q->withoutGlobalScopes()
                ->when($tenantId, fn ($sq) => $sq->where('school_id', $tenantId))
                ->when($this->statusFilter && $this->statusFilter !== 'all', fn ($sq) => $sq->where('status', $this->statusFilter))
                ->when($this->fromDate, fn ($sq) => $sq->whereDate('start_date', '>=', $this->fromDate))
                ->when($this->untilDate, fn ($sq) => $sq->whereDate('start_date', '<=', $this->untilDate))
            )
            ->with(['contractItem.contract.student.contacts', 'contractItem.formation', 'course'])
            ->get()
            ->map(function (ContractCourse $contractCourse) use ($calcService): array {
                $contract = $contractCourse->contractItem?->contract;
                $student = $contract?->student;
                $formation = $contractCourse->contractItem?->formation;
                $phone = $contract ? $calcService->resolveContractContactPhone($contract) : null;
                $phoneDigits = $phone ? preg_replace('/\D+/', '', $phone) : null;

                return [
                    'contract_id' => $contract?->id,
                    'contract_number' => $contract?->contract_number ?? '—',
                    'student_name' => $student?->name ?? 'Aluno não identificado',
                    'formation_name' => $formation?->name ?? '—',
                    'status' => $contractCourse->status,
                    'status_label' => match ($contractCourse->status) {
                        'in_progress' => 'Cursando',
                        'completed' => 'Concluído',
                        'not_started' => 'Não iniciado',
                        default => $contractCourse->status,
                    },
                    'grade' => $contractCourse->grade !== null ? number_format((float) $contractCourse->grade, 1, ',', '.') : '—',
                    'completed_at' => $contractCourse->completed_at ? $contractCourse->completed_at->format('d/m/Y') : '—',
                    'phone' => $phone,
                    'whatsapp_url' => $phoneDigits ? "https://wa.me/55{$phoneDigits}" : null,
                ];
            })
            ->toArray();
    }

    public function exportFormationsCsv(): StreamedResponse
    {
        $formations = $this->getFormationsData();
        $date = now()->format('Y-m-d');
        $filename = "relatorio-alunos-por-formacao-{$date}.csv";
        $tenantId = Filament::getTenant()?->id;
        $calcService = app(FinancialCalculationService::class);

        return response()->streamDownload(function () use ($formations, $tenantId, $calcService): void {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");

            fputcsv($stream, ['RELATÓRIO DE ALUNOS POR FORMAÇÃO', now()->format('d/m/Y H:i')], ';', '"', '');
            fputcsv($stream, [], ';', '"', '');
            fputcsv($stream, [
                'Posição', 'Formação', 'Carga Horária', 'Total Matrículas',
                'Alunos Ativos', 'Concluídos', 'Trancados', 'Cancelados', 'Faturamento Gerado (R$)',
            ], ';', '"', '');

            foreach ($formations as $f) {
                fputcsv($stream, [
                    $f['rank'].'º',
                    $f['name'],
                    $f['workload'].'h',
                    $f['total_contracts'],
                    $f['active_count'],
                    $f['concluded_count'],
                    $f['suspended_count'],
                    $f['canceled_count'],
                    number_format($f['total_revenue'], 2, ',', '.'),
                ], ';', '"', '');
            }

            fputcsv($stream, [], ';', '"', '');
            fputcsv($stream, ['DETALHAMENTO DE ALUNOS MATRICULADOS POR FORMAÇÃO'], ';', '"', '');
            fputcsv($stream, [
                'Formação', 'Aluno', 'CPF', 'Contrato', 'Status do Contrato', 'Data Início', 'Data Término', 'Conclusão da Formação', 'Telefone',
            ], ';', '"', '');

            foreach ($formations as $f) {
                $items = ContractItem::query()
                    ->where('formation_id', $f['id'])
                    ->whereHas('contract', fn ($q) => $q->withoutGlobalScopes()
                        ->when($tenantId, fn ($sq) => $sq->where('school_id', $tenantId))
                        ->when($this->statusFilter && $this->statusFilter !== 'all', fn ($sq) => $sq->where('status', $this->statusFilter))
                        ->when($this->fromDate, fn ($sq) => $sq->whereDate('start_date', '>=', $this->fromDate))
                        ->when($this->untilDate, fn ($sq) => $sq->whereDate('start_date', '<=', $this->untilDate))
                    )
                    ->with(['contract.student.contacts'])
                    ->get();

                foreach ($items as $it) {
                    $c = $it->contract;
                    $phone = $c ? $calcService->resolveContractContactPhone($c) : '';
                    fputcsv($stream, [
                        $f['name'],
                        $c?->student?->name ?? '—',
                        $c?->student?->cpf ?? '—',
                        $c?->contract_number ?? '—',
                        $c?->status ?? '—',
                        $c?->start_date ? Carbon::parse($c->start_date)->format('d/m/Y') : '—',
                        $c?->end_date ? Carbon::parse($c->end_date)->format('d/m/Y') : '—',
                        $it->formation_completed_at ? $it->formation_completed_at->format('d/m/Y') : '—',
                        $phone ?? '—',
                    ], ';', '"', '');
                }
            }

            fclose($stream);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportCoursesCsv(): StreamedResponse
    {
        $courses = $this->getCoursesData();
        $date = now()->format('Y-m-d');
        $filename = "relatorio-alunos-por-curso-{$date}.csv";
        $tenantId = Filament::getTenant()?->id;
        $calcService = app(FinancialCalculationService::class);

        return response()->streamDownload(function () use ($courses, $tenantId, $calcService): void {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");

            fputcsv($stream, ['RELATÓRIO DE ALUNOS POR CURSO / MÓDULO', now()->format('d/m/Y H:i')], ';', '"', '');
            fputcsv($stream, [], ';', '"', '');
            fputcsv($stream, [
                'Posição', 'Curso', 'Carga Horária', 'Total Inscrições',
                'Cursando', 'Concluídos', 'A Iniciar', 'Taxa de Conclusão (%)', 'Média de Notas',
            ], ';', '"', '');

            foreach ($courses as $c) {
                fputcsv($stream, [
                    $c['rank'].'º',
                    $c['name'],
                    $c['workload'].'h',
                    $c['total_enrollments'],
                    $c['in_progress_count'],
                    $c['completed_count'],
                    $c['not_started_count'],
                    $c['completion_rate'].'%',
                    $c['average_grade'] !== null ? number_format($c['average_grade'], 1, ',', '.') : '—',
                ], ';', '"', '');
            }

            fputcsv($stream, [], ';', '"', '');
            fputcsv($stream, ['DETALHAMENTO DE ALUNOS POR CURSO'], ';', '"', '');
            fputcsv($stream, [
                'Curso', 'Aluno', 'Formação', 'Contrato', 'Situação no Curso', 'Nota', 'Data Conclusão', 'Telefone',
            ], ';', '"', '');

            foreach ($courses as $c) {
                $contractCourses = ContractCourse::query()
                    ->where('course_id', $c['id'])
                    ->whereHas('contractItem.contract', fn ($q) => $q->withoutGlobalScopes()
                        ->when($tenantId, fn ($sq) => $sq->where('school_id', $tenantId))
                        ->when($this->statusFilter && $this->statusFilter !== 'all', fn ($sq) => $sq->where('status', $this->statusFilter))
                        ->when($this->fromDate, fn ($sq) => $sq->whereDate('start_date', '>=', $this->fromDate))
                        ->when($this->untilDate, fn ($sq) => $sq->whereDate('start_date', '<=', $this->untilDate))
                    )
                    ->with(['contractItem.contract.student.contacts', 'contractItem.formation'])
                    ->get();

                foreach ($contractCourses as $cc) {
                    $contract = $cc->contractItem?->contract;
                    $phone = $contract ? $calcService->resolveContractContactPhone($contract) : '';
                    fputcsv($stream, [
                        $c['name'],
                        $contract?->student?->name ?? '—',
                        $cc->contractItem?->formation?->name ?? '—',
                        $contract?->contract_number ?? '—',
                        $cc->status,
                        $cc->grade !== null ? number_format((float) $cc->grade, 1, ',', '.') : '—',
                        $cc->completed_at ? $cc->completed_at->format('d/m/Y') : '—',
                        $phone ?? '—',
                    ], ';', '"', '');
                }
            }

            fclose($stream);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}

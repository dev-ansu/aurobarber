<?php

namespace App\Filament\Pages;

use App\Filament\Exports\CompletionReportExporter;
use App\Models\Contract;
use App\Models\ContractCourseProgressHistory;
use App\Models\ContractItemCompletionHistory;
use App\Models\ContractStatusHistory;
use App\Models\Course;
use App\Models\Formation;
use App\Models\User;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\Exports\Models\Export;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL as URLFacade;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

class CompletionReports extends Page
{
    use WithPagination;

    protected static ?string $title = 'Relatório de Conclusão de Alunos (Formandos e Certificados)';

    protected static ?string $navigationLabel = 'Conclusão de alunos';

    protected static string|\UnitEnum|null $navigationGroup = 'Relatórios';

    protected static ?string $navigationParentItem = 'Pedagógico';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.completion-reports';

    #[Url]
    public string $periodStart = '';

    #[Url]
    public string $periodEnd = '';

    #[Url]
    public int $nearCompletionDays = 30;

    #[Url]
    public string $search = '';

    #[Url]
    public string $formationId = '';

    #[Url]
    public string $courseId = '';

    #[Url]
    public string $nearCompletionStatus = 'all';

    public function mount(): void
    {
        $this->periodStart = $this->periodStart ?: now()->startOfMonth()->toDateString();
        $this->periodEnd = $this->periodEnd ?: now()->endOfMonth()->toDateString();
        $this->nearCompletionDays = in_array($this->nearCompletionDays, [7, 14, 30, 60, 90], true) ? $this->nearCompletionDays : 30;
        $this->nearCompletionStatus = in_array($this->nearCompletionStatus, ['all', 'upcoming', 'overdue'], true) ? $this->nearCompletionStatus : 'all';
        $this->search = mb_substr($this->search, 0, 100);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['periodStart', 'periodEnd'], true) && $this->periodStart !== '' && $this->periodEnd !== '') {
            $this->validate([
                'periodStart' => ['required', 'date_format:Y-m-d'],
                'periodEnd' => ['required', 'date_format:Y-m-d', 'after_or_equal:periodStart'],
            ]);
        }

        if (in_array($property, ['periodStart', 'periodEnd'], true)) {
            foreach (['contractCompletionPage', 'formationCompletionPage', 'courseCompletionPage'] as $pageName) {
                $this->resetPage($pageName);
            }
        }

        if ($property === 'nearCompletionDays') {
            $this->validate(['nearCompletionDays' => ['required', 'integer', 'in:7,14,30,60,90']]);
            $this->resetPage('nearCompletionPage');
        }

        if ($property === 'nearCompletionStatus') {
            $this->validate(['nearCompletionStatus' => ['required', 'in:all,upcoming,overdue']]);
        }

        if (in_array($property, ['search', 'formationId', 'courseId', 'nearCompletionStatus'], true)) {
            foreach (['contractCompletionPage', 'formationCompletionPage', 'courseCompletionPage', 'nearCompletionPage'] as $pageName) {
                $this->resetPage($pageName);
            }
        }
    }

    #[Computed]
    public function completedContracts(): LengthAwarePaginator
    {
        return $this->paginateQuery($this->completedContractsQuery(), 'contractCompletionPage');
    }

    #[Computed]
    public function completedFormations(): LengthAwarePaginator
    {
        return $this->paginateQuery($this->completedFormationsQuery(), 'formationCompletionPage');
    }

    #[Computed]
    public function completedCourses(): LengthAwarePaginator
    {
        return $this->paginateQuery($this->completedCoursesQuery(), 'courseCompletionPage');
    }

    #[Computed]
    public function studentsNearCompletion(): LengthAwarePaginator
    {
        return $this->paginateQuery($this->nearCompletionQuery(), 'nearCompletionPage');
    }

    #[Computed]
    public function completedStudentCounts(): array
    {
        if (! $this->hasValidPeriod()) {
            return ['contracts' => 0, 'formations' => 0, 'courses' => 0, 'previous' => []];
        }

        [$start, $end] = $this->periodBounds();
        $duration = (int) $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) + 1;
        $previousEnd = $start->copy()->startOfDay()->subSecond();
        $previousStart = $previousEnd->copy()->subDays($duration)->startOfDay();

        return [
            'contracts' => $this->distinctStudentsWithCompletedContracts($start, $end),
            'formations' => $this->distinctStudentsWithCompletedFormations($start, $end),
            'courses' => $this->distinctStudentsWithCompletedCourses($start, $end),
            'previous' => [
                'contracts' => $this->distinctStudentsWithCompletedContracts($previousStart, $previousEnd),
                'formations' => $this->distinctStudentsWithCompletedFormations($previousStart, $previousEnd),
                'courses' => $this->distinctStudentsWithCompletedCourses($previousStart, $previousEnd),
            ],
        ];
    }

    #[Computed]
    public function filterOptions(): array
    {
        return [
            'formations' => Formation::query()->orderBy('name')->pluck('name', 'id')->all(),
            'courses' => Course::query()->orderBy('name')->pluck('name', 'id')->all(),
        ];
    }

    public function downloadReport(string $type, string $format = 'csv')
    {
        abort_unless($this->canExport(), 403);
        abort_unless(in_array($type, ['contracts', 'formations', 'courses', 'near-completion'], true), 404);
        abort_unless(in_array($format, ['csv', 'xlsx'], true), 404);
        abort_unless($type === 'near-completion' || $this->hasValidPeriod(), 422);

        $reports = [
            'contracts' => [
                'filename' => 'contratos-concluidos',
                'headers' => ['Data de conclusão', 'Aluno', 'Contrato', 'Observação'],
                'query' => $this->completedContractsQuery(),
                'row' => fn (ContractStatusHistory $history): array => [
                    $history->created_at?->format('d/m/Y'),
                    $history->contract?->student?->name ?? 'Aluno removido',
                    $history->contract?->contract_number ?? '',
                    $history->reason ?? '',
                ],
            ],
            'formations' => [
                'filename' => 'formacoes-concluidas',
                'headers' => ['Data de conclusão', 'Aluno', 'Contrato', 'Formação'],
                'query' => $this->completedFormationsQuery(),
                'row' => fn (ContractItemCompletionHistory $history): array => [
                    $history->completed_at?->format('d/m/Y'),
                    $history->contractItem?->contract?->student?->name ?? 'Aluno removido',
                    $history->contractItem?->contract?->contract_number ?? '',
                    $history->contractItem?->formation?->name ?? 'Formação removida',
                ],
            ],
            'courses' => [
                'filename' => 'cursos-concluidos',
                'headers' => ['Data de conclusão', 'Aluno', 'Contrato', 'Formação', 'Curso', 'Nota', 'Origem'],
                'query' => $this->completedCoursesQuery(),
                'row' => fn (ContractCourseProgressHistory $history): array => [
                    $history->completed_at?->format('d/m/Y'),
                    $history->contractCourse?->contractItem?->contract?->student?->name ?? 'Aluno removido',
                    $history->contractCourse?->contractItem?->contract?->contract_number ?? '',
                    $history->contractCourse?->contractItem?->formation?->name ?? '',
                    $history->contractCourse?->course?->name ?? 'Curso removido',
                    $history->grade ?? '',
                    match ($history->source) {
                        'ouro_moderno' => 'Ouro Moderno',
                        'aurosoft' => 'Aurosoft LMS',
                        default => 'Manual',
                    },
                ],
            ],
            'near-completion' => [
                'filename' => "conclusao-prevista-proximos-{$this->nearCompletionDays}-dias",
                'headers' => ['Previsão', 'Situação', 'Dias', 'Aluno', 'Contrato', 'Progresso das formações'],
                'query' => $this->nearCompletionQuery(),
                'row' => fn (Contract $contract): array => [
                    $contract->end_date?->format('d/m/Y'),
                    $contract->end_date?->isBefore(today()) ? 'Atrasado' : 'Próximo',
                    $contract->end_date?->isBefore(today()) ? $contract->end_date->diffInDays(today()) : '',
                    $contract->student?->name ?? 'Aluno removido',
                    $contract->contract_number,
                    $this->formationProgress($contract),
                ],
            ],
        ];

        $report = $reports[$type];
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        $export = new Export;
        $export->user()->associate($user);
        $export->exporter = CompletionReportExporter::class;
        $export->total_rows = (clone $report['query'])->count();
        $export->file_disk = (new CompletionReportExporter($export, [], []))->getFileDisk();
        $export->save();
        $export->file_name = $report['filename'].'-'.$export->getKey();
        $export->save();
        $export->deleteFileDirectory();

        $disk = Storage::disk($export->file_disk);
        $directory = $export->getFileDirectory();
        $disk->makeDirectory($directory);

        $headers = fopen('php://temp', 'w+b');
        fputcsv($headers, $report['headers'], ';', '"', '\\');
        rewind($headers);
        $disk->writeStream($directory.'/headers.csv', $headers);
        fclose($headers);

        $successfulRows = 0;
        $chunkNumber = 0;
        $report['query']->chunkById(500, function ($records) use ($disk, $directory, $report, &$successfulRows, &$chunkNumber): void {
            $stream = fopen('php://temp', 'w+b');

            foreach ($records as $record) {
                fputcsv($stream, array_map([$this, 'safeCsvValue'], ($report['row'])($record)), ';', '"', '\\');
                $successfulRows++;
            }

            rewind($stream);
            $chunkPath = $directory.'/part-'.str_pad((string) $chunkNumber++, 6, '0', STR_PAD_LEFT).'.csv';
            $disk->writeStream($chunkPath, $stream);
            fclose($stream);
        });

        $export->forceFill([
            'processed_rows' => $successfulRows,
            'successful_rows' => $successfulRows,
            'completed_at' => now(),
        ])->save();

        if (! $user->getAllPermissions()->contains('name', 'Download:Export')) {
            Notification::make()
                ->title('Exportação salva')
                ->body('O arquivo está no histórico. É necessária a permissão de download para baixá-lo.')
                ->success()
                ->send();

            return redirect()->back();
        }

        return redirect()->to(URLFacade::signedRoute('filament.exports.download', [
            'authGuard' => filament()->getAuthGuard(),
            'export' => $export,
            'format' => $format === 'xlsx' ? ExportFormat::Xlsx : ExportFormat::Csv,
        ], absolute: false));
    }

    public function canExport(): bool
    {
        return Auth::user()?->getAllPermissions()->contains('name', 'Export:CompletionReports') ?? false;
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->getAllPermissions()->contains('name', 'View:CompletionReports');
    }

    public function formationProgress(Contract $contract): string
    {
        $total = $contract->items->count();
        $completed = $contract->items->filter(fn ($item): bool => $item->formation_completed_at !== null)->count();

        if ($total === 0) {
            return 'Sem formações';
        }

        $percentage = (int) round($completed / $total * 100);

        return "{$completed}/{$total} ({$percentage}%)";
    }

    private function completedContractsQuery(): Builder
    {
        if (! $this->hasValidPeriod()) {
            return ContractStatusHistory::query()->whereRaw('1 = 0');
        }

        $query = ContractStatusHistory::query()
            ->with(['contract.student'])
            ->where('to_status', 'finished')
            ->whereBetween('created_at', $this->periodBounds())
            ->latest('id');

        return $query->whereHas('contract', fn (Builder $contractQuery): Builder => $this->applyContractFilters($contractQuery));
    }

    private function completedFormationsQuery(): Builder
    {
        if (! $this->hasValidPeriod()) {
            return ContractItemCompletionHistory::query()->whereRaw('1 = 0');
        }

        $query = ContractItemCompletionHistory::query()
            ->with(['contractItem.formation', 'contractItem.contract.student'])
            ->whereNotNull('completed_at')
            ->whereBetween('completed_at', $this->periodBounds())
            ->latest('id');

        return $query->whereHas('contractItem', function (Builder $itemQuery): Builder {
            $itemQuery->withTrashed();
            $itemQuery->when($this->formationId !== '', fn (Builder $q): Builder => $q->where('formation_id', $this->formationId));
            $itemQuery->when($this->courseId !== '', fn (Builder $q): Builder => $q->whereHas('contractCourses', fn (Builder $courseQuery): Builder => $courseQuery->withTrashed()->where('course_id', $this->courseId)));
            $itemQuery->whereHas('contract', fn (Builder $contractQuery): Builder => $this->applyContractFilters($contractQuery));

            return $itemQuery;
        });
    }

    private function completedCoursesQuery(): Builder
    {
        if (! $this->hasValidPeriod()) {
            return ContractCourseProgressHistory::query()->whereRaw('1 = 0');
        }

        $query = ContractCourseProgressHistory::query()
            ->with(['contractCourse.course', 'contractCourse.contractItem.formation', 'contractCourse.contractItem.contract.student'])
            ->where('status', 'completed')
            ->whereNotNull('completed_at')
            ->whereBetween('completed_at', $this->periodBounds())
            ->latest('id');

        return $query->whereHas('contractCourse', function (Builder $courseQuery): Builder {
            $courseQuery->withTrashed()
                ->where('status', 'completed')
                ->when($this->courseId !== '', fn (Builder $q): Builder => $q->where('course_id', $this->courseId))
                ->whereHas('contractItem', function (Builder $itemQuery): Builder {
                    $itemQuery->withTrashed()
                        ->when($this->formationId !== '', fn (Builder $q): Builder => $q->where('formation_id', $this->formationId))
                        ->whereHas('contract', fn (Builder $contractQuery): Builder => $this->applyContractFilters($contractQuery));

                    return $itemQuery;
                });

            return $courseQuery;
        });
    }

    private function nearCompletionQuery(): Builder
    {
        $query = Contract::query()
            ->with(['student', 'items' => fn ($itemQuery) => $itemQuery->withTrashed()->with('formation')])
            ->where('status', 'active')
            ->whereNotNull('end_date')
            ->whereDate('end_date', '<=', now()->addDays($this->nearCompletionDays)->toDateString());

        if ($this->nearCompletionStatus === 'upcoming') {
            $query->whereDate('end_date', '>=', today()->toDateString());
        } elseif ($this->nearCompletionStatus === 'overdue') {
            $query->whereDate('end_date', '<', today()->toDateString());
        }

        return $this->applyContractFilters($query)->orderBy('end_date');
    }

    private function applyContractFilters(Builder $query): Builder
    {
        $tenantId = Filament::getTenant()?->id;
        if ($tenantId) {
            $query->where($query->getModel()->qualifyColumn('school_id'), $tenantId);
        }

        $query->when($this->search !== '', function (Builder $query): Builder {
            $term = '%'.trim($this->search).'%';

            return $query->where(fn (Builder $nested): Builder => $nested
                ->where('contract_number', 'like', $term)
                ->orWhereHas('student', fn (Builder $student): Builder => $student->where('name', 'like', $term)));
        });

        $query->when($this->formationId !== '', fn (Builder $query): Builder => $query->whereHas('items', fn (Builder $items): Builder => $items->withTrashed()->where('formation_id', $this->formationId)));
        $query->when($this->courseId !== '', fn (Builder $query): Builder => $query->whereHas('items', fn (Builder $items): Builder => $items->withTrashed()->whereHas('contractCourses', fn (Builder $courses): Builder => $courses->withTrashed()->where('course_id', $this->courseId))));

        return $query;
    }

    private function paginateQuery(Builder $query, string $pageName): LengthAwarePaginator
    {
        return $query->paginate(15, ['*'], $pageName);
    }

    private function distinctStudentsWithCompletedContracts(Carbon $start, Carbon $end): int
    {
        return $this->applyContractFilters(Contract::query())
            ->whereHas('statusHistories', fn (Builder $query): Builder => $query
                ->where('to_status', 'finished')->whereBetween('created_at', [$start, $end]))
            ->distinct('student_id')->count('student_id');
    }

    private function distinctStudentsWithCompletedFormations(Carbon $start, Carbon $end): int
    {
        return $this->applyContractFilters(Contract::query())
            ->whereHas('items', fn (Builder $items): Builder => $items->withTrashed()
                ->when($this->formationId !== '', fn (Builder $items): Builder => $items->where('formation_id', $this->formationId))
                ->when($this->courseId !== '', fn (Builder $items): Builder => $items->whereHas('contractCourses', fn (Builder $courses): Builder => $courses->withTrashed()->where('course_id', $this->courseId)))
                ->whereHas('completionHistories', fn (Builder $history): Builder => $history
                    ->whereNotNull('completed_at')->whereBetween('completed_at', [$start, $end])))
            ->distinct('student_id')->count('student_id');
    }

    private function distinctStudentsWithCompletedCourses(Carbon $start, Carbon $end): int
    {
        return $this->applyContractFilters(Contract::query())
            ->whereHas('items', fn (Builder $items): Builder => $items->withTrashed()
                ->whereHas('contractCourses', fn (Builder $courses): Builder => $courses->withTrashed()
                    ->where('status', 'completed')
                    ->when($this->courseId !== '', fn (Builder $courses): Builder => $courses->where('course_id', $this->courseId))
                    ->when($this->formationId !== '', fn (Builder $courses): Builder => $courses->whereHas('contractItem', fn (Builder $items): Builder => $items->withTrashed()->where('formation_id', $this->formationId)))
                    ->whereHas('progressHistories', fn (Builder $history): Builder => $history
                        ->where('status', 'completed')->whereBetween('completed_at', [$start, $end]))))
            ->distinct('student_id')->count('student_id');
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function periodBounds(): array
    {
        if (! $this->hasValidPeriod()) {
            return [now()->startOfMonth(), now()->endOfMonth()];
        }

        return [Carbon::parse($this->periodStart)->startOfDay(), Carbon::parse($this->periodEnd)->endOfDay()];
    }

    private function hasValidPeriod(): bool
    {
        return Validator::make([
            'periodStart' => $this->periodStart,
            'periodEnd' => $this->periodEnd,
        ], [
            'periodStart' => ['required', 'date_format:Y-m-d'],
            'periodEnd' => ['required', 'date_format:Y-m-d', 'after_or_equal:periodStart'],
        ])->passes();
    }

    private function safeCsvValue(mixed $value): string
    {
        $value = (string) ($value ?? '');

        return preg_match('/^[=+@\-\t\r]/', $value) ? "'{$value}" : $value;
    }
}

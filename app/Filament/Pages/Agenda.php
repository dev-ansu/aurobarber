<?php

namespace App\Filament\Pages;

use App\Actions\GenerateStudentAttendancesForDate;
use App\Models\Classroom;
use App\Models\ClassSession;
use App\Models\Contract;
use App\Models\ContractCourse;
use App\Models\Person;
use App\Models\StudentAttendance;
use App\Models\StudentSchedule;
use App\Models\User;
use App\Services\OuroModernoProgressSyncService;
use App\Services\OuroModernoService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;

class Agenda extends Page
{
    protected static ?string $navigationLabel = 'Agenda de chamadas';

    protected static string|\UnitEnum|null $navigationGroup = 'Pedagógico';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.agenda';

    public string $date = '';

    public ?string $classroomId = null;

    public string $startHour = '';

    public string $search = '';

    #[Locked]
    public ?int $expandedStudentId = null;

    /**
     * @var array<int, array{status?: string, note?: string}>
     */
    public array $attendanceEdits = [];

    public function mount(): void
    {
        $this->date = now()->toDateString();
    }

    public function updatedDate(): void
    {
        $this->validate(['date' => ['required', 'date_format:Y-m-d']]);
        $this->attendanceEdits = [];
        $this->expandedStudentId = null;
    }

    public function updatedSearch(): void
    {
        $this->validate(['search' => ['nullable', 'string', 'max:100']]);
        $this->expandedStudentId = null;
    }

    public function updatedClassroomId(): void
    {
        $this->validate(['classroomId' => ['nullable', 'integer', 'exists:classrooms,id']]);
        $this->attendanceEdits = [];
        $this->expandedStudentId = null;
    }

    public function updatedStartHour(): void
    {
        $this->validate(['startHour' => ['nullable', 'date_format:H:i']]);
        $this->attendanceEdits = [];
        $this->expandedStudentId = null;
    }

    /**
     * @return Collection<string, string>
     */
    #[Computed]
    public function availableHours(): Collection
    {
        $date = $this->selectedDate();

        $hours = $date->isFuture()
            ? $this->scheduleQuery($date, applyStartHour: false)
                ->select('start_hour')
                ->distinct()
                ->orderBy('start_hour')
                ->pluck('start_hour')
            : ClassSession::query()
                ->whereHas('classroom')
                ->whereDate('date', $date->toDateString())
                ->when($this->classroomId, fn (Builder $query): Builder => $query
                    ->where('classroom_id', $this->classroomId))
                ->distinct()
                ->orderBy('start_hour')
                ->pluck('start_hour');

        return $hours
            ->mapWithKeys(fn (string $hour): array => [
                Carbon::parse($hour)->format('H:i') => Carbon::parse($hour)->format('H:i'),
            ]);
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->getAllPermissions()->contains('name', 'View:Agenda');
    }

    /**
     * @return Collection<int, Classroom>
     */
    #[Computed]
    public function classrooms(): Collection
    {
        return Classroom::query()
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, StudentSchedule|StudentAttendance>
     */
    #[Computed]
    public function agendaRows(): Collection
    {
        $date = $this->selectedDate();

        if ($date->isFuture()) {
            return $this->scheduleQuery($date)
                ->with([
                    'classroom',
                    'contractItem.formation',
                    'contractItem.contractCourses.course',
                    'contractItem.contract.student.contacts',
                ])
                ->get()
                ->sortBy(fn (StudentSchedule $schedule): string => $schedule->start_hour)
                ->values();
        }

        if ($date->isToday()) {
            $hasTodayAttendances = StudentAttendance::query()
                ->whereDate('attendance_date', $date->toDateString())
                ->exists();

            if (! $hasTodayAttendances) {
                app(GenerateStudentAttendancesForDate::class)->execute($date);
            }
        }

        $rows = StudentAttendance::query()
            ->whereDate('attendance_date', $date->toDateString())
            ->when($this->classroomId, fn (Builder $query): Builder => $query->whereHas(
                'session',
                fn (Builder $sessionQuery): Builder => $sessionQuery->where('classroom_id', $this->classroomId),
            ))
            ->when($this->startHour !== '', fn (Builder $query): Builder => $query->whereHas(
                'session',
                fn (Builder $sessionQuery): Builder => $sessionQuery->where(
                    'start_hour',
                    Carbon::createFromFormat('H:i', $this->startHour)->format('H:i:s'),
                ),
            ))
            ->when($this->search !== '', fn (Builder $query): Builder => $query->whereHas(
                'studentSchedule.contractItem.contract.student',
                fn (Builder $studentQuery): Builder => $studentQuery->whereLike('name', '%'.$this->search.'%'),
            ))
            ->with([
                'session.classroom',
                'studentSchedule.contractItem.formation',
                'studentSchedule.contractItem.contractCourses.course',
                'studentSchedule.contractItem.contract.student.contacts',
            ])
            ->get()
            ->sortBy(fn (StudentAttendance $attendance): string => $attendance->session->start_hour)
            ->values();

        foreach ($rows as $attendance) {
            $this->attendanceEdits[$attendance->id] ??= [
                'status' => $attendance->status,
                'note' => $attendance->note ?? '',
            ];
        }

        return $rows;
    }

    #[Computed]
    public function hasSessionsForSelectedDate(): bool
    {
        return ClassSession::query()
            ->whereDate('date', $this->selectedDate()->toDateString())
            ->when($this->classroomId, fn (Builder $query): Builder => $query
                ->where('classroom_id', $this->classroomId))
            ->when($this->startHour !== '', fn (Builder $query): Builder => $query
                ->where('start_hour', Carbon::createFromFormat('H:i', $this->startHour)->format('H:i:s')))
            ->exists();
    }

    public function generateTodayAttendance(): void
    {
        $this->authorizeStaff();

        if (! $this->selectedDate()->isToday()) {
            throw ValidationException::withMessages([
                'date' => 'A geração de chamadas está disponível somente para hoje.',
            ]);
        }

        $createdCount = app(GenerateStudentAttendancesForDate::class)->execute($this->selectedDate());

        Notification::make()
            ->title($createdCount === 0 ? 'As chamadas já estavam atualizadas.' : "Chamadas geradas: {$createdCount}.")
            ->success()
            ->send();
    }

    public function saveAttendance(int $attendanceId): void
    {
        $this->authorizeStaff();

        $input = Validator::make($this->attendanceEdits[$attendanceId] ?? [], [
            'status' => ['required', Rule::in(['present', 'absent'])],
            'note' => ['nullable', 'string', 'max:1000'],
        ])->validate();

        $attendance = StudentAttendance::query()
            ->whereKey($attendanceId)
            ->whereDate('attendance_date', $this->selectedDate()->toDateString())
            ->when($this->classroomId, fn (Builder $query): Builder => $query->whereHas(
                'session',
                fn (Builder $sessionQuery): Builder => $sessionQuery->where('classroom_id', $this->classroomId),
            ))
            ->with('studentSchedule.contractItem.contract.student')
            ->firstOrFail();

        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        $statusChanged = $attendance->status !== $input['status'];
        $isAlreadyMarked = $attendance->marked_at !== null;

        if ($statusChanged && $isAlreadyMarked) {
            $canChange = $user->hasRole('super_admin') || $user->can('ChangeStatus:StudentAttendance');
            if (! $canChange) {
                throw ValidationException::withMessages([
                    "attendanceEdits.{$attendanceId}.status" => 'Você não possui permissão para alterar a frequência de uma chamada já registrada.',
                ]);
            }

            $reason = trim($input['note'] ?? '');
            if ($reason === '') {
                throw ValidationException::withMessages([
                    "attendanceEdits.{$attendanceId}.note" => 'A justificativa/observação é obrigatória para alterar a frequência (Presença ⇄ Falta).',
                ]);
            }

            $attendance->changeStatus($input['status'], $user, $reason);

            Notification::make()
                ->title('Frequência alterada com sucesso!')
                ->body('A alteração foi registrada e auditada no sistema.')
                ->success()
                ->send();

            return;
        }

        $attendance->mark($input['status'], $user, 'staff', $input['note'] ?? null);

        Notification::make()
            ->title('Chamada atualizada.')
            ->success()
            ->send();
    }

    public function toggleContacts(int $studentId): void
    {
        $this->authorizeStaff();

        abort_unless(
            $this->agendaRows->contains(fn (StudentSchedule|StudentAttendance $row): bool => $this->studentId($row) === $studentId),
            404,
        );

        $this->expandedStudentId = $this->expandedStudentId === $studentId ? null : $studentId;
    }

    public function studentRecord(StudentSchedule|StudentAttendance $row): Person
    {
        return $row instanceof StudentAttendance
            ? $row->studentSchedule->contractItem->contract->student
            : $row->contractItem->contract->student;
    }

    public function contractRecord(StudentSchedule|StudentAttendance $row): Contract
    {
        return $row instanceof StudentAttendance
            ? $row->studentSchedule->contractItem->contract
            : $row->contractItem->contract;
    }

    public function studentId(StudentSchedule|StudentAttendance $row): int
    {
        return (int) $this->studentRecord($row)->getKey();
    }

    public function studentName(StudentSchedule|StudentAttendance $row): string
    {
        return $this->studentRecord($row)->name;
    }

    public function formationName(StudentSchedule|StudentAttendance $row): string
    {
        return $row instanceof StudentAttendance
            ? $row->studentSchedule->contractItem->formation->name
            : $row->contractItem->formation->name;
    }

    public function rowStartHour(StudentSchedule|StudentAttendance $row): string
    {
        return $row instanceof StudentAttendance
            ? $row->session->formattedStartHour()
            : $row->formattedStartHour();
    }

    public function rowEndHour(StudentSchedule|StudentAttendance $row): string
    {
        return $row instanceof StudentAttendance
            ? $row->session->formattedEndHour()
            : $row->formattedEndHour();
    }

    public function rowClassroom(StudentSchedule|StudentAttendance $row): string
    {
        return $row instanceof StudentAttendance
            ? $row->session->classroom->name
            : $row->classroom->name;
    }

    /**
     * @param  Collection<int, StudentSchedule|StudentAttendance>  $rows
     * @return array{percentage: int, label: string, bar: string, text: string, icon: string}
     */
    public function attendanceSummary(Collection $rows): array
    {
        $percentage = $rows->isEmpty()
            ? 0
            : (int) round(($rows->where('status', 'present')->count() / $rows->count()) * 100);

        return match (true) {
            $percentage >= 90 => [
                'percentage' => $percentage,
                'label' => 'Excelente',
                'bar' => 'bg-success-500',
                'text' => 'text-success-600 dark:text-success-400',
                'icon' => '🟢',
            ],
            $percentage >= 70 => [
                'percentage' => $percentage,
                'label' => 'Atenção',
                'bar' => 'bg-warning-500',
                'text' => 'text-warning-600 dark:text-warning-400',
                'icon' => '🟡',
            ],
            default => [
                'percentage' => $percentage,
                'label' => 'Baixa',
                'bar' => 'bg-danger-500',
                'text' => 'text-danger-600 dark:text-danger-400',
                'icon' => '🔴',
            ],
        };
    }

    /**
     * @return array{course_name: string, current_lesson: int|string|null, completed_lessons: int, total_lessons: int, average: ?float, status_label: ?string, local_status: string, has_lms_id: bool, synced_at: ?string}|null
     */
    public function studentLmsProgress(StudentSchedule|StudentAttendance $row): ?array
    {
        $contractItem = $row instanceof StudentAttendance
            ? $row->studentSchedule?->contractItem
            : $row->contractItem;

        if (! $contractItem) {
            return null;
        }

        $courses = $contractItem->contractCourses;
        if ($courses->isEmpty()) {
            return null;
        }

        /** @var ContractCourse|null $current */
        $current = $courses->firstWhere('status', 'in_progress')
            ?? $courses->firstWhere('status', 'not_started')
            ?? $courses->first();

        if (! $current) {
            return null;
        }

        $feedback = app(OuroModernoProgressSyncService::class)->feedback($current);

        return [
            'course_name' => $current->course?->name ?? 'Curso sem nome',
            'current_lesson' => $feedback['current_lesson'] ?? null,
            'completed_lessons' => (int) ($feedback['completed_lessons'] ?? 0),
            'total_lessons' => (int) ($feedback['total_lessons'] ?? 0),
            'average' => $feedback['average'] ?? ($current->grade !== null ? (float) $current->grade : null),
            'status_label' => $feedback['course_status_label'] ?? null,
            'local_status' => $current->status,
            'has_lms_id' => $current->course?->ouro_moderno_id !== null,
            'synced_at' => $current->lms_synced_at?->format('d/m/Y H:i') ?? $current->ouro_moderno_status_checked_at?->format('d/m/Y H:i'),
        ];
    }

    public function syncStudentLms(int $contractId): void
    {
        $this->authorizeStaff();

        $contract = Contract::query()
            ->with(['student', 'items.contractCourses.course'])
            ->find($contractId);

        if (! $contract) {
            Notification::make()->title('Contrato não encontrado.')->danger()->send();

            return;
        }

        if (! $contract->student?->ouro_moderno_cod_int) {
            Notification::make()
                ->title('Aluno sem código no LMS')
                ->body('O aluno não possui código de integração (cod_int) cadastrado no sistema.')
                ->warning()
                ->send();

            return;
        }

        $ouroModerno = app(OuroModernoService::class)->forSchool($contract->school_id);
        if (! $ouroModerno->isConfigured()) {
            Notification::make()
                ->title('Integração Ouro Moderno não configurada')
                ->warning()
                ->send();

            return;
        }

        $progressSync = app(OuroModernoProgressSyncService::class);
        $courses = $contract->items
            ->flatMap(fn ($item) => $item->contractCourses)
            ->filter(fn ($link) => $link->course?->ouro_moderno_id !== null);

        if ($courses->isEmpty()) {
            Notification::make()
                ->title('Nenhum curso do LMS vinculado a este contrato.')
                ->warning()
                ->send();

            return;
        }

        try {
            $updated = 0;
            foreach ($courses as $link) {
                $progressSync->sync($link, (int) $contract->student->ouro_moderno_cod_int, $ouroModerno);
                $updated++;
            }

            Notification::make()
                ->title('Progresso sincronizado com o LMS')
                ->body("{$updated} curso(s) atualizados para {$contract->student->name}.")
                ->success()
                ->send();
        } catch (\Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('Erro ao sincronizar com o LMS')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    private function authorizeStaff(): void
    {
        abort_unless(self::canAccess(), 403);
    }

    private function selectedDate(): Carbon
    {
        Validator::make(['date' => $this->date], [
            'date' => ['required', 'date_format:Y-m-d'],
        ])->validate();

        return Carbon::parse($this->date)->startOfDay();
    }

    /**
     * @return Builder<StudentSchedule>
     */
    private function scheduleQuery(Carbon $date, bool $applyStartHour = true): Builder
    {
        return StudentSchedule::query()
            ->where('weekday', $date->dayOfWeek)
            ->whereHas('contractItem', fn (Builder $query): Builder => $query
                ->whereNull('deleted_at')
                ->whereNull('formation_completed_at')
                ->whereHas('contract', fn (Builder $contractQuery): Builder => $contractQuery
                    ->where('status', 'active')
                    ->whereDate('start_date', '<=', $date)))
            ->when($this->classroomId, fn (Builder $query): Builder => $query
                ->where('classroom_id', $this->classroomId))
            ->when($applyStartHour && $this->startHour !== '', fn (Builder $query): Builder => $query
                ->where('start_hour', Carbon::createFromFormat('H:i', $this->startHour)->format('H:i:s')))
            ->when($this->search !== '', fn (Builder $query): Builder => $query->whereHas(
                'contractItem.contract.student',
                fn (Builder $studentQuery): Builder => $studentQuery->whereLike('name', '%'.$this->search.'%'),
            ));
    }
}

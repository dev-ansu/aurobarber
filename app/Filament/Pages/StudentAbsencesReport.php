<?php

namespace App\Filament\Pages;

use App\Enums\ContactTypeEnum;
use App\Models\Classroom;
use App\Models\Contract;
use App\Models\Formation;
use App\Models\StudentAttendance;
use App\Models\StudentMakeupClass;
use App\Models\StudentSchedule;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class StudentAbsencesReport extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Relatórios';

    protected static ?string $navigationParentItem = 'Pedagógico';

    protected static ?string $navigationLabel = 'Alunos faltantes';

    protected static ?string $title = 'Relatório de alunos faltantes e busca ativa';

    protected static ?string $slug = 'student-absences-report';

    protected static ?int $navigationSort = 6;

    protected string $view = 'filament.pages.student-absences-report';

    public string $activeTab = 'day';

    // Filtros da aba "Faltantes do Dia"
    public string $dayDate = '';

    public string $dayClassroomId = '';

    public string $dayStartHour = '';

    // Filtros da aba "Faltantes por Período"
    public string $fromDate = '';

    public string $untilDate = '';

    public string $periodClassroomId = '';

    public string $periodFormationId = '';

    public string $minAbsences = '1';

    // Modal e filtros de Reposição de Aula Agendada
    public bool $showMakeupModal = false;

    public ?int $makeupContractId = null;

    public ?int $makeupAttendanceId = null;

    public string $makeupStudentName = '';

    public string $makeupDate = '';

    public string $makeupClassroomId = '';

    public string $makeupStartHour = '14:00';

    public string $makeupEndHour = '15:30';

    public string $makeupNotes = '';

    public string $makeupStatusFilter = 'scheduled';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && ($user->hasRole('super_admin') || $user->can('View:StudentAbsencesReport'));
    }

    public function mount(): void
    {
        $this->dayDate = today()->toDateString();
        $this->fromDate = today()->subDays(29)->toDateString();
        $this->untilDate = today()->toDateString();
        $this->makeupDate = today()->toDateString();

        $reqContractId = (int) request()->query('schedule_makeup_contract', 0);
        if ($reqContractId > 0) {
            $contract = Contract::with('student')->find($reqContractId);
            if ($contract) {
                $this->openMakeupModal($contract->id, $contract->student?->name ?? 'Aluno');
            }
        }
    }

    public function classrooms(): array
    {
        return Classroom::query()->orderBy('name')->pluck('name', 'id')->all();
    }

    public function formations(): array
    {
        return Formation::query()->orderBy('name')->pluck('name', 'id')->all();
    }

    public function startHours(): array
    {
        return StudentSchedule::query()
            ->whereNull('deleted_at')
            ->distinct()
            ->orderBy('start_hour')
            ->pluck('start_hour')
            ->mapWithKeys(fn (string $hour): array => [
                Carbon::parse($hour)->format('H:i') => Carbon::parse($hour)->format('H:i'),
            ])
            ->all();
    }

    /**
     * Retorna os alunos que faltaram no dia selecionado
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function dayAbsences(): Collection
    {
        $date = Carbon::parse($this->dayDate);

        $startOfDay = $date->copy()->startOfDay()->toDateTimeString();
        $endOfDay = $date->copy()->endOfDay()->toDateTimeString();
        $d = $date->toDateString();

        $attendances = StudentAttendance::query()
            ->where(function (Builder $q) use ($startOfDay, $endOfDay, $d): void {
                $q->whereBetween('attendance_date', [$startOfDay, $endOfDay])
                    ->orWhere('attendance_date', $d)
                    ->orWhere('attendance_date', 'like', "{$d}%");
            })
            ->where('status', 'absent')
            ->when(filled($this->dayClassroomId), fn (Builder $q) => $q->whereHas(
                'session',
                fn (Builder $sq) => $sq->where('classroom_id', (int) $this->dayClassroomId),
            ))
            ->when(filled($this->dayStartHour), fn (Builder $q) => $q->whereHas(
                'session',
                fn (Builder $sq) => $sq->where('start_hour', Carbon::createFromFormat('H:i', $this->dayStartHour)->format('H:i:s')),
            ))
            ->with([
                'session.classroom',
                'studentSchedule.contractItem.contract.student.contacts',
                'studentSchedule.contractItem.contract.responsible.person.contacts',
                'studentSchedule.contractItem.formation',
                'studentSchedule.contractItem.contractCourses.course',
            ])
            ->get();

        return $attendances->map(function (StudentAttendance $att): array {
            $schedule = $att->studentSchedule;
            $contract = $schedule?->contractItem?->contract;
            $student = $contract?->student;
            $responsible = $contract?->responsible?->person;

            // Extrai todos os contatos do RelationManager de People
            $studentContacts = $student?->contacts->map(fn ($c): array => [
                'type' => $c->type?->getLabel() ?? 'Contato',
                'value' => $c->value,
                'clean' => preg_replace('/\D/', '', (string) $c->value),
                'is_whatsapp' => in_array($c->type, [ContactTypeEnum::WhatsApp, ContactTypeEnum::Celular], true),
                'is_email' => $c->type === ContactTypeEnum::Email,
                'is_primary' => (bool) $c->is_primary,
            ])->all() ?? [];

            // Extrai WhatsApp e Celular do aluno
            $studentWhatsapp = $student?->contacts->firstWhere('type', ContactTypeEnum::WhatsApp)?->value
                ?? $student?->contacts->firstWhere('type', ContactTypeEnum::Celular)?->value;

            // Extrai WhatsApp e Celular do responsável
            $respWhatsapp = $responsible?->contacts->firstWhere('type', ContactTypeEnum::WhatsApp)?->value
                ?? $responsible?->contacts->firstWhere('type', ContactTypeEnum::Celular)?->value;

            // Histórico de faltas recentes nos últimos 30 dias
            $recentAbsencesCount = StudentAttendance::query()
                ->where('student_schedule_id', $att->student_schedule_id)
                ->where('status', 'absent')
                ->whereBetween('attendance_date', [today()->subDays(30)->toDateString(), today()->toDateString()])
                ->count();

            $courseName = $schedule?->contractItem?->formation?->name
                ?? $schedule?->contractItem?->contractCourses?->first()?->course?->name
                ?? 'Curso Regular';

            return [
                'attendance_id' => $att->id,
                'contract_id' => $contract?->id,
                'student_id' => $student?->id,
                'student_name' => $student?->name ?? 'Aluno não identificado',
                'student_code' => $student?->ouro_moderno_cod_int ?: $student?->id,
                'student_contacts' => $studentContacts,
                'student_phone' => $studentWhatsapp,
                'clean_phone' => preg_replace('/\D/', '', (string) $studentWhatsapp),
                'responsible_name' => $responsible?->name,
                'responsible_phone' => $respWhatsapp,
                'clean_resp_phone' => preg_replace('/\D/', '', (string) $respWhatsapp),
                'classroom' => $att->session?->classroom?->name ?? 'Sala não informada',
                'time' => $att->session ? Carbon::parse($att->session->start_hour)->format('H:i').' - '.Carbon::parse($att->session->end_hour)->format('H:i') : '—',
                'course' => $courseName,
                'contract_number' => $contract?->contract_number,
                'recent_absences_count' => $recentAbsencesCount,
                'note' => $att->note,
            ];
        });
    }

    /**
     * Retorna o consolidado de faltas por período para busca ativa e prevenção de evasão
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function periodAbsences(): Collection
    {
        $from = Carbon::parse($this->fromDate)->toDateString();
        $until = Carbon::parse($this->untilDate)->toDateString();

        $attendances = StudentAttendance::query()
            ->whereBetween('attendance_date', [$from, $until.' 23:59:59'])
            ->whereNotNull('marked_at')
            ->when(filled($this->periodClassroomId), fn (Builder $q) => $q->whereHas(
                'session',
                fn (Builder $sq) => $sq->where('classroom_id', (int) $this->periodClassroomId),
            ))
            ->when(filled($this->periodFormationId), fn (Builder $q) => $q->whereHas(
                'studentSchedule.contractItem',
                fn (Builder $iq) => $iq->where('formation_id', (int) $this->periodFormationId),
            ))
            ->with([
                'session.classroom',
                'studentSchedule.contractItem.contract.student.contacts',
                'studentSchedule.contractItem.contract.responsible.person.contacts',
                'studentSchedule.contractItem.formation',
            ])
            ->get();

        $grouped = [];

        foreach ($attendances as $att) {
            $student = $att->studentSchedule?->contractItem?->contract?->student;
            if (! $student) {
                continue;
            }

            $id = $student->id;
            if (! isset($grouped[$id])) {
                $contract = $att->studentSchedule?->contractItem?->contract;
                $responsible = $contract?->responsible?->person;
                $studentWhatsapp = $student->contacts->firstWhere('type', ContactTypeEnum::WhatsApp)?->value
                    ?? $student->contacts->firstWhere('type', ContactTypeEnum::Celular)?->value;
                $respWhatsapp = $responsible?->contacts->firstWhere('type', ContactTypeEnum::WhatsApp)?->value
                    ?? $responsible?->contacts->firstWhere('type', ContactTypeEnum::Celular)?->value;

                $studentContacts = $student->contacts->map(fn ($c): array => [
                    'type' => $c->type?->getLabel() ?? 'Contato',
                    'value' => $c->value,
                    'clean' => preg_replace('/\D/', '', (string) $c->value),
                    'is_whatsapp' => in_array($c->type, [ContactTypeEnum::WhatsApp, ContactTypeEnum::Celular], true),
                    'is_email' => $c->type === ContactTypeEnum::Email,
                    'is_primary' => (bool) $c->is_primary,
                ])->all();

                $grouped[$id] = [
                    'contract_id' => $contract?->id,
                    'student_id' => $id,
                    'student_name' => $student->name,
                    'student_code' => $student->ouro_moderno_cod_int ?: $student->id,
                    'student_contacts' => $studentContacts,
                    'student_phone' => $studentWhatsapp,
                    'clean_phone' => preg_replace('/\D/', '', (string) $studentWhatsapp),
                    'responsible_name' => $responsible?->name,
                    'responsible_phone' => $respWhatsapp,
                    'course' => $att->studentSchedule?->contractItem?->formation?->name ?? 'Curso Regular',
                    'contract_number' => $contract?->contract_number,
                    'total_sessions' => 0,
                    'absent_count' => 0,
                    'present_count' => 0,
                ];
            }

            $grouped[$id]['total_sessions']++;
            if ($att->status === 'absent') {
                $grouped[$id]['absent_count']++;
            } else {
                $grouped[$id]['present_count']++;
            }
        }

        $minAbs = max(1, (int) $this->minAbsences);

        return collect($grouped)
            ->filter(fn (array $row): bool => $row['absent_count'] >= $minAbs)
            ->map(function (array $row): array {
                $total = max(1, $row['total_sessions']);
                $rate = round(($row['absent_count'] / $total) * 100, 1);

                if ($row['absent_count'] >= 4 || ($total >= 3 && $rate >= 35)) {
                    $risk = 'Crítico / Alto Risco';
                    $riskColor = 'danger';
                } elseif ($row['absent_count'] >= 2 || ($total >= 3 && $rate >= 20)) {
                    $risk = 'Alerta / Moderado';
                    $riskColor = 'warning';
                } else {
                    $risk = 'Acompanhar';
                    $riskColor = 'info';
                }

                $row['absence_rate'] = $rate;
                $row['risk_level'] = $risk;
                $row['risk_color'] = $riskColor;

                return $row;
            })
            ->sortByDesc('absent_count')
            ->values();
    }

    public function openMakeupModal(?int $contractId, string $studentName, ?int $attendanceId = null): void
    {
        if (! $contractId) {
            Notification::make()
                ->title('Contrato não localizado para este aluno.')
                ->warning()
                ->send();

            return;
        }

        $this->makeupContractId = $contractId;
        $this->makeupAttendanceId = $attendanceId;
        $this->makeupStudentName = $studentName;
        $this->makeupDate = today()->toDateString();
        $this->makeupStartHour = '14:00';
        $this->makeupEndHour = '15:30';
        $this->makeupNotes = '';
        $this->showMakeupModal = true;
    }

    public function saveMakeupClass(): void
    {
        if (! $this->makeupContractId || blank($this->makeupDate) || blank($this->makeupStartHour) || blank($this->makeupEndHour)) {
            Notification::make()
                ->title('Preencha data, horário inicial e final para agendar a reposição.')
                ->warning()
                ->send();

            return;
        }

        StudentMakeupClass::query()->create([
            'contract_id' => $this->makeupContractId,
            'student_attendance_id' => $this->makeupAttendanceId,
            'classroom_id' => filled($this->makeupClassroomId) ? (int) $this->makeupClassroomId : null,
            'scheduled_by_user_id' => auth()->id(),
            'makeup_date' => $this->makeupDate,
            'start_hour' => strlen($this->makeupStartHour) === 5 ? $this->makeupStartHour.':00' : $this->makeupStartHour,
            'end_hour' => strlen($this->makeupEndHour) === 5 ? $this->makeupEndHour.':00' : $this->makeupEndHour,
            'status' => 'scheduled',
            'notes' => trim($this->makeupNotes) ?: null,
        ]);

        $this->showMakeupModal = false;

        Notification::make()
            ->title('Reposição de aula agendada!')
            ->body("O portal do aluno {$this->makeupStudentName} será liberado automaticamente no horário agendado.")
            ->success()
            ->send();
    }

    public function completeMakeupClass(int $id): void
    {
        StudentMakeupClass::whereKey($id)->update(['status' => 'completed']);

        Notification::make()
            ->title('Reposição marcada como concluída!')
            ->success()
            ->send();
    }

    public function cancelMakeupClass(int $id): void
    {
        StudentMakeupClass::whereKey($id)->update(['status' => 'cancelled']);

        Notification::make()
            ->title('Reposição cancelada.')
            ->info()
            ->send();
    }

    /**
     * @return Collection<int, StudentMakeupClass>
     */
    public function scheduledMakeups(): Collection
    {
        return StudentMakeupClass::query()
            ->whereHas('contract')
            ->with(['contract.student', 'classroom', 'scheduledBy'])
            ->when(filled($this->makeupStatusFilter), fn (Builder $q) => $q->where('status', $this->makeupStatusFilter))
            ->orderByDesc('makeup_date')
            ->orderBy('start_hour')
            ->limit(100)
            ->get();
    }

    public function exportPeriodReport(): StreamedResponse
    {
        $user = auth()->user();
        abort_unless(
            $user instanceof User && ($user->hasRole('super_admin') || $user->can('Export:StudentAbsencesReport')),
            403
        );

        $rows = $this->periodAbsences();
        $filename = 'alunos-faltantes-'.$this->fromDate.'-a-'.$this->untilDate.'.csv';

        return response()->streamDownload(function () use ($rows): void {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, ['Relatório de Alunos Faltantes por Período', $this->fromDate, $this->untilDate], ';', '"', '');
            fputcsv($stream, [], ';', '"', '');
            fputcsv($stream, [
                'Aluno', 'Cód. Aluno', 'Telefone', 'Responsável', 'Telefone Resp.',
                'Curso', 'Contrato', 'Total Aulas', 'Faltas', 'Presenças', '% Falta', 'Risco',
            ], ';', '"', '');

            foreach ($rows as $row) {
                fputcsv($stream, [
                    $row['student_name'],
                    $row['student_code'],
                    $row['student_phone'] ?? '—',
                    $row['responsible_name'] ?? '—',
                    $row['responsible_phone'] ?? '—',
                    $row['course'],
                    $row['contract_number'] ?? '—',
                    $row['total_sessions'],
                    $row['absent_count'],
                    $row['present_count'],
                    $row['absence_rate'].'%',
                    $row['risk_level'],
                ], ';', '"', '');
            }

            fclose($stream);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}

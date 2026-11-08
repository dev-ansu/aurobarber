<?php

namespace App\Filament\Pages;

use App\Models\Classroom;
use App\Models\Formation;
use App\Models\StudentSchedule;
use App\Services\PedagogicalFollowUpReport as PedagogicalFollowUpReportService;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class PedagogicalFollowUpReport extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Relatórios';

    protected static ?string $navigationParentItem = 'Pedagógico';

    protected static ?string $navigationLabel = 'Acompanhamento pedagógico';

    protected static ?string $title = 'Acompanhamento pedagógico';

    protected static ?string $slug = 'pedagogical-follow-up-report';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.pedagogical-follow-up-report';

    public ?string $fromDate = null;

    public ?string $untilDate = null;

    public ?string $classroomId = null;

    public ?string $formationId = null;

    public ?string $weekday = null;

    public ?string $startHour = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('View:PedagogicalFollowUpReport') ?? false;
    }

    public function mount(): void
    {
        $this->fromDate = today()->subDays(29)->toDateString();
        $this->untilDate = today()->toDateString();
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
            ->mapWithKeys(fn (string $hour): array => [Carbon::parse($hour)->format('H:i') => Carbon::parse($hour)->format('H:i')])
            ->all();
    }

    public function weekdays(): array
    {
        return [
            0 => 'Domingo',
            1 => 'Segunda-feira',
            2 => 'Terça-feira',
            3 => 'Quarta-feira',
            4 => 'Quinta-feira',
            5 => 'Sexta-feira',
            6 => 'Sábado',
        ];
    }

    public function reportData(): array
    {
        $validated = $this->validateFilters();

        if (! $validated) {
            return self::emptyReport();
        }

        return app(PedagogicalFollowUpReportService::class)->generate(
            Carbon::parse($validated['from']),
            Carbon::parse($validated['until']),
            filled($validated['classroom_id'] ?? null) ? (int) $validated['classroom_id'] : null,
            filled($validated['formation_id'] ?? null) ? (int) $validated['formation_id'] : null,
            filled($validated['weekday'] ?? null) ? (int) $validated['weekday'] : null,
            filled($validated['start_hour'] ?? null) ? $validated['start_hour'].':00' : null,
        );
    }

    public function exportReport(): StreamedResponse
    {
        abort_unless(auth()->user()?->can('Export:PedagogicalFollowUpReport'), 403);
        $this->validateFilters(true);
        $report = $this->reportData();
        $filename = 'acompanhamento-pedagogico-'.$this->fromDate.'-'.$this->untilDate.'.csv';

        return response()->streamDownload(function () use ($report): void {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, ['Aluno', 'Contrato', 'Formação', 'Horários e salas', 'Aulas confirmadas', 'Presenças', 'Faltas', 'Presença (%)', 'Faltas consecutivas', 'Indicador', 'Última presença'], ';', '"', '');

            foreach ($report['students'] as $student) {
                fputcsv($stream, [
                    $student['student'], $student['contracts'], $student['formations'], $student['schedules'],
                    $student['sessions'], $student['present'], $student['absent'], $student['attendance_rate'],
                    $student['consecutive_absences'], $student['health']['label'], $student['last_attended'],
                ], ';', '"', '');
            }

            fputcsv($stream, [], ';', '"', '');
            fputcsv($stream, ['Chamadas ainda não marcadas', $report['unmarked_sessions']], ';', '"', '');

            foreach ([
                'Por sala' => $report['by_classroom'],
                'Por dia da semana' => $report['by_weekday'],
                'Por horário' => $report['by_time'],
            ] as $title => $rows) {
                fputcsv($stream, [], ';', '"', '');
                fputcsv($stream, [$title, 'Alunos', 'Aulas', 'Presenças', 'Faltas', 'Presença (%)', 'Faltas (%)'], ';', '"', '');

                foreach ($rows as $row) {
                    fputcsv($stream, [
                        $row['label'], $row['students'], $row['sessions'], $row['present'],
                        $row['absent'], $row['attendance_rate'], $row['absence_rate'],
                    ], ';', '"', '');
                }
            }

            fclose($stream);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function validateFilters(bool $throw = false): ?array
    {
        $validator = Validator::make([
            'from' => $this->fromDate,
            'until' => $this->untilDate,
            'classroom_id' => $this->classroomId,
            'formation_id' => $this->formationId,
            'weekday' => $this->weekday,
            'start_hour' => $this->startHour,
        ], [
            'from' => ['required', 'date'],
            'until' => ['required', 'date', 'after_or_equal:from', 'before_or_equal:today'],
            'classroom_id' => ['nullable', 'integer', 'exists:classrooms,id'],
            'formation_id' => ['nullable', 'integer', 'exists:formations,id'],
            'weekday' => ['nullable', 'integer', 'between:0,6'],
            'start_hour' => ['nullable', 'date_format:H:i'],
        ]);

        if ($validator->fails()) {
            if ($throw) {
                $validator->validate();
            }

            return null;
        }

        $data = $validator->validated();
        if (Carbon::parse($data['from'])->diffInDays(Carbon::parse($data['until'])) > 365) {
            if ($throw) {
                abort(422, 'Selecione um período de até 366 dias.');
            }

            return null;
        }

        return $data;
    }

    private static function emptyReport(): array
    {
        return [
            'students_monitored' => 0,
            'students_at_risk' => 0,
            'students_at_risk_rate' => 0,
            'confirmed_sessions' => 0,
            'unmarked_sessions' => 0,
            'present_count' => 0,
            'absent_count' => 0,
            'attendance_rate' => 0,
            'absence_rate' => 0,
            'students' => [],
            'by_classroom' => [],
            'by_weekday' => [],
            'by_time' => [],
        ];
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GenerateStudentAttendancesForDate;
use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\School;
use App\Models\StudentAttendance;
use App\Models\StudentSchedule;
use Filament\Facades\Filament;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AgendaPrintController extends Controller
{
    public function __invoke(Request $request): View
    {
        $dateStr = $request->query('date', now()->toDateString());
        $date = Carbon::parse($dateStr);
        $classroomId = $request->query('classroom_id');
        $startHour = $request->query('start_hour');

        $schoolId = $request->query('school_id') ?? session('filament_tenant_id');
        $tenantSchool = $schoolId
            ? School::query()->withoutGlobalScopes()->find((int) $schoolId)
            : null;
        $school = $tenantSchool
            ?? (class_exists(\Filament\Facades\Filament::class) && \Filament\Facades\Filament::getTenant() instanceof School ? \Filament\Facades\Filament::getTenant() : null)
            ?? School::getActiveSchool()
            ?? School::query()->withoutGlobalScopes()->first();
        $classroom = $classroomId ? Classroom::query()->withoutGlobalScopes()->find($classroomId) : null;

        if ($tenantSchool) {
            Filament::setTenant($tenantSchool);
        }

        // Garante que as sessões e presenças do dia foram geradas se não for futuro
        if (! $date->isFuture()) {
            try {
                app(GenerateStudentAttendancesForDate::class)->execute($date);
            } catch (\Throwable) {
                // Se já existirem ou validação de data passada, prossegue com a consulta
            }

            $attendances = StudentAttendance::query()
                ->where(function ($q) use ($date): void {
                    $d = $date->toDateString();
                    $q->where('attendance_date', $d)->orWhere('attendance_date', 'like', "{$d}%");
                })
                ->when($classroomId, fn ($query) => $query->whereHas(
                    'session',
                    fn ($sessionQuery) => $sessionQuery->where('classroom_id', $classroomId),
                ))
                ->when($startHour, fn ($query) => $query->whereHas(
                    'session',
                    fn ($sessionQuery) => $sessionQuery->where('start_hour', Carbon::createFromFormat('H:i', $startHour)->format('H:i:s')),
                ))
                ->with([
                    'session.classroom',
                    'studentSchedule.contractItem.formation',
                    'studentSchedule.contractItem.contractCourses.course',
                    'studentSchedule.contractItem.contract.student',
                ])
                ->get()
                ->sortBy(fn (StudentAttendance $att) => ($att->session->start_hour ?? '').' '.($att->studentSchedule?->contractItem?->contract?->student?->name ?? ''))
                ->values();
        } else {
            // Data futura: lê diretamente os horários agendados
            $attendances = StudentSchedule::query()
                ->where('weekday', $date->dayOfWeek)
                ->whereNull('deleted_at')
                ->whereHas('contractItem.contract', fn ($q) => $q->where('status', 'active'))
                ->when($classroomId, fn ($q) => $q->where('classroom_id', $classroomId))
                ->when($startHour, fn ($q) => $q->where('start_hour', Carbon::createFromFormat('H:i', $startHour)->format('H:i:s')))
                ->with([
                    'classroom',
                    'contractItem.formation',
                    'contractItem.contractCourses.course',
                    'contractItem.contract.student',
                ])
                ->get()
                ->sortBy(fn (StudentSchedule $sch) => $sch->start_hour.' '.($sch->contractItem?->contract?->student?->name ?? ''))
                ->values();
        }

        return view('filament.pages.agenda-attendance-sheet', [
            'date' => $date,
            'school' => $school,
            'classroom' => $classroom,
            'startHour' => $startHour,
            'attendances' => $attendances,
            'isFuture' => $date->isFuture(),
        ]);
    }
}

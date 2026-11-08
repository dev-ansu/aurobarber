<?php

namespace App\Http\Controllers\Admin;

use App\Models\Contract;
use App\Models\ContractCourse;
use App\Models\User;
use Illuminate\View\View;

class OuroModernoStudentTranscriptController
{
    public function __invoke(?Contract $contract = null): View
    {
        if (! $contract || ! $contract->exists) {
            $user = auth()->user();
            $contractId = session('portal_contract_id');
            if ($contractId) {
                $contract = Contract::query()->find($contractId);
            }
            if (! $contract && $user instanceof User && $user->person_id) {
                $contract = Contract::query()
                    ->where('student_id', $user->person_id)
                    ->where('status', 'active')
                    ->latest('id')
                    ->first();
            }

            abort_unless($contract instanceof Contract, 404, 'Nenhuma matrícula ativa localizada para exibição do boletim.');
        }

        $this->authorizeAccess($contract);

        $contract->loadMissing([
            'student.documents',
            'student.contacts',
            'student.addresses.city.state',
            'responsible.person.documents',
            'responsible.person.contacts',
            'items.formation',
            'items.contractCourses.course',
            'items.schedules.attendances',
        ]);

        app(\App\Services\AurosoftLmsProgressSyncService::class)->syncAllForContract($contract);

        $attendanceData = $this->calculateAttendanceMetrics($contract);

        return view('contracts.ouro-moderno-student-transcript', [
            'contract' => $contract,
            'selectedCourse' => null,
            'school' => $contract->resolved_school,
            'attendanceData' => $attendanceData,
        ]);
    }

    public function showCourse(Contract $contract, ContractCourse $contractCourse): View
    {
        $this->authorizeAccess($contract);

        $contractCourse->load(['course', 'contractItem.formation', 'contractItem.contract.student']);

        abort_unless(
            $contractCourse->contractItem?->contract_id === $contract->getKey(),
            404,
        );

        if ($contractCourse->course?->isAurosoftLms()) {
            app(\App\Services\AurosoftLmsProgressSyncService::class)->sync($contractCourse);
        }

        $contract->loadMissing([
            'student.documents',
            'student.contacts',
            'student.addresses.city.state',
            'responsible.person.documents',
            'items.formation',
            'items.schedules.attendances',
        ]);

        $attendanceData = $this->calculateAttendanceMetrics($contract);

        return view('contracts.ouro-moderno-student-transcript', [
            'contract' => $contract,
            'selectedCourse' => $contractCourse,
            'school' => $contract->resolved_school,
            'attendanceData' => $attendanceData,
        ]);
    }

    /**
     * @return array{total: int, present: int, absent: int, rate: float|null}
     */
    private function calculateAttendanceMetrics(Contract $contract): array
    {
        $allAttendances = $contract->items
            ->flatMap(fn ($item) => $item->schedules)
            ->flatMap(fn ($schedule) => $schedule->attendances)
            ->whereNotNull('marked_at');

        $total = $allAttendances->count();
        $present = $allAttendances->where('status', 'present')->count();
        $absent = $allAttendances->where('status', 'absent')->count();
        $rate = $total > 0 ? round(($present / $total) * 100, 1) : null;

        return [
            'total' => $total,
            'present' => $present,
            'absent' => $absent,
            'rate' => $rate,
        ];
    }

    private function authorizeAccess(Contract $contract): void
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            abort(403, 'Acesso não autenticado.');
        }

        $isStudentOwner = ($user->person_id !== null && (int) $user->person_id === (int) $contract->student_id)
            || ((int) session('portal_contract_id') === (int) $contract->id);

        $isStaffAuthorized = $user->hasRole('super_admin')
            || $user->isStaff()
            || $user->isEducator()
            || $user->can('View:OuroModernoTranscript');

        abort_unless($isStudentOwner || $isStaffAuthorized, 403, 'Acesso não autorizado a este boletim escolar.');
    }
}

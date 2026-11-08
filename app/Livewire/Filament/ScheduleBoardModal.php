<?php

namespace App\Livewire\Filament;

use App\Models\Classroom;
use App\Models\StudentSchedule;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class ScheduleBoardModal extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    public string $mode = 'topbar';

    public ?int $classroomId = null;

    #[On('open-schedule-board')]
    public function openFromEvent(): void
    {
        if ($this->mode === 'topbar' && $this->canAccess()) {
            $this->mountAction('scheduleBoard');
        }
    }

    public function scheduleBoardAction(): Action
    {
        $action = Action::make('scheduleBoard')
            ->label('Quadro de horários')
            ->icon('heroicon-o-calendar-days')
            ->authorize($this->canAccess())
            ->modalHeading('Quadro de horários')
            ->modalWidth('7xl')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Fechar')
            ->modalContent(fn (): View => view('filament.components.schedule-board-content', [
                'classrooms' => $this->classrooms(),
                'classroomId' => $this->classroomId,
                'board' => $this->buildBoard(),
                'boards' => $this->buildAllBoards(),
            ]));

        if ($this->mode === 'sidebar') {
            $action
                ->color('gray')
                ->extraAttributes([
                    'class' => 'w-full !justify-start !text-left font-medium',
                ]);
        }

        return $action;
    }

    public function updatedClassroomId($value = null): void
    {
        if (blank($this->classroomId)) {
            $this->classroomId = null;

            return;
        }

        $this->classroomId = (int) $this->classroomId;

        if (
            ! Classroom::query()
                ->whereKey($this->classroomId)
                ->where('active', true)
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'classroomId' => 'Selecione uma sala ativa válida.',
            ]);
        }
    }

    public function render(): View
    {
        return view('livewire.filament.schedule-board-modal', [
            'canAccessBoard' => $this->canAccess(),
            'mode' => $this->mode,
        ]);
    }

    private function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && ($user->hasRole('super_admin') || $user->can('View:ScheduleBoard') || $user->can('View:Agenda'));
    }

    /**
     * @return Collection<int, Classroom>
     */
    private function classrooms(): Collection
    {
        abort_unless($this->canAccess(), 403);

        $tenantId = \Filament\Facades\Filament::getTenant()?->id;

        return Classroom::withoutGlobalScopes()
            ->where('active', true)
            ->when($tenantId !== null, fn (Builder $q) => $q->where(fn (Builder $sub) => $sub->where('school_id', $tenantId)->orWhereNull('school_id')))
            ->orderBy('name')
            ->get();
    }

    /**
     * @return list<array{
     *     label: string,
     *     cells: array<int, array{occupied: int, total: int, closed: bool, ratio: int}>
     * }>
     */
    private function buildBoard(?int $targetClassroomId = null): array
    {
        abort_unless($this->canAccess(), 403);

        $effectiveClassroomId = $targetClassroomId ?? $this->classroomId;
        $allClassrooms = $this->classrooms();

        if ($allClassrooms->isEmpty()) {
            return [];
        }

        $classrooms = $effectiveClassroomId
            ? $allClassrooms->where('id', $effectiveClassroomId)
            : $allClassrooms;

        if ($classrooms->isEmpty()) {
            return [];
        }

        $schedules = StudentSchedule::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->whereIn('classroom_id', $allClassrooms->modelKeys())
            ->whereHas('contractItem', fn (Builder $query): Builder => $query
                ->whereNull('deleted_at')
                ->whereNull('formation_completed_at')
                ->whereHas('contract', fn (Builder $contractQuery): Builder => $contractQuery
                    ->withoutGlobalScopes()
                    ->whereNull('deleted_at')
                    ->whereIn('status', ['draft', 'active'])))
            ->get(['classroom_id', 'weekday', 'start_hour', 'end_hour']);

        $firstHour = (int) $allClassrooms
            ->min(fn (Classroom $classroom): int => Carbon::parse($classroom->opening_time)->hour);
        $lastHour = (int) $allClassrooms
            ->max(fn (Classroom $classroom): int => Carbon::parse($classroom->closing_time)->hour) - 1;
        $lastHour = max($firstHour, $lastHour);
        $hours = range($firstHour, $lastHour);
        $weekdays = [1, 2, 3, 4, 5, 6, 0];
        $board = [];

        foreach ($hours as $hour) {
            $cells = [];
            $slotStart = sprintf('%02d:00:00', $hour);
            $slotEnd = sprintf('%02d:00:00', $hour + 1);

            foreach ($weekdays as $weekday) {
                $openClassrooms = $classrooms->filter(function (Classroom $classroom) use ($slotStart, $slotEnd): bool {
                    $open = Carbon::parse($classroom->opening_time)->format('H:i:s');
                    $close = Carbon::parse($classroom->closing_time)->format('H:i:s');

                    return $open <= $slotStart && $close >= $slotEnd;
                });
                $occupied = $schedules
                    ->filter(function (StudentSchedule $schedule) use ($weekday, $slotStart, $slotEnd, $openClassrooms): bool {
                        $start = Carbon::parse($schedule->start_hour)->format('H:i:s');
                        $end = Carbon::parse($schedule->end_hour)->format('H:i:s');

                        return $schedule->weekday === $weekday
                            && $openClassrooms->contains('id', $schedule->classroom_id)
                            && $start < $slotEnd
                            && $end > $slotStart;
                    })
                    ->count();

                $total = (int) $openClassrooms->sum('capacity');
                $ratio = $total > 0 ? (int) round(($occupied / $total) * 100) : 0;

                $cells[$weekday] = [
                    'occupied' => $occupied,
                    'total' => $total,
                    'closed' => $openClassrooms->isEmpty(),
                    'ratio' => $ratio,
                ];
            }

            $board[] = [
                'label' => sprintf('%02d:00 - %02d:00', $hour, $hour + 1),
                'cells' => $cells,
            ];
        }

        return $board;
    }

    /**
     * @return array<string, list<array{label: string, cells: array<int, array{occupied: int, total: int, closed: bool, ratio: int}>}>>
     */
    private function buildAllBoards(): array
    {
        $classrooms = $this->classrooms();
        $boards = [
            'all' => $this->buildBoard(null),
        ];

        foreach ($classrooms as $classroom) {
            $boards[(string) $classroom->id] = $this->buildBoard($classroom->id);
        }

        return $boards;
    }
}

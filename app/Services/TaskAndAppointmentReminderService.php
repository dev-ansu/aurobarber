<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\School;
use App\Models\SchoolAppointment;
use App\Models\TaskCard;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TaskAndAppointmentReminderService
{
    /**
     * Collects due-today, overdue, and pending tasks/appointments for a user.
     *
     * @return array{
     *     today_tasks: Collection<int, TaskCard>,
     *     overdue_tasks: Collection<int, TaskCard>,
     *     pending_tasks_count: int,
     *     today_appointments: Collection<int, SchoolAppointment>,
     *     overdue_appointments: Collection<int, SchoolAppointment>
     * }
     */
    public function summaryForUser(User $user, ?School $activeSchool = null): array
    {
        $today = today()->toDateString();

        $schoolIds = $user->isSuperAdmin()
            ? School::query()->withoutGlobalScopes()->pluck('id')->map(fn ($id) => (int) $id)->all()
            : $user->schools()->pluck('schools.id')->map(fn ($id) => (int) $id)->all();

        if ($activeSchool && ! in_array((int) $activeSchool->id, $schoolIds, true)) {
            $schoolIds[] = (int) $activeSchool->id;
        }

        $todayTasks = collect();
        $overdueTasks = collect();
        $pendingTasksCount = 0;

        if (Schema::hasTable('task_cards') && Schema::hasTable('task_columns') && Schema::hasTable('task_boards')) {
            $baseTaskQuery = TaskCard::query()
                ->with(['board.school', 'column'])
                ->whereNull('completed_at')
                ->whereHas('column', fn (Builder $cq) => $cq->where('is_done_column', false))
                ->whereHas('board', function (Builder $bq) use ($user, $schoolIds): void {
                    if (! $user->isSuperAdmin()) {
                        $bq->where(function (Builder $sub) use ($schoolIds): void {
                            $sub->whereNull('school_id');
                            if ($schoolIds !== []) {
                                $sub->orWhereIn('school_id', $schoolIds);
                            }
                        });
                    }
                })
                ->where(function (Builder $q) use ($user): void {
                    $q->where('assigned_to_all', true)
                        ->orWhere('created_by_user_id', $user->id)
                        ->orWhereHas('assignedUsers', fn (Builder $uq) => $uq->where('users.id', $user->id));
                });

            $pendingTasksCount = (clone $baseTaskQuery)->count();

            $todayTasks = (clone $baseTaskQuery)
                ->whereDate('due_date', $today)
                ->orderBy('sort_order')
                ->get();

            $overdueTasks = (clone $baseTaskQuery)
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<', $today)
                ->orderBy('due_date')
                ->get();
        }

        $todayAppointments = collect();
        $overdueAppointments = collect();

        if (Schema::hasTable('school_appointments')) {
            $baseApptQuery = SchoolAppointment::query()
                ->with('school')
                ->where('status', SchoolAppointment::STATUS_SCHEDULED)
                ->forUserOrShared((int) $user->id)
                ->when(! $user->isSuperAdmin(), function (Builder $q) use ($schoolIds): void {
                    $q->where(function (Builder $sub) use ($schoolIds): void {
                        $sub->whereNull('school_id');
                        if ($schoolIds !== []) {
                            $sub->orWhereIn('school_id', $schoolIds);
                        }
                    });
                });

            $todayAppointments = (clone $baseApptQuery)
                ->whereDate('date', $today)
                ->orderBy('start_time')
                ->get();

            $overdueAppointments = (clone $baseApptQuery)
                ->whereDate('date', '<', $today)
                ->orderBy('date')
                ->get();
        }

        return [
            'today_tasks' => $todayTasks,
            'overdue_tasks' => $overdueTasks,
            'pending_tasks_count' => $pendingTasksCount,
            'today_appointments' => $todayAppointments,
            'overdue_appointments' => $overdueAppointments,
        ];
    }

    /**
     * Sends a database notification (and optional toast) if the user has tasks/appointments
     * due today or overdue and has not yet been notified for the current daily item set.
     */
    public function notifyUserIfNeeded(User $user, ?School $activeSchool = null, bool $sendToast = false): bool
    {
        if (session('portal_auth_type') === 'student_contract') {
            return false;
        }

        if (! Schema::hasTable('notifications')) {
            return false;
        }

        $summary = $this->summaryForUser($user, $activeSchool);

        $todayCount = $summary['today_tasks']->count() + $summary['today_appointments']->count();
        $overdueCount = $summary['overdue_tasks']->count() + $summary['overdue_appointments']->count();

        if ($todayCount === 0 && $overdueCount === 0) {
            return false;
        }

        $todayKey = today()->toDateString();
        $signaturePayload = implode('|', [
            't_today:'.$summary['today_tasks']->pluck('id')->sort()->join(','),
            't_over:'.$summary['overdue_tasks']->pluck('id')->sort()->join(','),
            'a_today:'.$summary['today_appointments']->pluck('id')->sort()->join(','),
            'a_over:'.$summary['overdue_appointments']->pluck('id')->sort()->join(','),
        ]);
        $signature = substr(sha1($signaturePayload), 0, 16);
        $cacheKey = "daily_task_appt_reminder:{$user->id}:{$todayKey}:{$signature}";

        if (Cache::has($cacheKey)) {
            return false;
        }

        // Evita duplicidade mesmo se o cache for limpo
        $alreadyInDb = $user->notifications()
            ->whereDate('created_at', today())
            ->get()
            ->contains(fn ($n) => data_get($n->data, 'viewData.reminder_signature') === $signature);

        if ($alreadyInDb) {
            Cache::put($cacheKey, true, now()->endOfDay());

            return false;
        }

        $title = match (true) {
            $todayCount > 0 && $overdueCount > 0 => "🔔 Você tem {$todayCount} tarefa(s)/compromisso(s) para hoje e {$overdueCount} em atraso",
            $todayCount === 1 => '🔔 Você tem 1 tarefa ou compromisso para hoje!',
            $todayCount > 1 => "🔔 Você tem {$todayCount} tarefas/compromissos para hoje!",
            $overdueCount === 1 => '⚠️ Você tem 1 tarefa ou compromisso em atraso',
            default => "⚠️ Você tem {$overdueCount} tarefas/compromissos em atraso",
        };

        $lines = [];

        foreach ($summary['today_appointments']->take(2) as $appt) {
            $time = $appt->start_time ? substr((string) $appt->start_time, 0, 5).' ' : '';
            $lines[] = '📅 Hoje '.$time.'— '.Str::limit($appt->title, 50);
        }

        foreach ($summary['today_tasks']->take(2) as $task) {
            $lines[] = '📋 Hoje — '.Str::limit($task->title, 50);
        }

        foreach ($summary['overdue_appointments']->take(2) as $appt) {
            $lines[] = '⏰ Atrasado ('.$appt->date?->format('d/m').') — '.Str::limit($appt->title, 45);
        }

        foreach ($summary['overdue_tasks']->take(2) as $task) {
            $lines[] = '⏰ Atrasada ('.$task->due_date?->format('d/m').') — '.Str::limit($task->title, 45);
        }

        if ($summary['pending_tasks_count'] > ($summary['today_tasks']->count() + $summary['overdue_tasks']->count())) {
            $otherPending = $summary['pending_tasks_count'] - ($summary['today_tasks']->count() + $summary['overdue_tasks']->count());
            $lines[] = "📌 Além disso, há {$otherPending} tarefa(s) pendente(s) no seu quadro.";
        }

        $targetSchoolId = $activeSchool?->id
            ?? $user->schools()->value('schools.id')
            ?? School::query()->withoutGlobalScopes()->orderBy('id')->value('id');

        $agendaUrl = $targetSchoolId
            ? url("/admin/{$targetSchoolId}/agenda-compromissos")
            : url('/admin');

        $kanbanUrl = $targetSchoolId
            ? url("/admin/{$targetSchoolId}/tarefas-trello")
            : url('/admin');

        $notification = Notification::make()
            ->title($title)
            ->body(implode("\n", array_slice($lines, 0, 5)))
            ->icon($overdueCount > 0 ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-bell-alert')
            ->color($overdueCount > 0 ? 'warning' : 'info')
            ->viewData(['reminder_signature' => $signature])
            ->actions([
                Action::make('open_agenda')
                    ->label('Minha Agenda')
                    ->url($agendaUrl)
                    ->markAsRead(),
                Action::make('open_tasks')
                    ->label('Quadro de Tarefas')
                    ->url($kanbanUrl)
                    ->markAsRead(),
            ]);

        $notification->sendToDatabase($user);

        if ($sendToast) {
            $notification->send();
        }

        Cache::put($cacheKey, true, now()->endOfDay());

        return true;
    }
}

<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Services\TaskAndAppointmentReminderService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class SendDailyTaskRemindersCommand extends Command
{
    protected $signature = 'tasks:notify-daily';

    protected $description = 'Envia notificações diárias no painel sobre tarefas e compromissos para hoje ou em atraso.';

    public function handle(TaskAndAppointmentReminderService $reminderService): int
    {
        $users = User::query()
            ->where('panel_access', true)
            ->where(function (Builder $q): void {
                $q->whereHas('collaborator', fn (Builder $cq) => $cq->where('active', true))
                    ->orWhereHas('roles');
            })
            ->get();

        $notified = 0;

        foreach ($users as $user) {
            if ($reminderService->notifyUserIfNeeded($user, null, false)) {
                $notified++;
            }
        }

        $this->info("Lembretes diários processados. Usuários notificados: {$notified}.");

        return self::SUCCESS;
    }
}

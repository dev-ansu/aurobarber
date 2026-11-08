<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('attendance:generate-daily')
    ->dailyAt('00:05')
    ->withoutOverlapping();

Schedule::command('tasks:notify-daily')
    ->dailyAt('07:30')
    ->withoutOverlapping();

Schedule::command('system:backup')
    ->twiceDaily(12, 23)
    ->withoutOverlapping();

// Boletim Executivo Semanal de Diretoria (todo domingo às 20:00)
Schedule::command('school:send-weekly-digest')
    ->weeklyOn(0, '20:00')
    ->withoutOverlapping();

// Rotina Diária de Backup Automatizado no Cloud SaaS (Base Global e Schemas dos Tenants às 03:00 com retenção de 7 dias)
Schedule::command('saas:backup-database --scope=all --keep=7')
    ->dailyAt('03:00')
    ->withoutOverlapping();

// Check-up Clínico Preventivo do AuroDoctor (antes do início dos 3 turnos: manhã, tarde e noite)
Schedule::command('aurosoft:doctor --notify')
    ->dailyAt('07:30')
    ->withoutOverlapping();

Schedule::command('aurosoft:doctor --notify')
    ->dailyAt('13:30')
    ->withoutOverlapping();

Schedule::command('aurosoft:doctor --notify')
    ->dailyAt('19:30')
    ->withoutOverlapping();

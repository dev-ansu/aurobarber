<?php

namespace App\Console\Commands;

use App\Actions\GenerateStudentAttendancesForDate;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

#[Signature('attendance:generate-daily {--date= : Data a gerar no formato AAAA-MM-DD (padrão: hoje)}')]
#[Description('Gera as chamadas dos horários semanais ativos para uma data')]
class GenerateDailyStudentAttendances extends Command
{
    public function handle(GenerateStudentAttendancesForDate $generator): int
    {
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))
            : Carbon::today();

        $createdCount = $generator->execute($date);

        $this->info("Chamadas geradas: {$createdCount}.");

        return self::SUCCESS;
    }
}

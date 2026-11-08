<?php

namespace App\Filament\Exports;

use App\Models\StudentSchedule;

class StudentScheduleExporter extends ConfiguredResourceExporter
{
    protected static ?string $model = StudentSchedule::class;

    protected static array $columns = [
        'contractItem.contract.contract_number' => 'Contrato',
        'contractItem.contract.student.name' => 'Aluno',
        'contractItem.formation.name' => 'Formação', 'classroom.name' => 'Sala',
        'weekday' => 'Dia da semana', 'start_hour' => 'Início', 'end_hour' => 'Término',
    ];
}

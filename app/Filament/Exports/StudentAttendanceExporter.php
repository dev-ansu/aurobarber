<?php

namespace App\Filament\Exports;

use App\Models\StudentAttendance;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;

class StudentAttendanceExporter extends Exporter
{
    protected static ?string $model = StudentAttendance::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('attendance_date')
                ->label('Data')
                ->formatStateUsing(fn ($state): string => $state?->format('d/m/Y') ?? ''),
            ExportColumn::make('session.start_hour')
                ->label('Início')
                ->formatStateUsing(fn (?string $state): string => $state ? substr($state, 0, 5) : ''),
            ExportColumn::make('session.end_hour')
                ->label('Término')
                ->formatStateUsing(fn (?string $state): string => $state ? substr($state, 0, 5) : ''),
            ExportColumn::make('studentSchedule.contractItem.contract.contract_number')
                ->label('Contrato')
                ->preventFormulaInjection(),
            ExportColumn::make('studentSchedule.contractItem.contract.student.name')
                ->label('Aluno')
                ->preventFormulaInjection(),
            ExportColumn::make('studentSchedule.contractItem.formation.name')
                ->label('Formação')
                ->preventFormulaInjection(),
            ExportColumn::make('session.classroom.name')
                ->label('Sala')
                ->preventFormulaInjection(),
            ExportColumn::make('status')
                ->label('Presença')
                ->formatStateUsing(fn (string $state): string => $state === 'present' ? 'Presente' : 'Falta'),
            ExportColumn::make('markedBy.name')
                ->label('Registrado por')
                ->state(fn (StudentAttendance $record): string => $record->markedBy?->name
                    ?? ($record->marking_source === 'kiosk' ? 'Kiosk do aluno' : 'Aguardando confirmação'))
                ->preventFormulaInjection(),
            ExportColumn::make('marked_at')
                ->label('Registrado em')
                ->formatStateUsing(fn ($state): string => $state?->format('d/m/Y H:i') ?? ''),
            ExportColumn::make('note')
                ->label('Observação')
                ->preventFormulaInjection(),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = "A exportação de presenças e faltas foi concluída: {$export->successful_rows} registro(s) exportado(s).";

        return $export->getFailedRowsCount()
            ? $body." {$export->getFailedRowsCount()} registro(s) não puderam ser exportados."
            : $body;
    }

    public function getFileName(Export $export): string
    {
        return "frequencia-aluno-{$export->getKey()}";
    }

    public static function getCsvDelimiter(): string
    {
        return ';';
    }

    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with([
            'session.classroom',
            'studentSchedule.contractItem.contract.student',
            'studentSchedule.contractItem.formation',
            'markedBy.person',
        ]);
    }
}

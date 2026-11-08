<?php

namespace App\Filament\Resources\StudentSchedules\Pages;

use App\Filament\Resources\StudentSchedules\StudentScheduleResource;
use App\Models\StudentSchedule;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ManageStudentSchedules extends ManageRecords
{
    protected static string $resource = StudentScheduleResource::class;

    public function getTitle(): string
    {
        return 'Horários dos Alunos (Alocação de Salas e Laboratórios)';
    }

    public function getSubheading(): ?string
    {
        return 'Mapa geral de ocupação dos laboratórios e horários fixos de aula dos alunos matriculados. Use esta visão para acompanhar a capacidade das salas e saber quais alunos devem comparecer a cada dia. Para alocar ou alterar os horários de um aluno, acesse o Contrato correspondente.';
    }

    public function getTabs(): array
    {
        $todayWeekday = now()->dayOfWeek;

        return [
            'today' => Tab::make('📅 Aulas de Hoje')
                ->badge(StudentSchedule::query()->where('weekday', $todayWeekday)->count())
                ->badgeColor('amber')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('weekday', $todayWeekday)),

            'all' => Tab::make('Todos os Horários')
                ->badge(StudentSchedule::query()->count()),

            'morning' => Tab::make('🌅 Manhã')
                ->badge(StudentSchedule::query()->whereTime('start_hour', '<', '12:00:00')->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereTime('start_hour', '<', '12:00:00')),

            'afternoon' => Tab::make('☀️ Tarde')
                ->badge(StudentSchedule::query()->whereTime('start_hour', '>=', '12:00:00')->whereTime('start_hour', '<', '18:00:00')->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereTime('start_hour', '>=', '12:00:00')->whereTime('start_hour', '<', '18:00:00')),

            'night' => Tab::make('🌙 Noite')
                ->badge(StudentSchedule::query()->whereTime('start_hour', '>=', '18:00:00')->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereTime('start_hour', '>=', '18:00:00')),

            'saturday' => Tab::make('🗓️ Sábados')
                ->badge(StudentSchedule::query()->where('weekday', 6)->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('weekday', 6)),
        ];
    }
}

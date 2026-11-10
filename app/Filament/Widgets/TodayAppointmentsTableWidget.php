<?php

namespace App\Filament\Widgets;

use App\Models\Appointment;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TodayAppointmentsTableWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    protected function getTableHeading(): string
    {
        return 'Agendamentos de Hoje';
    }

    public function table(Table $table): Table
    {
        $tenant = filament()->getTenant();

        return $table
            ->query(
                Appointment::query()
                    ->where('barbershop_id', $tenant?->id ?? 0)
                    ->when(
                        auth()->user()?->isRestrictedBarber(),
                        fn ($q) => $q->where('professional_id', auth()->user()->resolveProfessionalId($tenant?->id) ?? 0)
                    )
                    ->whereDate('scheduled_at', now()->format('Y-m-d'))
                    ->with(['professional', 'service', 'services'])
                    ->orderBy('scheduled_at')
            )
            ->columns([
                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label('Horário')
                    ->time('H:i')
                    ->weight('bold')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('client_name')
                    ->label('Cliente')
                    ->weight('bold')
                    ->searchable(),

                Tables\Columns\TextColumn::make('professional.name')
                    ->label('Barbeiro')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('services_summary')
                    ->label('Serviço(s)'),

                Tables\Columns\TextColumn::make('service_price')
                    ->label('Valor')
                    ->money('BRL')
                    ->weight('bold')
                    ->color('success'),

                Tables\Columns\TextColumn::make('status_label')
                    ->label('Status')
                    ->badge()
                    ->color(fn (Appointment $record): string => match ($record->status) {
                        'scheduled' => 'warning',
                        'confirmed' => 'info',
                        'in_progress' => 'purple',
                        'completed' => 'success',
                        'no_show' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->headerActions([
                Action::make('openSchedule')
                    ->label(fn (): string => auth()->user()?->isRestrictedBarber()
                        ? 'Abrir Minha Agenda Diária ➔'
                        : 'Abrir Agenda Diária da Equipe ➔')
                    ->url(fn (): string => filament()->getTenant() ? url('/admin/' . filament()->getTenant()->slug . '/daily-schedule') : url('/admin'))
                    ->color('primary'),
            ])
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5);
    }
}

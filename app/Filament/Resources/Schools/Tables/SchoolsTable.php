<?php

namespace App\Filament\Resources\Schools\Tables;

use App\Filament\Exports\SchoolExporter;
use App\Models\School;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class SchoolsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('legal_name')
                    ->label('Razão social')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('trade_name')
                    ->label('Nome fantasia')
                    ->placeholder('—')
                    ->description(fn (School $record): ?string => $record->is_headquarters ? '🏛️ Unidade Matriz' : null),
                \Filament\Tables\Columns\IconColumn::make('is_headquarters')
                    ->label('Matriz')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('cnpj')
                    ->label('CNPJ')
                    ->copyable(),
                TextColumn::make('city')
                    ->label('Município')
                    ->formatStateUsing(fn (?string $state, $record): string => collect([
                        $state,
                        $record->state,
                    ])->filter()->join(' - '))
                    ->placeholder('—'),
                TextColumn::make('partners_count')
                    ->label('Sócios')
                    ->counts('partners'),
            ])
            ->filters([
                SelectFilter::make('state')->label('Estado')->options(fn (): array => School::query()
                    ->whereNotNull('state')->distinct()->orderBy('state')->pluck('state', 'state')->all()),
                SelectFilter::make('city')->label('Município')->options(fn (): array => School::query()
                    ->whereNotNull('city')->distinct()->orderBy('city')->pluck('city', 'city')->all()),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([])
            ->headerActions([
                ExportAction::make()->label('Exportar cadastro da escola')->exporter(SchoolExporter::class)
                    ->formats([ExportFormat::Csv, ExportFormat::Xlsx])
                    ->authorize(fn (): bool => Auth::user()?->getAllPermissions()->contains('name', 'Export:School') ?? false)
                    ->visible(fn (): bool => Auth::user()?->getAllPermissions()->contains('name', 'Export:School') ?? false),
            ]);
    }
}

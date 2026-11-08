<?php

namespace App\Filament\Resources\Formations\Tables;

use App\Actions\ToggleStatusAction;
use App\Filament\Exports\FormationExporter;
use App\Filters\ActiveStatusFilter;
use App\Models\Formation;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class FormationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image_path')
                    ->label('Capa')
                    ->disk('public')
                    ->square()
                    ->defaultImageUrl(asset('images/formation-placeholder.svg'))
                    ->size(46),
                TextColumn::make('name')
                    ->label('Formação')
                    ->tooltip(fn (Formation $record) => $record->description ?? null)
                    ->description(fn (Formation $record) => ($record->highlight_badge ? "⭐ {$record->highlight_badge} &middot; " : '') . ($record->active ? 'Ativo' : 'Inativo'))
                    ->searchable(),
                TextColumn::make('courses_count')
                    ->counts('courses')
                    ->label('Módulos / Cursos')
                    ->badge()
                    ->color('primary')
                    ->sortable(),
                TextColumn::make('total_workload')
                    ->label('Carga Horária')
                    ->formatStateUsing(fn ($state) => $state ? "{$state}h" : '-')
                    ->sortable(),
                IconColumn::make('show_in_showcase')
                    ->label('Na Vitrine')
                    ->boolean()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                ActiveStatusFilter::make('active'),
                TernaryFilter::make('courses_count')->label('Cursos vinculados')->queries(
                    true: fn ($query) => $query->has('courses'),
                    false: fn ($query) => $query->doesntHave('courses'),
                    blank: fn ($query) => $query,
                ),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                ToggleStatusAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                ExportAction::make()->label('Exportar formações')->exporter(FormationExporter::class)
                    ->formats([ExportFormat::Csv, ExportFormat::Xlsx])
                    ->authorize(fn (): bool => Auth::user()?->getAllPermissions()->contains('name', 'Export:Formation') ?? false)
                    ->visible(fn (): bool => Auth::user()?->getAllPermissions()->contains('name', 'Export:Formation') ?? false),
            ]);
    }
}

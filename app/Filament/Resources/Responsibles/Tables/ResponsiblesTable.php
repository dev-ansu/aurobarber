<?php

namespace App\Filament\Resources\Responsibles\Tables;

use App\Enums\RelationshipEnum;
use App\Filament\Exports\ResponsibleExporter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ResponsiblesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('person.name')
                    ->label('Nome')
                    ->searchable(),
                TextColumn::make('relationship')
                    ->label('Parentesco')
                    ->badge()
                    ->searchable(),
                IconColumn::make('financial_responsible')
                    ->label('Responsável financeiro')
                    ->boolean(),
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
                TernaryFilter::make('financial_responsible')->label('Responsabilidade financeira')
                    ->trueLabel('Financeiros')->falseLabel('Não financeiros')->placeholder('Todos'),
                SelectFilter::make('relationship')->label('Parentesco')->options(collect(RelationshipEnum::cases())
                    ->mapWithKeys(fn (RelationshipEnum $relationship): array => [$relationship->value => $relationship->getLabel()])
                    ->all())->searchable(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                ExportAction::make()->label('Exportar responsáveis')->exporter(ResponsibleExporter::class)
                    ->formats([ExportFormat::Csv, ExportFormat::Xlsx])
                    ->authorize(fn (): bool => Auth::user()?->getAllPermissions()->contains('name', 'Export:Responsible') ?? false)
                    ->visible(fn (): bool => Auth::user()?->getAllPermissions()->contains('name', 'Export:Responsible') ?? false),
            ]);
    }
}

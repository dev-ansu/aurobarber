<?php

namespace App\Filament\Resources\ContractTypes\Tables;

use App\Filament\Exports\ContractTypeExporter;
use App\Models\ContractType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ContractTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('document_type')
                    ->label('Tipo de Documento')
                    ->formatStateUsing(fn (?string $state): string => ContractType::DOCUMENT_TYPES[$state] ?? ($state ?: 'Contrato de Matrícula'))
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'cancellation' => 'danger',
                        'suspension' => 'warning',
                        'image_rights' => 'info',
                        'attendance' => 'gray',
                        default => 'success',
                    })
                    ->sortable()
                    ->searchable(),

                TextColumn::make('name')
                    ->label('Título do Modelo')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn ($record): ?string => $record->is_active ? '✅ Minuta vigente padrão' : 'Histórico arquivado'),

                TextColumn::make('version')
                    ->label('Versão / Ano')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Vigente')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('contracts_count')
                    ->label('Contratos Vinculados')
                    ->counts('contracts')
                    ->badge()
                    ->color('success')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('document_type')
                    ->label('Tipo de Documento')
                    ->options(ContractType::DOCUMENT_TYPES),

                TernaryFilter::make('is_active')
                    ->label('Status de Ativação')
                    ->placeholder('Todos os modelos')
                    ->trueLabel('Apenas Ativos')
                    ->falseLabel('Apenas Inativos'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->before(function (DeleteBulkAction $action, $records) {
                            foreach ($records as $record) {
                                if ($record->contracts()->exists()) {
                                    Notification::make()
                                        ->title('Ação em lote interrompida')
                                        ->body("O tipo de contrato '{$record->name}' possui vínculos ativos e não foi excluído.")
                                        ->danger()
                                        ->send();

                                    $action->halt();
                                }
                            }
                        }),
                ]),
            ])
            ->headerActions([
                ExportAction::make()->label('Exportar modelos de documentos')->exporter(ContractTypeExporter::class)
                    ->formats([ExportFormat::Csv, ExportFormat::Xlsx])
                    ->authorize(fn (): bool => Auth::user()?->getAllPermissions()->contains('name', 'Export:ContractType') ?? false)
                    ->visible(fn (): bool => Auth::user()?->getAllPermissions()->contains('name', 'Export:ContractType') ?? false),
            ]);
    }
}

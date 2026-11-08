<?php

namespace App\Filament\Resources\Courses\Tables;

use App\Filament\Exports\CourseExporter;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class CoursesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Curso')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('workload')
                    ->label('Carga Horária')
                    ->sortable()
                    ->searchable(),
                IconColumn::make('active')
                    ->label('Ativo')
                    ->boolean(),
                TextColumn::make('ouro_moderno_id')
                    ->label('ID Ouro Moderno')
                    ->sortable()
                    ->searchable(),
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
                TernaryFilter::make('active')->label('Situação')->trueLabel('Ativos')->falseLabel('Inativos')->placeholder('Todos'),
                TernaryFilter::make('ouro_moderno_id')->label('Origem')->trueLabel('Da Ouro Moderno')->falseLabel('Cadastrados localmente')->placeholder('Todas as origens')->queries(
                    true: fn ($query) => $query->whereNotNull('ouro_moderno_id'),
                    false: fn ($query) => $query->whereNull('ouro_moderno_id'),
                ),
            ])
            ->recordActions([
                Action::make('lms_studio')
                    ->label('Estúdio LMS')
                    ->icon('heroicon-o-academic-cap')
                    ->color('primary')
                    ->url(fn ($record) => route('filament.studio.pages.lms-studio', ['course_id' => $record->id]))
                    ->visible(fn ($record) => $record->isAurosoftLms() && \App\Filament\Studio\Pages\LmsStudio::canAccess()),
                Action::make('exportCourseJson')
                    ->label('Exportar Pacote')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(function (\App\Models\Course $record, \App\Services\LmsContentBackupService $backupService) {
                        $package = $backupService->exportCourse($record);
                        $slug = \Illuminate\Support\Str::slug($record->name);
                        $filename = "curso-{$slug}-lms.json";

                        return response()->streamDownload(function () use ($package) {
                            echo json_encode($package, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        }, $filename, ['Content-Type' => 'application/json']);
                    }),
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn ($record) => $record->isFromOuroModerno()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                Action::make('backupLmsContent')
                    ->label('Backup do LMS (JSON)')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function (\App\Services\LmsContentBackupService $backupService) {
                        $data = $backupService->exportAll();
                        $filename = 'aurosoft-lms-backup-' . now()->format('Y-m-d-His') . '.json';

                        return response()->streamDownload(function () use ($data) {
                            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        }, $filename, ['Content-Type' => 'application/json']);
                    }),
                Action::make('importLmsBackup')
                    ->label('Importar Conteúdo LMS')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('warning')
                    ->form([
                        \Filament\Forms\Components\FileUpload::make('backup_file')
                            ->label('Arquivo de Backup LMS (.json)')
                            ->acceptedFileTypes(['application/json', 'text/plain', 'application/octet-stream'])
                            ->required()
                            ->disk('local')
                            ->directory('temp-lms-imports'),
                    ])
                    ->action(function (array $data, \App\Services\LmsContentBackupService $backupService) {
                        $path = storage_path('app/' . $data['backup_file']);
                        $stats = $backupService->restoreFromFile($path);
                        \Illuminate\Support\Facades\File::delete($path);

                        \Filament\Notifications\Notification::make()
                            ->title('Backup LMS restaurado com sucesso!')
                            ->body("{$stats['courses']} cursos, {$stats['modules']} módulos, {$stats['lessons']} lições e {$stats['steps']} passos sincronizados.")
                            ->success()
                            ->send();
                    }),
                ExportAction::make()->label('Exportar cursos')->exporter(CourseExporter::class)
                    ->formats([ExportFormat::Csv, ExportFormat::Xlsx])
                    ->authorize(fn (): bool => Auth::user()?->getAllPermissions()->contains('name', 'Export:Course') ?? false)
                    ->visible(fn (): bool => Auth::user()?->getAllPermissions()->contains('name', 'Export:Course') ?? false),
            ]);
    }
}

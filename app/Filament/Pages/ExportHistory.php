<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\Exports\Models\Export;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use UnitEnum;

class ExportHistory extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $title = 'Histórico de Exportações do Sistema';

    protected static string|UnitEnum|null $navigationGroup = 'Relatórios';

    protected static ?string $navigationLabel = 'Minhas exportações';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-down-tray';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.export-history';

    public static function canAccess(): bool
    {
        return Auth::user()?->getAllPermissions()->contains('name', 'ViewAny:Export') ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Export::query()
                ->where('user_id', Auth::id())
                ->latest())
            ->columns([
                TextColumn::make('exporter')
                    ->label('Tipo de exportação')
                    ->formatStateUsing(fn (string $state): string => class_basename($state))
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Solicitada em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('completed_at')
                    ->label('Concluída em')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Em processamento'),
                TextColumn::make('successful_rows')
                    ->label('Registros exportados')
                    ->numeric(),
                TextColumn::make('status')
                    ->label('Situação')
                    ->state(fn (Export $record): string => $record->completed_at ? 'Concluída' : 'Em processamento')
                    ->badge()
                    ->color(fn (Export $record): string => $record->completed_at ? 'success' : 'warning'),
            ])
            ->recordActions([
                Action::make('download_csv')
                    ->label('Baixar CSV')
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn (Export $record): string => self::downloadUrl($record, ExportFormat::Csv))
                    ->openUrlInNewTab()
                    ->authorize(fn (Export $record): bool => self::canDownload($record))
                    ->visible(fn (Export $record): bool => self::canDownload($record)),
                Action::make('download_xlsx')
                    ->label('Baixar Excel')
                    ->icon('heroicon-o-table-cells')
                    ->url(fn (Export $record): string => self::downloadUrl($record, ExportFormat::Xlsx))
                    ->openUrlInNewTab()
                    ->authorize(fn (Export $record): bool => self::canDownload($record))
                    ->visible(fn (Export $record): bool => self::canDownload($record)),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Nenhuma exportação encontrada')
            ->emptyStateDescription('Os arquivos gerados por você aparecerão aqui.');
    }

    private static function canDownload(Export $export): bool
    {
        return $export->completed_at !== null
            && (int) $export->user_id === (int) Auth::id()
            && (Auth::user()?->getAllPermissions()->contains('name', 'Download:Export') ?? false)
            && Storage::disk($export->file_disk)->exists($export->getFileDirectory().'/headers.csv');
    }

    private static function downloadUrl(Export $export, ExportFormat $format): string
    {
        return URL::signedRoute('filament.exports.download', [
            'authGuard' => filament()->getAuthGuard(),
            'export' => $export,
            'format' => $format,
        ], absolute: false);
    }
}

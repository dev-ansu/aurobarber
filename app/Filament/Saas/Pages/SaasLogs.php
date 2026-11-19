<?php

declare(strict_types=1);

namespace App\Filament\Saas\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\File;
use Illuminate\Support\HtmlString;
use UnitEnum;

class SaasLogs extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-magnifying-glass';

    protected static ?string $navigationLabel = 'Logs & Diagnóstico';

    protected static ?string $title = 'Logs Técnicos & Diagnóstico do Sistema';

    protected static string|UnitEnum|null $navigationGroup = 'Configurações SaaS';

    protected static ?int $navigationSort = 15;

    protected static ?string $slug = 'logs';

    protected string $view = 'filament.saas.pages.logs';

    public static function canAccess(): bool
    {
        return auth()->user()?->role === 'master_admin';
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): array => $this->getLogFiles())
            ->columns([
                TextColumn::make('name')
                    ->label('Arquivo de Log')
                    ->fontFamily('mono')
                    ->weight('bold'),

                TextColumn::make('size')
                    ->label('Tamanho'),

                TextColumn::make('modified_at')
                    ->label('Última Modificação'),

                TextColumn::make('active')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Log do Dia / Ativo' : 'Arquivado')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
            ])
            ->recordActions([
                Action::make('inspectLog')
                    ->label('Inspecionar Últimas Linhas')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(fn (array $record): string => 'Log: '.$record['name'])
                    ->modalWidth(Width::FiveExtraLarge)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fechar')
                    ->modalContent(function (array $record): HtmlString {
                        $content = e($this->readTail($record['name']));

                        return new HtmlString(
                            '<pre class="max-h-[65vh] overflow-auto whitespace-pre-wrap break-words rounded-xl border border-gray-200 bg-gray-50 p-4 font-mono text-xs leading-relaxed text-gray-900 dark:border-white/10 dark:bg-gray-950 dark:text-gray-100">'
                            .($content !== '' ? $content : 'Log vazio (sem ocorrências registradas).')
                            .'</pre>'
                        );
                    }),

                Action::make('clearLog')
                    ->label(fn (array $record): string => $record['active'] ? 'Limpar' : 'Excluir')
                    ->icon('heroicon-m-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading(fn (array $record): string => $record['active'] ? 'Limpar log atual?' : 'Excluir arquivo de log?')
                    ->action(function (array $record): void {
                        $this->deleteOrTruncateLog($record['name']);
                    }),
            ]);
    }

    /**
     * @return array<string, array{name: string, size: string, modified_at: string, active: bool}>
     */
    private function getLogFiles(): array
    {
        $dir = storage_path('logs');
        if (! File::isDirectory($dir)) {
            return [];
        }

        $todayLog = 'laravel-'.now()->format('Y-m-d').'.log';

        return collect(File::files($dir))
            ->filter(fn ($file): bool => str_ends_with($file->getFilename(), '.log'))
            ->sortByDesc(fn ($file): int => $file->getMTime())
            ->mapWithKeys(function ($file) use ($todayLog): array {
                $name = $file->getFilename();
                $bytes = $file->getSize();
                $size = $bytes < 1048576
                    ? round($bytes / 1024, 1).' KB'
                    : round($bytes / 1048576, 2).' MB';

                return [
                    $name => [
                        'name' => $name,
                        'size' => $size,
                        'modified_at' => date('d/m/Y H:i:s', $file->getMTime()),
                        'active' => $name === $todayLog || $name === 'laravel.log',
                    ],
                ];
            })
            ->all();
    }

    private function readTail(string $filename, int $maxBytes = 65536): string
    {
        $clean = basename($filename);
        $path = storage_path('logs/'.$clean);
        if (! File::isFile($path)) {
            return '';
        }

        $size = File::size($path);
        if ($size === 0) {
            return '';
        }

        $handle = fopen($path, 'rb');
        if (! is_resource($handle)) {
            return '';
        }

        try {
            $offset = max(0, $size - $maxBytes);
            if ($offset > 0) {
                fseek($handle, $offset);
            }
            $data = (string) stream_get_contents($handle, $maxBytes);
            $lines = preg_split('/\r\n|\n|\r/', $data) ?: [];

            return implode(PHP_EOL, array_slice($lines, -200));
        } finally {
            fclose($handle);
        }
    }

    private function deleteOrTruncateLog(string $filename): void
    {
        $clean = basename($filename);
        $path = storage_path('logs/'.$clean);
        if (! File::isFile($path)) {
            return;
        }

        $todayLog = 'laravel-'.now()->format('Y-m-d').'.log';
        if ($clean === $todayLog || $clean === 'laravel.log') {
            File::put($path, '');
            Notification::make()->title('Log atual limpo com sucesso!')->success()->send();
        } else {
            File::delete($path);
            Notification::make()->title('Arquivo de log excluído!')->success()->send();
        }
    }
}

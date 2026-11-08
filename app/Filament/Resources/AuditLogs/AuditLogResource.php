<?php

namespace App\Filament\Resources\AuditLogs;

use App\Filament\Exports\AuditLogExporter;
use App\Filament\Resources\AuditLogs\Pages\ManageAuditLogs;
use App\Models\AuditLog;
use App\Models\CashRegister;
use App\Models\CashTransaction;
use App\Models\Contract;
use App\Models\Installment;
use App\Models\Person;
use App\Models\School;
use App\Models\User;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class AuditLogResource extends Resource
{
    protected static bool $isScopedToTenant = false;

    protected static ?string $model = AuditLog::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Relatórios';

    protected static ?string $navigationLabel = 'Auditoria';

    protected static ?string $modelLabel = 'registro de auditoria';

    protected static ?string $pluralModelLabel = 'auditoria';

    protected static ?int $navigationSort = 4;

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginationPageOptions([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->columns([
                TextColumn::make('created_at')
                    ->label('Data e hora')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('contract.contract_number')
                    ->label('Contrato')
                    ->placeholder(fn (AuditLog $record): string => data_get($record->changes, 'before.contract_number', '—'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('contract.student.name')
                    ->label('Aluno')
                    ->placeholder(fn (AuditLog $record): string => data_get($record->changes, 'before.name') ?? data_get($record->changes, 'after.name', '—'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('record_label')
                    ->label('Registro')
                    ->state(fn (AuditLog $record): string => $record->recordLabel())
                    ->description(fn (AuditLog $record): string => "#{$record->auditable_id}")
                    ->wrap(),
                TextColumn::make('event_label')
                    ->label('Ação')
                    ->state(fn (AuditLog $record): string => $record->actionLabel())
                    ->badge()
                    ->color(fn (AuditLog $record): string => match ($record->event) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        'restored' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('user.person.name')
                    ->label('Operador')
                    ->placeholder('Sistema / usuário removido')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('changes')
                    ->label('Alterações')
                    ->formatStateUsing(fn (mixed $state): string => is_array($state)
                        ? (json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '—')
                        : (filled($state) ? (string) $state : '—'))
                    ->limit(80)
                    ->tooltip(fn (mixed $state): ?string => is_array($state)
                        ? (json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: null)
                        : (filled($state) ? (string) $state : null))
                    ->copyable()
                    ->wrap()
                    ->toggleable(),
            ])
            ->filters([
                Filter::make('period')
                    ->label('Período')
                    ->schema([
                        DatePicker::make('from')
                            ->label('De')
                            ->default(now()->startOfMonth()),
                        DatePicker::make('until')
                            ->label('Até')
                            ->default(now()->endOfMonth()),
                    ])
                    ->columns(2)
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date))),
                SelectFilter::make('user_id')
                    ->label('Operador')
                    ->options(fn (): array => User::query()
                        ->withoutTechnicalAdmin()
                        ->with('person')
                        ->orderBy('email')
                        ->limit(250)
                        ->get()
                        ->mapWithKeys(fn (User $user): array => [$user->id => $user->name ?? $user->email])
                        ->all())
                    ->searchable()
                    ->preload(),
                SelectFilter::make('event')
                    ->label('Ação')
                    ->options([
                        'created' => 'Criação',
                        'updated' => 'Alteração',
                        'deleted' => 'Remoção',
                        'restored' => 'Restauração',
                    ]),
            ])
            ->headerActions([
                ExportAction::make()
                    ->label('Exportar resultados')
                    ->authorize(fn (): bool => auth()->user()?->getAllPermissions()->contains('name', 'Export:AuditLog') ?? false)
                    ->visible(fn (): bool => auth()->user()?->getAllPermissions()->contains('name', 'Export:AuditLog') ?? false)
                    ->exporter(AuditLogExporter::class)
                    ->formats([ExportFormat::Csv, ExportFormat::Xlsx])
                    ->options(fn (): array => [
                        'school' => (Filament::getTenant() instanceof School
                            ? Filament::getTenant()->loadMissing('partners')
                            : School::query()->with('partners')->latest('id')->first())?->toContractSnapshot() ?? [],
                    ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $tenantId = Filament::getTenant()?->id;

        return parent::getEloquentQuery()
            ->with(['contract' => fn ($q) => $q->withoutGlobalScopes()->with('student'), 'user.person', 'auditable'])
            ->when($tenantId, function (Builder $query) use ($tenantId): void {
                $query->where(function (Builder $q) use ($tenantId): void {
                    $q->where('school_id', $tenantId)
                        ->orWhereNull('school_id')
                        ->orWhereHas('contract', fn (Builder $cq) => $cq->withoutGlobalScopes()->where('school_id', $tenantId))
                        ->orWhere(function (Builder $sub) use ($tenantId): void {
                            $sub->where('auditable_type', Contract::class)
                                ->whereIn('auditable_id', Contract::query()->withoutGlobalScopes()->withTrashed()->where('school_id', $tenantId)->select('id'));
                        })
                        ->orWhere(function (Builder $sub) use ($tenantId): void {
                            $sub->where('auditable_type', Installment::class)
                                ->whereIn('auditable_id', Installment::query()->withoutGlobalScopes()->where('school_id', $tenantId)->select('id'));
                        })
                        ->orWhere(function (Builder $sub) use ($tenantId): void {
                            $sub->where('auditable_type', CashRegister::class)
                                ->whereIn('auditable_id', CashRegister::query()->withoutGlobalScopes()->where('school_id', $tenantId)->select('id'));
                        })
                        ->orWhere(function (Builder $sub) use ($tenantId): void {
                            $sub->where('auditable_type', CashTransaction::class)
                                ->whereIn('auditable_id', CashTransaction::query()->whereIn(
                                    'cash_register_id',
                                    CashRegister::query()->withoutGlobalScopes()->where('school_id', $tenantId)->select('id')
                                )->select('id'));
                        });
                });
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAuditLogs::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}

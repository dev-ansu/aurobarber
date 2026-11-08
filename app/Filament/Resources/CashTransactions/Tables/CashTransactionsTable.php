<?php

namespace App\Filament\Resources\CashTransactions\Tables;

use App\Filament\Exports\CashTransactionExporter;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class CashTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('register.name')
                    ->label('Caixa Origem')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('user.name')
                    ->label('Operador')
                    ->searchable(),

                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'input' => 'success',
                        'output' => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'input' => 'Entrada (+)',
                        'output' => 'Saída (-)',
                    })
                    ->searchable(),

                TextColumn::make('amount')
                    ->label('Valor')
                    ->money('BRL')
                    ->sortable(),

                TextColumn::make('paymentMethod.name')
                    ->label('Forma de Pagto')
                    ->badge()
                    ->color('gray')
                    ->searchable(),

                TextColumn::make('description')
                    ->label('Descrição / Motivo')
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label('Realizado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Última alteração')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')->label('Tipo')->options(['input' => 'Entrada', 'output' => 'Saída']),
                SelectFilter::make('register_id')->label('Caixa')->relationship('register', 'name')->searchable()->preload(),
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
                SelectFilter::make('payment_method_id')->label('Forma de pagamento')->relationship('paymentMethod', 'name')->searchable()->preload(),
                Filter::make('created_at')->label('Período')->schema([
                    DatePicker::make('from')->label('De'), DatePicker::make('until')->label('Até'),
                ])->columns(2)->query(fn (Builder $query, array $data): Builder => $query
                    ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date))
                    ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date))),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                ExportAction::make()->label('Exportar movimentações')->exporter(CashTransactionExporter::class)
                    ->formats([ExportFormat::Csv, ExportFormat::Xlsx])
                    ->authorize(fn (): bool => Auth::user()?->getAllPermissions()->contains('name', 'Export:CashTransaction') ?? false)
                    ->visible(fn (): bool => Auth::user()?->getAllPermissions()->contains('name', 'Export:CashTransaction') ?? false),
            ]);
    }
}

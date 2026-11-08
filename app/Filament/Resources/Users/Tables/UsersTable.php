<?php

namespace App\Filament\Resources\Users\Tables;

use App\Filament\Exports\UserExporter;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('person.name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable(),
                TextColumn::make('schools.trade_name')
                    ->label('Unidades')
                    ->badge()
                    ->placeholder('Todas')
                    ->searchable(),
                IconColumn::make('panel_access')
                    ->label('Acesso Admin')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable(),
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
                TernaryFilter::make('panel_access')
                    ->label('Acesso ao Painel Admin')
                    ->trueLabel('Com acesso')
                    ->falseLabel('Sem acesso')
                    ->placeholder('Todos'),
                TernaryFilter::make('email_verified_at')->label('E-mail verificado')->nullable()
                    ->trueLabel('Verificado')->falseLabel('Pendente')->placeholder('Todos'),
                SelectFilter::make('roles')->label('Perfil')->relationship('roles', 'name')->searchable()->preload(),
            ])
            ->recordActions([
                ViewAction::make()->label(''),
                EditAction::make()->label(''),
                DeleteAction::make()
                    ->visible(fn (User $record): bool => $record->id !== Auth::id()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->authorize(function () {
                            return true;
                        })
                        ->action(function (Collection $records) {
                            $records->where('id', '!=', Auth::id())
                                ->each->delete();
                        }),
                ]),
            ])
            ->headerActions([
                ExportAction::make()->label('Exportar usuários')->exporter(UserExporter::class)
                    ->formats([ExportFormat::Csv, ExportFormat::Xlsx])
                    ->authorize(fn (): bool => Auth::user()?->getAllPermissions()->contains('name', 'Export:User') ?? false)
                    ->visible(fn (): bool => Auth::user()?->getAllPermissions()->contains('name', 'Export:User') ?? false),
            ]);
    }
}

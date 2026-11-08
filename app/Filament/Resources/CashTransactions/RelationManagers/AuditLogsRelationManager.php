<?php

namespace App\Filament\Resources\CashTransactions\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AuditLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'auditLogs';

    protected static ?string $title = 'Histórico de auditoria';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('event')
            ->columns([
                TextColumn::make('event')
                    ->label('Ação')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'created' => 'Criado',
                        'updated' => 'Alterado',
                        'deleted' => 'Removido',
                        'restored' => 'Restaurado',
                        default => $state,
                    }),
                TextColumn::make('user.name')
                    ->label('Operador')
                    ->placeholder('Sistema / usuário removido'),
                TextColumn::make('created_at')
                    ->label('Data e hora')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
                TextColumn::make('changes')
                    ->label('Valores antes e depois')
                    ->formatStateUsing(fn (mixed $state): string => is_array($state)
                        ? (json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '—')
                        : (filled($state) ? (string) $state : '—'))
                    ->wrap()
                    ->copyable(),
            ])
            ->filters([])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}

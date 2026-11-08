<?php

namespace App\Filament\Resources\People\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PersonInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identificação')
                    ->schema([
                        TextEntry::make('name')->label('Nome completo')->weight('bold'),
                        TextEntry::make('social_name')->label('Nome social')->placeholder('Não informado'),
                        TextEntry::make('birth_date')->label('Data de nascimento')->date('d/m/Y')->placeholder('Não informada'),
                        TextEntry::make('gender')->label('Gênero')->badge()->placeholder('Não informado'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Contato e documentos')
                    ->schema([
                        TextEntry::make('email')
                            ->label('E-mail')
                            ->placeholder('Não informado')
                            ->formatStateUsing(fn ($state) => \App\Services\DataMaskingService::maskEmail($state))
                            ->suffixAction(
                                \App\Services\DataMaskingService::makeRevealModalAction('revealEmail', 'E-mail', fn ($record): string => (string) ($record->email ?? ''))
                            ),
                        TextEntry::make('cpf')
                            ->label('CPF')
                            ->placeholder('Não informado')
                            ->formatStateUsing(fn ($state) => \App\Services\DataMaskingService::maskCpf($state))
                            ->suffixAction(
                                \App\Services\DataMaskingService::makeRevealModalAction('revealCpf', 'CPF', fn ($record): string => (string) ($record->cpf ?? ''))
                            ),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Responsabilidade Financeira')
                    ->schema([
                        TextEntry::make('financial_responsible_status')
                            ->label('Responsável financeiro')
                            ->badge()
                            ->state(fn ($record): string => $record->responsibles()->where('financial_responsible', true)->exists() ? 'Sim' : 'Não')
                            ->color(fn ($record): string => $record->responsibles()->where('financial_responsible', true)->exists() ? 'success' : 'gray'),
                        TextEntry::make('financial_responsible_relationship')
                            ->label('Parentesco')
                            ->state(fn ($record): string => $record->responsibles()->where('financial_responsible', true)->first()?->relationship?->getLabel() ?? '—'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Registro')
                    ->schema([
                        TextEntry::make('created_at')->label('Cadastrado em')->dateTime('d/m/Y H:i'),
                        TextEntry::make('updated_at')->label('Atualizado em')->dateTime('d/m/Y H:i'),
                    ])
                    ->columns(2)
                    ->collapsed()
                    ->columnSpanFull(),
            ]);
    }
}

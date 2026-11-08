<?php

namespace App\Filament\Resources\Collaborators\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CollaboratorInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dados do colaborador')->schema([
                    TextEntry::make('person.name')->label('Pessoa')->weight('bold'),
                    TextEntry::make('person.email')
                        ->label('E-mail principal')
                        ->formatStateUsing(fn ($state) => \App\Services\DataMaskingService::maskEmail($state))
                        ->placeholder('Não informado')
                        ->suffixAction(
                            \App\Services\DataMaskingService::makeRevealModalAction('revealCollabEmail', 'E-mail', fn ($record): string => (string) ($record->person?->email ?? ''), 'heroicon-m-eye', 'email')
                        ),
                    TextEntry::make('person.cpf')
                        ->label('CPF')
                        ->formatStateUsing(fn ($state) => \App\Services\DataMaskingService::maskCpf($state))
                        ->placeholder('Não informado')
                        ->suffixAction(
                            \App\Services\DataMaskingService::makeRevealModalAction('revealCollabCpf', 'CPF', fn ($record): string => (string) ($record->person?->cpf ?? ''), 'heroicon-m-eye', 'cpf')
                        ),
                    TextEntry::make('person.birth_date')->label('Data de Nascimento')->date('d/m/Y')->placeholder('Não informada'),
                    TextEntry::make('role')->label('Função / Cargo')->badge(),
                    TextEntry::make('admission_date')->label('Admissão')->date('d/m/Y'),
                    TextEntry::make('dismissal_date')->label('Desligamento')->date('d/m/Y')->placeholder('Ativo'),
                    TextEntry::make('pix')
                        ->label('Chave PIX')
                        ->formatStateUsing(fn ($state) => \App\Services\DataMaskingService::maskPix($state))
                        ->placeholder('Não informada')
                        ->suffixAction(
                            \App\Services\DataMaskingService::makeRevealModalAction('revealCollabPix', 'Chave PIX', fn ($record): string => (string) ($record->pix ?? ''), 'heroicon-m-eye', 'financial')
                        ),
                    IconEntry::make('active')->label('Ativo')->boolean(),
                ])->columns(2)->columnSpanFull(),
                Section::make('Regras de Comissionamento')->schema([
                    TextEntry::make('formatted_commission_rule')->label('Regra atual')->badge()->color('primary'),
                    TextEntry::make('hourly_rate')->label('Valor da Hora-Aula')->money('BRL')->placeholder('R$ 0,00'),
                    TextEntry::make('commission_notes')->label('Observações / Acordo')->placeholder('Nenhuma observação informada')->columnSpanFull(),
                ])->columns(2)->columnSpanFull(),
                Section::make('Registro')->schema([
                    TextEntry::make('created_at')->label('Cadastrado em')->dateTime('d/m/Y H:i'),
                    TextEntry::make('updated_at')->label('Atualizado em')->dateTime('d/m/Y H:i'),
                ])->columns(2)->collapsed()->columnSpanFull(),
            ]);
    }
}

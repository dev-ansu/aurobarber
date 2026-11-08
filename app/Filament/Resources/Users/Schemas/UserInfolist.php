<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Conta do usuário')->schema([
                    TextEntry::make('person.name')->label('Pessoa')->weight('bold'),
                    TextEntry::make('email')->label('E-mail')->copyable(),
                    TextEntry::make('roles.name')->label('Perfis de acesso')->badge()->separator(', ')->placeholder('Sem perfil'),
                    TextEntry::make('email_verified_at')->label('E-mail verificado em')->dateTime('d/m/Y H:i')->placeholder('Não verificado'),
                ])->columns(2)->columnSpanFull(),
                Section::make('Registro')->schema([
                    TextEntry::make('created_at')->label('Criado em')->dateTime('d/m/Y H:i'),
                    TextEntry::make('updated_at')->label('Atualizado em')->dateTime('d/m/Y H:i'),
                ])->columns(2)->collapsed()->columnSpanFull(),
            ]);
    }
}

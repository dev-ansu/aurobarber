<?php

namespace App\Filament\Auth;

use Filament\Auth\Pages\EditProfile;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class EditOwnProfile extends EditProfile
{
    public static function canAccess(): bool
    {
        return auth()->check();
    }

    public static function getLabel(): string
    {
        return 'Meu Perfil & Segurança';
    }

    protected function getPasswordFormComponent(): Component
    {
        return parent::getPasswordFormComponent()
            ->helperText('A senha deve ter no mínimo 8 caracteres, contendo letras maiúsculas, minúsculas, números e símbolos.')
            ->columnSpanFull();
    }

    protected function getPasswordConfirmationFormComponent(): Component
    {
        return parent::getPasswordConfirmationFormComponent()
            ->columnSpanFull();
    }

    protected function getCurrentPasswordFormComponent(): Component
    {
        return parent::getCurrentPasswordFormComponent()
            ->columnSpanFull();
    }

    public function form(Schema $schema): Schema
    {
        $mfaComponent = $this->getMultiFactorAuthenticationContentComponent();

        return $schema
            ->components([
                Tabs::make('Perfil & Segurança')
                    ->tabs([
                        Tab::make('Dados Pessoais & Acesso')
                            ->icon('heroicon-o-user')
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nome Completo')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),

                                $this->getEmailFormComponent()->columnSpanFull(),
                            ]),

                        Tab::make('Alterar Senha')
                            ->icon('heroicon-o-key')
                            ->schema([
                                $this->getPasswordFormComponent(),
                                $this->getPasswordConfirmationFormComponent(),
                                $this->getCurrentPasswordFormComponent(),
                            ]),

                        Tab::make('Autenticação em Duas Etapas (2FA)')
                            ->icon('heroicon-o-shield-check')
                            ->schema(array_filter([
                                $mfaComponent,
                            ])),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}

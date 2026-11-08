<?php

namespace App\Filament\Auth;

use Filament\Auth\Pages\EditProfile;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Arr;

class EditOwnProfile extends EditProfile
{
    public static function canAccess(): bool
    {
        return auth()->check();
    }

    public static function getLabel(): string
    {
        return 'Meu perfil';
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $user = $this->getUser();
        $data['name'] = $user->person?->name ?? $user->name;
        $data['two_factor_enabled'] = (bool) ($user->two_factor_enabled ?? false);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $name = Arr::pull($data, 'name');
        $twoFactorEnabled = Arr::pull($data, 'two_factor_enabled');
        $user = $this->getUser();

        if (filled($name)) {
            if ($user->person) {
                try {
                    $user->person->updateQuietly(['name' => $name]);
                } catch (\Throwable) {
                    // Ignora inconsistência com Person
                }
            }
        }

        if ($twoFactorEnabled !== null) {
            $user->two_factor_enabled = (bool) $twoFactorEnabled;
        }

        try {
            $user->saveQuietly();
        } catch (\Throwable) {
            // Garante que não dispare exceção
        }

        return $data;
    }

    protected function getPasswordFormComponent(): Component
    {
        return parent::getPasswordFormComponent()
            ->helperText('A senha deve ter no mínimo 8 caracteres, contendo letras, números e símbolos.');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dados pessoais e de acesso')
                    ->description('Atualize seu nome de exibição e o e-mail usado para entrar no sistema.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome completo')
                            ->required()
                            ->maxLength(255)
                            ->autofocus(),
                        $this->getEmailFormComponent(),
                    ])
                    ->columns(2),
                Section::make('Alterar senha')
                    ->description('Deixe os campos de senha vazios se não quiser alterá-la.')
                    ->schema([
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                        $this->getCurrentPasswordFormComponent(),
                    ])
                    ->columns(2),
                Section::make('Segurança & Verificação em Duas Etapas (2FA)')
                    ->description('Proteja sua conta contra acessos não autorizados exigindo um código de segurança enviado ao seu e-mail ao logar a partir de novos computadores ou navegadores.')
                    ->schema([
                        Toggle::make('two_factor_enabled')
                            ->label('Ativar Verificação em Duas Etapas por E-mail (2FA)')
                            ->helperText('Quando ativo, acessos vindos de dispositivos não reconhecidos exigirão um código de 6 dígitos enviado para seu e-mail de login.')
                            ->inline(false),
                    ]),
            ]);
    }
}

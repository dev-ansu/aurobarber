<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use Filament\Actions\Action;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Illuminate\Contracts\Support\Htmlable;

class Login extends BaseLogin
{
    public function getTitle(): string | Htmlable
    {
        return Filament::getCurrentPanel()?->getId() === 'saas'
            ? 'Acesso SaaS Master • AuroBarber'
            : 'Acesso Administrativo • AuroBarber';
    }

    public function getHeading(): string | Htmlable | null
    {
        return Filament::getCurrentPanel()?->getId() === 'saas'
            ? 'AuroBarber SaaS Master'
            : 'Painel da Barbearia';
    }

    public function getSubheading(): string | Htmlable | null
    {
        return Filament::getCurrentPanel()?->getId() === 'saas'
            ? 'Acesso restrito ao Administrador Global da Plataforma.'
            : 'Acesse para gerenciar agendamentos, equipe e financeiro.';
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('E-mail de Acesso')
            ->email()
            ->placeholder('seu.email@barbearia.com.br')
            ->required()
            ->autocomplete('username')
            ->autofocus();
    }

    protected function getAuthenticateFormAction(): Action
    {
        $label = Filament::getCurrentPanel()?->getId() === 'saas'
            ? 'Entrar no SaaS Master'
            : 'Entrar no Painel';

        return parent::getAuthenticateFormAction()
            ->label($label);
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use App\Models\User;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Actions\Action;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Validation\ValidationException;
use SensitiveParameter;

class Login extends BaseLogin
{
    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $data = $this->form->getState();

        /** @var \Illuminate\Auth\SessionGuard $authGuard */
        $authGuard = Filament::auth();
        $authProvider = $authGuard->getProvider();
        $credentials = $this->getCredentialsFromFormData($data);
        $remember = (bool) ($data['remember'] ?? false);

        $user = $authProvider->retrieveByCredentials($credentials);

        if (! $user || ! $authProvider->validateCredentials($user, $credentials)) {
            $this->throwFailureValidationException();
        }

        // Usuários sem papel definido no sistema não possuem autorização para logar
        if ($user instanceof User && method_exists($user, 'isCollaborator') && $user->isCollaborator() && method_exists($user, 'hasDefinedRole') && ! $user->hasDefinedRole()) {
            throw ValidationException::withMessages([
                'data.email' => 'Acesso bloqueado: Este usuário não possui um papel atribuído no sistema.',
            ]);
        }

        if (! $this->isUserAllowedToAccessPanel($user)) {
            $this->throwFailureValidationException();
        }

        // Verificação em Duas Etapas (2FA por E-mail)
        $twoFactorService = app(\App\Services\TwoFactorAuthService::class);
        if ($twoFactorService->isEnabled($user) && ! $twoFactorService->isDeviceTrusted($user, request())) {
            $twoFactorService->generateAndSendCode($user, request());

            session()->put('2fa.user_id', $user->id);
            session()->put('2fa.remember', $remember);
            session()->put('2fa.panel', Filament::getCurrentPanel()?->getId() ?? 'admin');

            $this->redirect(route('auth.2fa.challenge'));

            return null;
        }

        $authGuard->login($user, $remember);

        session()->regenerate();

        return app(LoginResponse::class);
    }

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
            : 'Acesso restrito para equipe da barbearia.';
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('E-mail ou Usuário')
            ->placeholder('seu.email@barbearia.com.br')
            ->required()
            ->autocomplete('username')
            ->autofocus();
    }

    protected function getAuthenticateFormAction(): Action
    {
        $label = Filament::getCurrentPanel()?->getId() === 'saas'
            ? 'Entrar no SaaS Master'
            : 'Entrar no Painel Administrativo';

        return parent::getAuthenticateFormAction()
            ->label($label);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function getCredentialsFromFormData(#[SensitiveParameter] array $data): array
    {
        $login = trim((string) ($data['email'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        // Se for um endereço de e-mail comum, autentica diretamente
        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            return [
                'email' => $login,
                'password' => $password,
            ];
        }

        // Tenta resolver usuário por CPF ou username institucional
        $cleanDigits = preg_replace('/\D/', '', $login);
        $resolvedUser = null;

        if (strlen($cleanDigits) === 11) {
            $resolvedUser = User::query()
                ->whereHas('person', fn ($q) => $q->whereCpf($cleanDigits))
                ->first();
        }

        if (! $resolvedUser) {
            $resolvedUser = User::query()
                ->whereHas('person', function ($q) use ($login) {
                    $q->whereRaw('LOWER(name) = ?', [mb_strtolower($login)])
                        ->orWhere('ouro_moderno_usuario', $login);
                })
                ->orWhereHas('person.collaborators', function ($q) use ($login) {
                    $q->where('ouro_moderno_usuario', $login);
                })
                ->first();
        }

        return [
            'email' => $resolvedUser ? $resolvedUser->email : $login,
            'password' => $password,
        ];
    }
}

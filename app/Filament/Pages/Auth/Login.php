<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use App\Models\User;
use App\Services\TwoFactorAuthService;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Actions\Action;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\MultiFactor\Contracts\HasBeforeChallengeHook;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Timebox;

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

        /** @var SessionGuard $authGuard */
        $authGuard = Filament::auth();

        $authProvider = $authGuard->getProvider();
        $credentials = $this->getCredentialsFromFormData($data);
        $remember = $data['remember'] ?? false;
        $timeboxDuration = (int) config('auth.timebox_duration', 200_000);

        $user = app(Timebox::class)->call(function (Timebox $timebox) use ($authProvider, $authGuard, $credentials, $remember): Authenticatable {
            $this->fireAttemptingEvent($authGuard, $credentials, $remember);

            $user = $authProvider->retrieveByCredentials($credentials);

            if ((! $user) || (! $authProvider->validateCredentials($user, $credentials))) {
                $this->userUndertakingMultiFactorAuthentication = null;

                $this->fireFailedEvent($authGuard, $user, $credentials);
                $this->throwFailureValidationException();
            }

            if (! $this->isUserAllowedToAccessPanel($user)) {
                $this->userUndertakingMultiFactorAuthentication = null;

                $this->fireFailedEvent($authGuard, $user, $credentials);
                $this->throwFailureValidationException();
            }

            $timebox->returnEarly();

            return $user;
        }, $timeboxDuration);

        $twoFactorService = app(TwoFactorAuthService::class);
        $isDeviceTrusted = $user instanceof User && $twoFactorService->isDeviceTrusted($user, request());

        if (! $isDeviceTrusted) {
            $completedChallengeNow = false;

            $needsMultiFactorChallenge = app(Timebox::class)->call(function (Timebox $timebox) use ($user, &$completedChallengeNow): bool {
                if (
                    filled($this->userUndertakingMultiFactorAuthentication) &&
                    (decrypt($this->userUndertakingMultiFactorAuthentication) === $user->getAuthIdentifier())
                ) {
                    if ($this->isMultiFactorChallengeRateLimited($user)) {
                        return true;
                    }

                    $this->multiFactorChallengeForm->validate();
                    $completedChallengeNow = true;

                    return false;
                }

                foreach (Filament::getMultiFactorAuthenticationProviders() as $multiFactorAuthenticationProvider) {
                    if (! $multiFactorAuthenticationProvider->isEnabled($user)) {
                        continue;
                    }

                    $this->userUndertakingMultiFactorAuthentication = encrypt($user->getAuthIdentifier());

                    if ($multiFactorAuthenticationProvider instanceof HasBeforeChallengeHook) {
                        $multiFactorAuthenticationProvider->beforeChallenge($user);
                    }

                    break;
                }

                if (filled($this->userUndertakingMultiFactorAuthentication)) {
                    $this->multiFactorChallengeForm->fill();

                    return true;
                }

                return false;
            }, $timeboxDuration);

            if ($needsMultiFactorChallenge) {
                return null;
            }

            // Se acabou de validar o 2FA e marcou "Confiar neste navegador por 30 dias"
            if ($completedChallengeNow && $user instanceof User && ! empty($this->data['multiFactor']['trust_device'])) {
                $twoFactorService->trustCurrentDevice($user, request(), 30);
            }
        }

        if (! $authGuard->attemptWhen($credentials, fn (Authenticatable $user): bool => $this->isUserAllowedToAccessPanel($user), $remember)) {
            $this->fireFailedEvent($authGuard, $user, $credentials);
            $this->throwFailureValidationException();
        }

        session()->regenerate();

        return app(LoginResponse::class);
    }

    public function defaultMultiFactorChallengeForm(Schema $schema): Schema
    {
        return $schema
            ->components(function (): array {
                if (blank($this->userUndertakingMultiFactorAuthentication)) {
                    return [];
                }

                $authProvider = Filament::auth()->getProvider();
                $user = $authProvider->retrieveById(decrypt($this->userUndertakingMultiFactorAuthentication));

                $enabledMultiFactorAuthenticationProviders = array_filter(
                    Filament::getMultiFactorAuthenticationProviders(),
                    fn (\Filament\Auth\MultiFactor\Contracts\MultiFactorAuthenticationProvider $multiFactorAuthenticationProvider): bool => $multiFactorAuthenticationProvider->isEnabled($user)
                );

                return [
                    ...\Illuminate\Support\Arr::wrap($this->getMultiFactorProviderFormComponent()),
                    ...collect($enabledMultiFactorAuthenticationProviders)
                        ->map(fn (\Filament\Auth\MultiFactor\Contracts\MultiFactorAuthenticationProvider $multiFactorAuthenticationProvider): Component => \Filament\Schemas\Components\Group::make($multiFactorAuthenticationProvider->getChallengeFormComponents($user))
                            ->statePath($multiFactorAuthenticationProvider->getId())
                            ->when(
                                count($enabledMultiFactorAuthenticationProviders) > 1,
                                fn (\Filament\Schemas\Components\Group $group) => $group->visible(fn (\Filament\Schemas\Components\Utilities\Get $get): bool => $get('provider') === $multiFactorAuthenticationProvider->getId())
                            ))
                        ->all(),
                    Checkbox::make('trust_device')
                        ->label('Confiar neste navegador por 30 dias')
                        ->default(true),
                ];
            })
            ->statePath('data.multiFactor');
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

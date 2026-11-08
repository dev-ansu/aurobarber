<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TwoFactorAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TwoFactorChallengeController extends Controller
{
    public function __construct(
        protected TwoFactorAuthService $twoFactorService
    ) {}

    public function show(Request $request): View | RedirectResponse
    {
        $userId = $request->session()->get('2fa.user_id');

        if (! $userId) {
            return $this->redirectToLogin();
        }

        $user = User::query()->find($userId);

        if (! $user) {
            $request->session()->forget(['2fa.user_id', '2fa.remember', '2fa.panel']);
            return $this->redirectToLogin();
        }

        $maskedEmail = $this->maskEmail($user->email);
        $panel = $request->session()->get('2fa.panel', 'admin');

        return view('auth.two-factor-challenge', [
            'user' => $user,
            'maskedEmail' => $maskedEmail,
            'panel' => $panel,
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $userId = $request->session()->get('2fa.user_id');

        if (! $userId) {
            return $this->redirectToLogin();
        }

        $user = User::query()->find($userId);

        if (! $user) {
            $request->session()->forget(['2fa.user_id', '2fa.remember', '2fa.panel']);
            return $this->redirectToLogin();
        }

        $rateKey = '2fa_attempts_' . $user->id;

        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            $seconds = RateLimiter::availableIn($rateKey);
            return back()->withErrors([
                'code' => "Muitas tentativas incorretas. Aguarde {$seconds} segundos antes de tentar novamente.",
            ]);
        }

        $code = trim((string) $request->input('code', ''));

        if (! $this->twoFactorService->verifyCode($user, $code)) {
            RateLimiter::hit($rateKey, 300); // 5 min decay
            return back()->withErrors([
                'code' => 'Código de segurança incorreto ou expirado. Verifique o seu e-mail e tente novamente.',
            ]);
        }

        RateLimiter::clear($rateKey);

        // Se marcou para lembrar deste dispositivo por 30 dias
        if ($request->boolean('remember_device', true)) {
            $this->twoFactorService->trustCurrentDevice($user, $request);
        }

        $remember = (bool) $request->session()->get('2fa.remember', false);
        $panel = $request->session()->get('2fa.panel', 'admin');

        $request->session()->forget(['2fa.user_id', '2fa.remember', '2fa.panel']);

        Auth::login($user, $remember);
        $request->session()->regenerate();

        return redirect()->intended($this->getPanelUrl($panel));
    }

    public function resend(Request $request): RedirectResponse
    {
        $userId = $request->session()->get('2fa.user_id');

        if (! $userId) {
            return $this->redirectToLogin();
        }

        $user = User::query()->find($userId);

        if (! $user) {
            return $this->redirectToLogin();
        }

        $rateKey = '2fa_resend_' . $user->id;

        if (RateLimiter::tooManyAttempts($rateKey, 1)) {
            $seconds = RateLimiter::availableIn($rateKey);
            return back()->with('warning', "Aguarde {$seconds} segundos antes de solicitar um novo código.");
        }

        RateLimiter::hit($rateKey, 60);

        $this->twoFactorService->generateAndSendCode($user, $request);

        return back()->with('status', 'Um novo código de segurança foi enviado para o seu e-mail.');
    }

    public function cancel(Request $request): RedirectResponse
    {
        $panel = $request->session()->get('2fa.panel', 'admin');
        $request->session()->forget(['2fa.user_id', '2fa.remember', '2fa.panel']);

        return $this->redirectToLogin($panel);
    }

    protected function redirectToLogin(?string $panel = null): RedirectResponse
    {
        $panel = $panel ?? session('2fa.panel', 'admin');

        if ($panel === 'saas') {
            $saasDomain = (string) env('SAAS_PANEL_DOMAIN', 'master.aurosoft.com.br');
            return redirect("https://{$saasDomain}/login");
        }

        if ($panel === 'student') {
            return redirect('/portal-aluno/login');
        }

        return redirect('/admin/login');
    }

    protected function getPanelUrl(string $panel): string
    {
        if ($panel === 'saas') {
            $saasDomain = (string) env('SAAS_PANEL_DOMAIN', 'master.aurosoft.com.br');
            return "https://{$saasDomain}";
        }

        if ($panel === 'student') {
            return '/portal-aluno';
        }

        return '/admin';
    }

    protected function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return $email;
        }

        $name = $parts[0];
        $domain = $parts[1];

        if (strlen($name) <= 2) {
            $maskedName = substr($name, 0, 1) . '***';
        } else {
            $maskedName = substr($name, 0, 2) . str_repeat('*', max(3, strlen($name) - 3)) . substr($name, -1);
        }

        return $maskedName . '@' . $domain;
    }
}

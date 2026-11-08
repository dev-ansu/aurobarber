<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsChanged
{
    /**
     * Intercepta usuários que possuem a flag must_change_password ativa,
     * forçando-os a redefinir a senha antes de acessar qualquer painel.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            if ($user && $user->must_change_password) {
                // Rotas permitidas mesmo com troca pendente (tela de troca, envio do form e logout)
                if (
                    $request->routeIs('password.force_change') ||
                    $request->routeIs('password.force_change.update') ||
                    $request->is('auth/trocar-senha*') ||
                    $request->is('*/logout') ||
                    $request->is('logout')
                ) {
                    return $next($request);
                }

                return redirect()->route('password.force_change');
            }
        }

        return $next($request);
    }
}

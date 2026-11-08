<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && (bool) $user->must_change_password) {
            // Permite deslogar
            if ($request->routeIs('filament.admin.auth.logout')) {
                return $next($request);
            }

            // Permite acessar a página de redefinição de senha
            if ($request->routeIs('*force-password-change*') || $request->is('*force-password-change*')) {
                return $next($request);
            }

            // Permite requisições Livewire na própria tela de redefinição
            if ($request->hasHeader('X-Livewire') && str_contains($request->header('Referer', ''), 'force-password-change')) {
                return $next($request);
            }

            return redirect()->to(url('/admin/force-password-change'));
        }

        return $next($request);
    }
}

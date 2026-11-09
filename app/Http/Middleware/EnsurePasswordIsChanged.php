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
            if ($request->routeIs('filament.*.auth.logout') || $request->is('*/logout')) {
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

            // Se for Master Admin ou estiver no painel SaaS, vai para o SaaS Master
            if ($user->role === 'master_admin' || $request->is('saas*')) {
                return redirect()->to(url('/saas/force-password-change'));
            }

            // Para donos/barbeiros no painel admin
            $panel = filament()->getCurrentPanel();
            $tenant = filament()->getTenant() ?? ($panel ? $user->getTenants($panel)->first() : null);

            if ($tenant) {
                return redirect()->to(url('/admin/' . $tenant->slug . '/force-password-change'));
            }

            return redirect()->to(url('/saas/force-password-change'));
        }

        return $next($request);
    }
}

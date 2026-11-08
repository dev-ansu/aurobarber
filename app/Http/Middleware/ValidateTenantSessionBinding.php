<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ValidateTenantSessionBinding
{
    /**
     * Valida se a sessão autenticada pertence estritamente ao host e schema atuais.
     * Impede qualquer vazamento cruzado de sessão entre o SaaS Master e as escolas clientes.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $currentHost = strtolower(trim(explode(':', $request->getHost())[0]));
            $currentSearchPath = (string) Config::get('database.connections.pgsql.search_path', 'public');
            $cleanCurrentSchema = trim(explode(',', $currentSearchPath)[0]);

            $sessionHost = session('auth_session_host');
            $sessionSchema = session('auth_session_schema');

            // 1. Primeira requisição logo após o login: vincula a sessão ao host e schema de origem
            if (empty($sessionHost) || empty($sessionSchema)) {
                session([
                    'auth_session_host' => $currentHost,
                    'auth_session_schema' => $cleanCurrentSchema,
                    'auth_session_user_id' => Auth::id(),
                ]);
            } else {
                // 2. Requisições subsequentes: verifica se o usuário trocou de subdomínio/URL na barra de endereços
                $isHostMismatch = ($sessionHost !== $currentHost);
                $isSchemaMismatch = ($sessionSchema !== $cleanCurrentSchema);

                if ($isHostMismatch || $isSchemaMismatch) {
                    Log::warning('Tentativa de acesso cruzado de sessão entre tenants bloqueada', [
                        'user_id' => Auth::id(),
                        'session_origin_host' => $sessionHost,
                        'session_origin_schema' => $sessionSchema,
                        'attempted_host' => $currentHost,
                        'attempted_schema' => $cleanCurrentSchema,
                        'ip' => $request->ip(),
                    ]);

                    // Destrói imediatamente a sessão e força reautenticação
                    Auth::guard('web')->logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return redirect('/login')->with('warning', 'Sessão desconectada por segurança ao trocar de unidade/ambiente.');
                }
            }
        }

        return $next($request);
    }
}

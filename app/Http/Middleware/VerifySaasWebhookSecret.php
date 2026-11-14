<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifySaasWebhookSecret
{
    /**
     * Valida se a requisição do SaaS Master possui o token secreto autorizado.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $expectedSecret = (string) config('saas.webhook_secret');

        if (blank($expectedSecret)) {
            return response()->json([
                'success' => false,
                'message' => 'SAAS_WEBHOOK_SECRET não configurado na aplicação.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $providedSecret = $request->header('X-Auro-Secret')
            ?? $request->header('X-SaaS-Secret')
            ?? $request->bearerToken()
            ?? $request->input('secret');

        if (! is_string($providedSecret) || ! hash_equals($expectedSecret, $providedSecret)) {
            return response()->json([
                'success' => false,
                'message' => 'Acesso não autorizado. Chave de integração inválida ou ausente.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}

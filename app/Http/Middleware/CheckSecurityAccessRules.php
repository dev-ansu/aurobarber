<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\Security\SecurityAccessRuleService;
use App\Services\SecurityEventLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSecurityAccessRules
{
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();
        $user = $request->user();

        $ruleService = app(SecurityAccessRuleService::class);
        $blacklistMatch = $ruleService->findBlacklistMatch($ip, $user instanceof User ? $user : null);

        if ($blacklistMatch) {
            app(SecurityEventLogger::class)->record(
                'security.ip_blocked',
                actor: $user,
                subject: $ip,
                severity: 'warning',
                metadata: [
                    'rule_id' => $blacklistMatch->id,
                    'rule_label' => $blacklistMatch->label,
                    'url' => $request->fullUrl(),
                ],
                request: $request,
            );

            abort(403, 'Acesso bloqueado: Seu endereço IP ou dispositivo consta na lista de restrição de segurança (Blacklist) do AuroBarber.');
        }

        return $next($request);
    }
}

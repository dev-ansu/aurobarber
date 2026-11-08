<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\LicenseService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckLicenseStatus
{
    public function __construct(
        protected LicenseService $licenseService
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Se a validação estiver desativada no ambiente (ex: dev local/testes), permite tudo
        if (! $this->licenseService->isEnabled()) {
            return $next($request);
        }

        // Rotas isentas de bloqueio de licença
        if ($this->shouldPassThrough($request)) {
            return $next($request);
        }

        // Valida se a licença está ativa
        if (! $this->licenseService->isLicenseActive()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                $status = $this->licenseService->checkLicense();

                return response()->json([
                    'error' => 'license_blocked',
                    'message' => $status['message'],
                    'support_phone' => $status['support_phone'],
                ], Response::HTTP_FORBIDDEN);
            }

            return redirect()->route('license.blocked');
        }

        return $next($request);
    }

    private function shouldPassThrough(Request $request): bool
    {
        // Se a requisição for para a Home pública, Painel SaaS Master, Developer, Telemetria ou Modo Demo, não bloqueia por licença
        if (
            $request->is('/') ||
            $request->is('saas*') ||
            $request->is('developer*') ||
            $request->is('demo*') ||
            $request->is('api/v1/internal/*') ||
            session('active_tenant_schema') === 'demo' ||
            app()->bound('current_tenant_license')
        ) {
            return true;
        }

        $exemptPatterns = [
            'manifest.webmanifest*',
            'solicitar-demonstracao*',
            'licenca*',
            'livewire*',
            'build/*',
            'css/*',
            'js/*',
            'images/*',
            'storage/*',
            'favicon.ico',
            'up',
            '_debugbar*',
        ];

        foreach ($exemptPatterns as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }
}


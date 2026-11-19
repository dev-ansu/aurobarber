<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Barbershop;
use App\Services\TenantService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantSchema
{
    public function __construct(
        protected TenantService $tenantService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = strtolower(trim(explode(':', $request->getHost())[0]));

        // Garante que a resolução do cadastro da barbearia sempre consulte o schema public
        $this->tenantService->switchToPublic();

        // 1. Painel SaaS Master ou rotas globais
        if (str_starts_with($host, 'saas.') || str_starts_with($host, 'master.') || $request->is('saas*')) {
            return $next($request);
        }

        // 2. Resolução por Subdomínio ou Domínio Próprio
        $tenantBarbershop = $this->tenantService->resolveTenantFromHost($host);
        $resolvedFromAdminContext = false;

        // 3. Resolução por Rota de Tenant do Filament (/admin/{tenant}/...) ou Rota Pública (/{slug}/...)
        if (! $tenantBarbershop) {
            $routeTenant = $request->route('tenant');
            $routeSlug = $request->route('slug');
            $routeIdentifier = $routeTenant ?? $routeSlug;

            // Fallback imediato via URI Path quando o middleware roda antes da resolução de rotas pelo kernel
            if (! $routeIdentifier) {
                if ($request->segment(1) === 'admin' && filled($request->segment(2)) && ! in_array($request->segment(2), ['login', 'logout', 'password-reset'], true)) {
                    $routeIdentifier = $request->segment(2);
                    $resolvedFromAdminContext = true;
                } elseif (! in_array($request->segment(1), ['admin', 'saas', 'livewire', 'up', 'api', 'manifest.json', 'storage', 'build', null], true)) {
                    $routeIdentifier = $request->segment(1);
                }
            }

            if ($routeIdentifier instanceof Barbershop) {
                $tenantBarbershop = $routeIdentifier;
                $resolvedFromAdminContext = $resolvedFromAdminContext || $routeTenant !== null;
            } elseif (is_string($routeIdentifier) && $routeIdentifier !== '') {
                $tenantBarbershop = Barbershop::where('slug', $routeIdentifier)
                    ->orderByDesc('is_active')
                    ->orderByDesc('id')
                    ->first();
                $resolvedFromAdminContext = $resolvedFromAdminContext || $routeTenant !== null;
            }
        }

        // 3.1. Resolução via contexto do Filament (ex: requisições Livewire /livewire/update com persistent middleware)
        if (! $tenantBarbershop && function_exists('filament') && filament()->getTenant() instanceof Barbershop) {
            $tenantBarbershop = filament()->getTenant();
            $resolvedFromAdminContext = true;
        }

        // 3.2. Resolução via Referer em requisições Livewire (/admin/{slug}/... ou /{slug}/...)
        if (! $tenantBarbershop && $request->headers->has('referer')) {
            $refererPath = (string) parse_url((string) $request->headers->get('referer'), PHP_URL_PATH);
            if (preg_match('#^/admin/([^/?\#]+)#', $refererPath, $matches)) {
                $tenantBarbershop = Barbershop::where('slug', $matches[1])
                    ->orderByDesc('is_active')
                    ->orderByDesc('id')
                    ->first();
                $resolvedFromAdminContext = true;
            }
        }

        // 3.3. Fallback via sessão autenticada (blindagem para requisições Livewire sem Referer)
        if (! $tenantBarbershop && $request->hasSession()) {
            $sessionTenantId = $request->session()->get('aurobarber.active_tenant_id');
            if ($sessionTenantId) {
                $candidate = Barbershop::query()->find($sessionTenantId);
                $user = $request->user();

                if ($candidate && (! $user || ! method_exists($user, 'canAccessTenant') || $user->canAccessTenant($candidate))) {
                    $tenantBarbershop = $candidate;
                    $resolvedFromAdminContext = true;
                }
            }
        }

        // Persiste o tenant ativo na sessão apenas em contexto do painel administrativo
        if ($tenantBarbershop && $resolvedFromAdminContext && $request->hasSession()) {
            $request->session()->put('aurobarber.active_tenant_id', $tenantBarbershop->getKey());
        }

        // 4. Se o tenant foi identificado e possui schema PostgreSQL dedicado
        if ($tenantBarbershop && ! empty($tenantBarbershop->schema_name)) {
            $this->tenantService->switchToTenant($tenantBarbershop->schema_name);
            app()->instance('current_tenant_barbershop', $tenantBarbershop);
        } else {
            // Modo retrocompatível: barbearia opera no schema public com barbershop_id
            $this->tenantService->switchToPublic();
            if ($tenantBarbershop) {
                app()->instance('current_tenant_barbershop', $tenantBarbershop);
            }
        }

        $response = $next($request);

        return $response;
    }
}

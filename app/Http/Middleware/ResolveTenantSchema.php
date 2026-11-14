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
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = strtolower(trim(explode(':', $request->getHost())[0]));

        // 1. Painel SaaS Master ou rotas globais
        if (str_starts_with($host, 'saas.') || str_starts_with($host, 'master.') || $request->is('saas*')) {
            $this->tenantService->switchToPublic();
            return $next($request);
        }

        // 2. Resolução por Subdomínio ou Domínio Próprio
        $tenantBarbershop = $this->tenantService->resolveTenantFromHost($host);

        // 3. Resolução por Rota de Tenant do Filament (ex: /admin/{tenant}/...)
        if (! $tenantBarbershop) {
            $routeTenant = $request->route('tenant');
            if ($routeTenant instanceof Barbershop) {
                $tenantBarbershop = $routeTenant;
            } elseif (is_string($routeTenant) && $routeTenant !== '') {
                $tenantBarbershop = Barbershop::where('slug', $routeTenant)->first();
            }
        }

        // 4. Se o tenant foi identificado e possui schema PostgreSQL dedicado
        if ($tenantBarbershop && ! empty($tenantBarbershop->schema_name)) {
            $this->tenantService->switchToTenant($tenantBarbershop->schema_name);
            app()->instance('current_tenant_barbershop', $tenantBarbershop);
        } else {
            // Modo retrocompatível: barbearia opera no schema public com barbershop_id
            $this->tenantService->switchToPublic();
        }

        $response = $next($request);

        return $response;
    }
}

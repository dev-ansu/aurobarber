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
                $tenantBarbershop = Barbershop::where('slug', $routeTenant)
                    ->orderByDesc('is_active')
                    ->orderByDesc('id')
                    ->first();
            }
        }

        // 3.1. Resolução via contexto do Filament (ex: requisições Livewire /livewire/update com persistent middleware)
        if (! $tenantBarbershop && function_exists('filament') && filament()->getTenant() instanceof Barbershop) {
            $tenantBarbershop = filament()->getTenant();
        }

        // 3.2. Resolução via Referer em requisições Livewire (/admin/{slug}/... ou /{slug}/...)
        if (! $tenantBarbershop && $request->headers->has('referer')) {
            $refererPath = (string) parse_url((string) $request->headers->get('referer'), PHP_URL_PATH);
            if (preg_match('#^/admin/([^/?\#]+)#', $refererPath, $matches)) {
                $tenantBarbershop = Barbershop::where('slug', $matches[1])
                    ->orderByDesc('is_active')
                    ->orderByDesc('id')
                    ->first();
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
                }
            }
        }

        // Persiste o tenant ativo na sessão para requisições assíncronas subsequentes
        if ($tenantBarbershop && $request->hasSession()) {
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

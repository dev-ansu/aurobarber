<?php

declare(strict_types=1);

namespace App\Http\Middleware;

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
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = strtolower(trim(explode(':', $request->getHost())[0]));

        // Isolamento Estrito de Cookies e Sessões: Cada tenant e o SaaS Master têm cookies únicos e host-only
        \Illuminate\Support\Facades\Config::set('session.domain', null);

        // 1. Subdomínio Exclusivo do SaaS Master (master.aurosoft.com.br ou master.localhost)
        if (str_starts_with($host, 'master.') || $host === 'master.aurosoft.com.br') {
            \Illuminate\Support\Facades\Config::set('session.cookie', 'aurosoft_master_session');
            $this->tenantService->switchToPublic();

            // O Painel SaaS Master não possui rotas /admin nem tenants de escola. Redireciona para o login ou raiz do Master.
            if ($request->is('admin*')) {
                return redirect($request->is('admin/login*') ? '/login' : '/');
            }

            return $next($request);
        }

        // 2. Ambiente de Demonstração (demo.aurosoft.com.br ou demo.localhost)
        if (str_starts_with($host, 'demo.') || $host === 'demo.aurosoft.com.br') {
            \Illuminate\Support\Facades\Config::set('session.cookie', 'aurosoft_demo_session');
            $this->tenantService->switchToTenant('demo');
            return $next($request);
        }

        // 3. Subdomínio ou Domínio Próprio de Escola Cliente
        $tenantLicense = $this->tenantService->resolveTenantFromHost($host);

        if ($tenantLicense) {
            $schemaName = $tenantLicense->schema_name;
            $cleanSchema = preg_replace('/[^a-zA-Z0-9_]/', '_', (string) $schemaName);
            \Illuminate\Support\Facades\Config::set('session.cookie', 'aurosoft_tenant_' . $cleanSchema . '_session');

            // Se a licença da escola estiver suspensa, vencida ou cancelada
            if (! $tenantLicense->isValid()) {
                return response()->view('license.blocked', [
                    'status' => [
                        'school_name' => $tenantLicense->trade_name ?: $tenantLicense->client_name,
                        'message' => 'O acesso da instituição foi temporariamente suspenso devido a pendências de assinatura ou expiração da licença.',
                        'due_date' => $tenantLicense->expires_at?->format('d/m/Y'),
                        'pix_payload' => null,
                        'pix_qr_url' => null,
                    ],
                ], 403);
            }

            // Verifica se o schema do banco já foi provisionado
            $schemaName = $tenantLicense->schema_name;
            if (empty($schemaName) || ! $tenantLicense->isSchemaProvisioned()) {
                return response()->view('license.blocked', [
                    'status' => [
                        'school_name' => $tenantLicense->trade_name ?: $tenantLicense->client_name,
                        'message' => 'O ambiente desta escola está em processo de provisionamento ou ativação. Por favor, aguarde alguns instantes ou contate o suporte.',
                        'due_date' => null,
                        'pix_payload' => null,
                        'pix_qr_url' => null,
                    ],
                ], 503);
            }

            // Alterna a conexão PostgreSQL para o schema do tenant
            $this->tenantService->switchToTenant($schemaName);

            // Registra a instância globalmente no container
            app()->instance('current_tenant_license', $tenantLicense);
            view()->share('currentTenantLicense', $tenantLicense);

            return $next($request);
        }

        // 4. APIs e healthcheck
        if (
            $request->is('api/v1/saas/*') || 
            $request->is('api/v1/internal/*') || 
            $request->is('up')
        ) {
            $this->tenantService->switchToPublic();
            return $next($request);
        }

        // 5. Fallback: Sessão explicitamente no modo 'demo' via navegação
        $activeTenant = session('active_tenant_schema');

        if ($activeTenant && is_string($activeTenant)) {
            $this->tenantService->switchToTenant($activeTenant);
            return $next($request);
        }

        // 6. No ambiente SaaS Cloud, o domínio raiz da Aurosoft não hospeda painéis de escolas diretamente.
        // As escolas devem acessar seus respectivos subdomínios (ex: colegio.aurosoft.com.br/admin).
        // Se alguém tentar acessar /admin, /estudio ou /presenca no domínio raiz fora da demo, redireciona para a Landing Page.
        if (
            (bool) env('SAAS_ENABLED', false) &&
            in_array($host, ['aurosoft.com.br', 'www.aurosoft.com.br', '2.25.75.35']) &&
            ($request->is('admin*') || $request->is('estudio*') || $request->is('presenca*'))
        ) {
            return redirect('/')->with('tenant_modal_open', true);
        }

        $this->tenantService->switchToPublic();

        return $next($request);
    }
}


<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Barbershop;
use Closure;
use Filament\Facades\Filament;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBarbershopLicenseIsValid
{
    /**
     * Valida se a barbearia acessada está com a licença ativa e aplica o White-Label (cor da marca).
     * Master Admin é sempre isento de bloqueio para poder prestar suporte e auditoria.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = Filament::getTenant();

        // Se não houver tenant no contexto atual (ex: rotas globais do painel), segue
        if (! ($tenant instanceof Barbershop)) {
            return $next($request);
        }

        // Aplica dinamicamente a cor da marca da barbearia (White-Label) no painel
        if (! empty($tenant->brand_color) && preg_match('/^#[0-9A-Fa-f]{6}$/', $tenant->brand_color)) {
            try {
                FilamentColor::register([
                    'primary' => Color::hex($tenant->brand_color),
                ]);
            } catch (\Throwable $e) {
                // Mantém o Amber padrão se a cor for inválida
            }
        }

        $user = auth()->user();

        // Rotas liberadas dentro do tenant: tela de bloqueio e logout
        if ($request->routeIs('filament.admin.tenant.pages.tenant-license-blocked') ||
            $request->is('*/licenca-bloqueada*') ||
            $request->is('*/logout*') ||
            $request->is('logout')
        ) {
            return $next($request);
        }

        // Verifica se a licença está expirada / suspensa (fora do prazo e fora da carência)
        if (! $tenant->isLicenseActive()) {
            return redirect()->to(url("/admin/{$tenant->slug}/licenca-bloqueada"));
        }

        return $next($request);
    }
}

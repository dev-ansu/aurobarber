<?php

namespace App\Http\Middleware;

use App\Models\School;
use App\Models\User;
use App\Services\TaskAndAppointmentReminderService;
use Closure;
use Filament\Facades\Filament;
use Filament\Models\Contracts\HasTenants;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class ApplyTenantUrlDefaults
{
    public function handle(Request $request, Closure $next): Response
    {
        $panel = Filament::getCurrentOrDefaultPanel();

        if (auth()->check() && method_exists(auth()->user(), 'touchOnlinePresence')) {
            auth()->user()->touchOnlinePresence();
        }

        if ($panel?->hasTenancy()) {
            $tenant = Filament::getTenant();

            if (! $tenant && auth()->check()) {
                /** @var User $user */
                $user = auth()->user();

                // 1. Tenta recuperar da query string ou rota
                $tenantId = filled($request->query('tenant')) ? $request->query('tenant') : $request->route('tenant');

                // 2. Tenta recuperar da sessão
                if (! $tenantId) {
                    $tenantId = session('filament_tenant_id');
                }

                if ($tenantId instanceof School) {
                    $candidate = $tenantId;
                } elseif ($tenantId && (is_numeric($tenantId) || ctype_digit((string) $tenantId))) {
                    $candidate = School::find((int) $tenantId);
                } else {
                    $candidate = null;
                }

                if ($candidate && (! $user instanceof HasTenants || $user->canAccessTenant($candidate))) {
                    $tenant = $candidate;
                }

                // 3. Fallback: primeira unidade autorizada do usuário
                if (! $tenant) {
                    if ($user instanceof HasTenants) {
                        $tenant = $user->getTenants($panel)->first();
                    }
                    $tenant ??= School::query()->orderBy('id')->first();
                }

                if ($tenant) {
                    Filament::setTenant($tenant);
                }
            }

            if ($tenant) {
                session(['filament_tenant_id' => $tenant->getKey()]);
                URL::defaults([
                    'tenant' => $tenant->getRouteKey(),
                ]);
            }
        }

        if (
            auth()->check()
            && $request->isMethod('GET')
            && ! $request->expectsJson()
            && ! $request->hasHeader('X-Livewire')
        ) {
            try {
                $authUser = auth()->user();
                if ($authUser instanceof User) {
                    $activeSchool = Filament::getTenant() instanceof School ? Filament::getTenant() : null;
                    app(TaskAndAppointmentReminderService::class)
                        ->notifyUserIfNeeded($authUser, $activeSchool, true);
                }
            } catch (\Throwable) {
                // Silencia falhas de notificação para nunca interromper a navegação
            }
        }

        return $next($request);
    }
}

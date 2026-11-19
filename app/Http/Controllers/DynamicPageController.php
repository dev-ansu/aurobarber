<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Barbershop;
use App\Models\Page;
use App\Models\SaasPlan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DynamicPageController extends Controller
{
    public function show(Request $request, ?string $slug = null)
    {
        $normalizedSlug = trim((string) $slug, '/');
        $normalizedSlug = ($normalizedSlug === '' || $normalizedSlug === 'home') ? '/' : $normalizedSlug;

        // Se for um slug diferente de "/", checa se é uma página publicada do CMS primeiro
        $cacheKey = 'cms_page_' . md5($normalizedSlug);
        $cachedData = null;

        try {
            $cachedData = Cache::get($cacheKey);
        } catch (\Throwable) {
            Cache::forget($cacheKey);
            $cachedData = null;
        }

        $page = null;

        if (is_array($cachedData)) {
            $page = new Page();
            $page->setRawAttributes($cachedData, true);
        } elseif ($cachedData instanceof Page) {
            $page = $cachedData;
        }

        if (! $page) {
            try {
                $page = Page::query()
                    ->published()
                    ->bySlug($normalizedSlug)
                    ->first();

                if ($page) {
                    try {
                        Cache::put($cacheKey, $page->getAttributes(), 86400);
                    } catch (\Throwable) {
                    }
                }
            } catch (\Throwable) {
                $page = null;
            }
        }

        // Se não for a Home e não encontrou página no CMS, verifica se é uma barbearia pública
        if (! $page && $normalizedSlug !== '/') {
            $barbershop = Barbershop::query()
                ->where('slug', $normalizedSlug)
                ->first();

            if ($barbershop) {
                return app(BookingController::class)->show($barbershop->slug);
            }

            abort(404);
        }

        // Carrega planos SaaS ativos
        $activeSaasPlans = collect();
        try {
            $activeSaasPlans = SaasPlan::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();
        } catch (\Throwable) {
        }

        // Carrega barbearias ativas
        $activeBarbershops = collect();
        try {
            $activeBarbershops = Barbershop::query()
                ->where('is_active', true)
                ->whereNotNull('slug')
                ->orderBy('name')
                ->get(['id', 'name', 'slug', 'city', 'logo_url', 'brand_color']);
        } catch (\Throwable) {
        }

        // Carrega WhatsApp comercial
        $commercialWhatsapp = $this->resolveCommercialWhatsapp();

        // Se não tem página CMS para a Home, exibe a landing clássica como fallback
        if (! $page) {
            return view('welcome-aurobarber', [
                'activeSaasPlans' => $activeSaasPlans,
                'activeBarbershops' => $activeBarbershops,
                'commercialWhatsapp' => $commercialWhatsapp,
            ]);
        }

        return view('dynamic-page', [
            'page' => $page,
            'activeSaasPlans' => $activeSaasPlans,
            'activeBarbershops' => $activeBarbershops,
            'commercialWhatsapp' => $commercialWhatsapp,
        ]);
    }

    protected function resolveCommercialWhatsapp(): string
    {
        try {
            $rawPhone = User::query()
                ->where('role', 'master_admin')
                ->whereNotNull('phone')
                ->value('phone');

            if ($rawPhone) {
                $digits = preg_replace('/\D/', '', (string) $rawPhone);
                if (strlen($digits) >= 10) {
                    return str_starts_with($digits, '55') ? $digits : '55' . $digits;
                }
            }
        } catch (\Throwable) {
        }

        return env('AUROSOFT_COMMERCIAL_WHATSAPP', '5585999999999');
    }
}

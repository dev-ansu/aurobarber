<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\DynamicPageController;
use App\Http\Controllers\LandingLeadController;
use App\Http\Controllers\SaasPaymentProofController;
use App\Http\Controllers\TenantSaasPaymentController;
use App\Models\Appointment;
use App\Models\Barbershop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

// Redirecionamentos de conveniência e proteção de rotas
Route::redirect('/admin/saas', '/saas');

Route::middleware('auth')->group(function () {
    Route::post('/admin/{tenant}/assinatura/comprovante', [TenantSaasPaymentController::class, 'store'])
        ->name('tenant.billing.payments.store');

    Route::get('/saas/cobranca/comprovantes/{submission}', [SaasPaymentProofController::class, 'show'])
        ->middleware('signed')
        ->name('saas.billing.proofs.show');
});

Route::get('/admin/force-password-change', function () {
    if (! auth()->check()) {
        return redirect('/saas/login');
    }
    if (auth()->user()->role === 'master_admin') {
        return redirect('/saas/force-password-change');
    }
    $tenant = auth()->user()->tenants()->first();
    if ($tenant) {
        return redirect('/admin/'.$tenant->slug.'/force-password-change');
    }

    return redirect('/saas/login');
});

// 1. Landing Page Principal Dinâmica (CMS AuroBarber SaaS)
Route::get('/', [DynamicPageController::class, 'show'])->name('home');

// 1.1 Captura de Leads Comerciais da Landing Page
Route::post('/landing/lead', [LandingLeadController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('landing.lead.store');

// 1.2 Web App Manifest (PWA) — Painel Administrativo / Equipe
Route::get('/manifest.json', function (Request $request) {
    $tenantSlug = $request->query('tenant');
    $shop = $tenantSlug ? Barbershop::where('slug', $tenantSlug)->first() : null;

    $name = $shop ? "{$shop->name} • Gestão" : 'AuroBarber • Gestão';
    $shortName = $shop ? Str::limit($shop->name, 14, '') : 'AuroBarber';
    $startUrl = $shop ? "/admin/{$shop->slug}" : '/admin';
    $themeColor = ($shop && ! empty($shop->brand_color)) ? $shop->brand_color : '#d97706';

    $icons = [
        [
            'src' => asset('pwa-icon.svg'),
            'sizes' => 'any',
            'type' => 'image/svg+xml',
            'purpose' => 'any maskable',
        ],
    ];

    if ($shop && ! empty($shop->logo_url)) {
        $logoUrl = str_starts_with($shop->logo_url, 'http') ? $shop->logo_url : asset('storage/'.$shop->logo_url);
        array_unshift($icons, [
            'src' => $logoUrl,
            'sizes' => '192x192 512x512',
            'type' => 'image/png',
            'purpose' => 'any',
        ]);
    }

    return response()->json([
        'name' => $name,
        'short_name' => $shortName,
        'description' => 'Painel de Agenda, Comanda e Comissões — AuroBarber',
        'start_url' => $startUrl,
        'scope' => '/',
        'display' => 'standalone',
        'background_color' => '#020617',
        'theme_color' => $themeColor,
        'orientation' => 'portrait',
        'icons' => $icons,
    ])->header('Content-Type', 'application/manifest+json');
})->name('pwa.manifest.admin');

// 2. Avaliação Pós-Atendimento (1 a 5 Estrelas)
Route::get('/avaliar/{appointment}', function (Appointment $appointment) {
    $appointment->load(['barbershop', 'professional', 'service', 'services']);

    return view('public.rate-appointment', compact('appointment'));
})->middleware(['throttle:public-rating'])->name('appointment.rate.show');

Route::post('/avaliar/{appointment}', function (Request $request, Appointment $appointment) {
    if ($appointment->rated_at !== null) {
        return back()->with('rated_success', true)->with('already_rated', true);
    }

    $validated = $request->validate([
        'rating' => 'required|integer|min:1|max:5',
        'rating_comment' => 'nullable|string|max:500',
        'website_url' => 'nullable|string|max:0', // Honeypot
        'cf-turnstile-response' => [
            config('services.turnstile.site_key') ? 'required' : 'nullable',
            new \App\Rules\Turnstile('rating'),
        ],
    ], [
        'cf-turnstile-response.required' => 'A verificação de segurança é obrigatória.',
    ]);

    $appointment->update([
        'rating' => $validated['rating'],
        'rating_comment' => $validated['rating_comment'] ?? null,
        'rated_at' => now(),
    ]);

    return back()->with('rated_success', true);
})->middleware(['throttle:public-rating'])->name('appointment.rate.submit');

// 3. Rotas Públicas de Agendamento da Barbearia (ex: barber.aurosoft.com.br/marinho)
Route::get('/{slug}/manifest.json', function (string $slug) {
    $shop = Barbershop::where('slug', $slug)->firstOrFail();
    $themeColor = ! empty($shop->brand_color) ? $shop->brand_color : '#d97706';

    $icons = [
        [
            'src' => asset('pwa-icon.svg'),
            'sizes' => 'any',
            'type' => 'image/svg+xml',
            'purpose' => 'any maskable',
        ],
    ];

    if (! empty($shop->logo_url)) {
        $logoUrl = str_starts_with($shop->logo_url, 'http') ? $shop->logo_url : asset('storage/'.$shop->logo_url);
        array_unshift($icons, [
            'src' => $logoUrl,
            'sizes' => '192x192 512x512',
            'type' => 'image/png',
            'purpose' => 'any',
        ]);
    }

    return response()->json([
        'name' => "{$shop->name} • Agendamento Online",
        'short_name' => Str::limit($shop->name, 14, ''),
        'description' => $shop->headline ?: "Agende seu horário online na {$shop->name}",
        'start_url' => "/{$shop->slug}",
        'scope' => "/{$shop->slug}",
        'display' => 'standalone',
        'background_color' => '#020617',
        'theme_color' => $themeColor,
        'orientation' => 'portrait',
        'icons' => $icons,
    ])->header('Content-Type', 'application/manifest+json');
})->name('barbershop.manifest');

// Rota de Agendamento / Página Dinâmica CMS (com restrição para evitar colisão com painéis e rotas internas)
Route::get('/{slug}', [DynamicPageController::class, 'show'])
    ->where('slug', '^(?!admin|api|login|auth|saas|storage|up|pwa|livewire|[0-9]+$).*$')
    ->name('barbershop.show');

Route::get('/{slug}/slots', [BookingController::class, 'getAvailableSlots'])
    ->middleware('throttle:booking-slots')
    ->name('barbershop.slots');

Route::post('/{slug}/book', [BookingController::class, 'store'])
    ->middleware('throttle:booking-submission')
    ->name('barbershop.book');

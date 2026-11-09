<?php

use App\Http\Controllers\BookingController;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Redirecionamentos de conveniência e proteção de rotas
Route::redirect('/admin/saas', '/saas');

Route::get('/admin/force-password-change', function () {
    if (!auth()->check()) {
        return redirect('/saas/login');
    }
    if (auth()->user()->role === 'master_admin') {
        return redirect('/saas/force-password-change');
    }
    $tenant = auth()->user()->tenants()->first();
    if ($tenant) {
        return redirect('/admin/' . $tenant->slug . '/force-password-change');
    }
    return redirect('/saas/login');
});

// 1. Landing Page Principal do AuroBarber (SaaS B2B)
Route::get('/', function () {
    return view('welcome-aurobarber');
})->name('home');

// 1.1 Web App Manifest (PWA) — Painel Administrativo / Equipe
Route::get('/manifest.json', function (Request $request) {
    $tenantSlug = $request->query('tenant');
    $shop = $tenantSlug ? \App\Models\Barbershop::where('slug', $tenantSlug)->first() : null;

    $name = $shop ? "{$shop->name} • Gestão" : 'AuroBarber • Gestão';
    $shortName = $shop ? \Illuminate\Support\Str::limit($shop->name, 14, '') : 'AuroBarber';
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
        $logoUrl = str_starts_with($shop->logo_url, 'http') ? $shop->logo_url : asset('storage/' . $shop->logo_url);
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
})->name('appointment.rate.show');

Route::post('/avaliar/{appointment}', function (Request $request, Appointment $appointment) {
    $validated = $request->validate([
        'rating' => 'required|integer|min:1|max:5',
        'rating_comment' => 'nullable|string|max:500',
    ]);

    $appointment->update([
        'rating' => $validated['rating'],
        'rating_comment' => $validated['rating_comment'] ?? null,
        'rated_at' => now(),
    ]);

    return back()->with('rated_success', true);
})->name('appointment.rate.submit');

// 3. Rotas Públicas de Agendamento da Barbearia (ex: barber.aurosoft.com.br/marinho)
Route::get('/{slug}/manifest.json', function (string $slug) {
    $shop = \App\Models\Barbershop::where('slug', $slug)->firstOrFail();
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
        $logoUrl = str_starts_with($shop->logo_url, 'http') ? $shop->logo_url : asset('storage/' . $shop->logo_url);
        array_unshift($icons, [
            'src' => $logoUrl,
            'sizes' => '192x192 512x512',
            'type' => 'image/png',
            'purpose' => 'any',
        ]);
    }

    return response()->json([
        'name' => "{$shop->name} • Agendamento Online",
        'short_name' => \Illuminate\Support\Str::limit($shop->name, 14, ''),
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

Route::get('/{slug}', [BookingController::class, 'show'])->name('barbershop.show');
Route::get('/{slug}/slots', [BookingController::class, 'getAvailableSlots'])->name('barbershop.slots');
Route::post('/{slug}/book', [BookingController::class, 'store'])->name('barbershop.book');

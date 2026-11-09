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
Route::get('/{slug}', [BookingController::class, 'show'])->name('barbershop.show');
Route::get('/{slug}/slots', [BookingController::class, 'getAvailableSlots'])->name('barbershop.slots');
Route::post('/{slug}/book', [BookingController::class, 'store'])->name('barbershop.book');

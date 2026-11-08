<?php

use App\Models\Barbershop;
use Illuminate\Support\Facades\Route;

// 1. Landing Page Principal do AuroBarber (SaaS B2B)
Route::get('/', function () {
    return view('welcome-aurobarber');
})->name('home');

// 2. Página Pública / Agendamento Online da Barbearia Específica (ex: barber.aurosoft.com.br/marinho)
Route::get('/{slug}', function (string $slug) {
    $barbershop = Barbershop::where('slug', $slug)
        ->where('is_active', true)
        ->with(['professionals' => fn ($q) => $q->where('is_active', true), 'services' => fn ($q) => $q->where('is_active', true)])
        ->firstOrFail();

    return view('barbershop-public', compact('barbershop'));
})->name('barbershop.show');

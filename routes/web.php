<?php

use App\Http\Controllers\BookingController;
use Illuminate\Support\Facades\Route;

// Redirecionamento de conveniência para o SaaS Master
Route::redirect('/admin/saas', '/saas');

// 1. Landing Page Principal do AuroBarber (SaaS B2B)
Route::get('/', function () {
    return view('welcome-aurobarber');
})->name('home');

// 2. Rotas Públicas de Agendamento da Barbearia (ex: barber.aurosoft.com.br/marinho)
Route::get('/{slug}', [BookingController::class, 'show'])->name('barbershop.show');
Route::get('/{slug}/slots', [BookingController::class, 'getAvailableSlots'])->name('barbershop.slots');
Route::post('/{slug}/book', [BookingController::class, 'store'])->name('barbershop.book');

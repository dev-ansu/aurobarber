<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CashRegister;
use App\Models\School;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CashRegisterReportController extends Controller
{
    public function print(CashRegister $cashRegister): View
    {
        // Carrega o caixa com todas as transações, usuários e os meios de pagamento relacionados (Eager Loading)
        $cashRegister->load(['transactions.user', 'transactions.paymentMethod', 'operator']);
        $transactions = $cashRegister->transactions()
            ->with(['user', 'paymentMethod'])
            ->orderBy('created_at')
            ->get();

        // Agrupa e soma as entradas buscando o nome diretamente da tabela dinâmica de meios de pagamento[cite: 4]
        $resumoMeios = $cashRegister->transactions()
            ->where('cash_transactions.type', 'input')
            ->join('payment_methods', 'cash_transactions.payment_method_id', '=', 'payment_methods.id')
            ->select('payment_methods.name as payment_method', DB::raw('SUM(cash_transactions.amount) as total'))
            ->groupBy('payment_methods.name')
            ->get();

        return view('admin.reports.cash-register', [
            'caixa' => $cashRegister,
            'transacoes' => $transactions,
            'resumoMeios' => $resumoMeios,
            'escola' => $cashRegister->school
                ?? (class_exists(\Filament\Facades\Filament::class) && \Filament\Facades\Filament::getTenant() instanceof School ? \Filament\Facades\Filament::getTenant() : null)
                ?? School::getActiveSchool()
                ?? School::query()->withoutGlobalScopes()->first(),
        ]);
    }
}

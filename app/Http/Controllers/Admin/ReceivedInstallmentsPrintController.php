<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Filament\Pages\ReceivedInstallmentsReport;
use App\Http\Controllers\Controller;
use App\Models\Installment;
use App\Models\School;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReceivedInstallmentsPrintController extends Controller
{
    public function __invoke(Request $request): View
    {
        $startDate = Carbon::parse($request->query('start', now()->startOfMonth()->toDateString()))->startOfDay();
        $endDate = Carbon::parse($request->query('end', now()->endOfMonth()->toDateString()))->endOfDay();

        $schoolId = $request->query('school_id') ?? session('filament_tenant_id');
        $school = $schoolId
            ? School::query()->withoutGlobalScopes()->find((int) $schoolId)
            : ((class_exists(\Filament\Facades\Filament::class) && \Filament\Facades\Filament::getTenant() instanceof School ? \Filament\Facades\Filament::getTenant() : null)
                ?? School::getActiveSchool()
                ?? School::query()->withoutGlobalScopes()->first());

        $records = Installment::query()
            ->when($schoolId, fn ($q) => $q->forSchool((int) $schoolId))
            ->where(function ($q) {
                $q->where('status', 'paid')
                    ->orWhere(function ($sub) {
                        $sub->where('status', 'partial')
                            ->whereNotNull('amount_paid')
                            ->where('amount_paid', '>', 0);
                    });
            })
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('paid_at', [$startDate, $endDate])
                    ->orWhere(function ($sub) use ($startDate, $endDate) {
                        $sub->whereNull('paid_at')
                            ->whereBetween('updated_at', [$startDate, $endDate]);
                    });
            })
            ->with([
                'contract.student',
                'contract.responsible.person',
                'financialType',
                'paymentMethod',
            ])
            ->orderBy('paid_at', 'desc')
            ->get();

        $totalRecebido = (float) $records->sum('amount_paid');
        $totalMatriculas = (float) $records->filter(function (Installment $inst): bool {
            $slug = $inst->financialType?->slug ?? '';
            $name = mb_strtolower($inst->financialType?->name ?? '');

            return str_contains($slug, 'matricula') || str_contains($name, 'matr') || str_contains($slug, 'entrada')
                || ($inst->installment_number === 1 && ! str_contains($slug, 'mensalidade'));
        })->sum('amount_paid');

        $totalMensalidades = (float) $records->filter(function (Installment $inst): bool {
            $slug = $inst->financialType?->slug ?? '';

            return str_contains($slug, 'mensalidade') || $slug === 'tuition';
        })->sum('amount_paid');

        $alunosUnicos = $records->pluck('contract.student_id')->filter()->unique()->count();

        return view('admin.reports.received-installments-print', [
            'school' => $school,
            'records' => $records,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'totalRecebido' => $totalRecebido,
            'totalMatriculas' => $totalMatriculas,
            'totalMensalidades' => $totalMensalidades,
            'alunosUnicos' => $alunosUnicos,
            'generatedAt' => now()->format('d/m/Y H:i'),
        ]);
    }
}


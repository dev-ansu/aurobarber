<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Services\DreService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DrePrintController extends Controller
{
    public function __invoke(Request $request): View
    {
        $startDate = Carbon::parse($request->query('start', now()->startOfMonth()->toDateString()));
        $endDate = Carbon::parse($request->query('end', now()->endOfMonth()->toDateString()));
        $regime = $request->query('regime', 'caixa');

        $schoolId = $request->query('school_id') ?? session('filament_tenant_id');
        $school = $schoolId
            ? School::query()->withoutGlobalScopes()->find((int) $schoolId)
            : ((class_exists(\Filament\Facades\Filament::class) && \Filament\Facades\Filament::getTenant() instanceof School ? \Filament\Facades\Filament::getTenant() : null)
                ?? School::getActiveSchool()
                ?? School::query()->withoutGlobalScopes()->first());

        $dreService = app(DreService::class);
        $report = $dreService->generate($startDate, $endDate, $regime, $schoolId ? (int) $schoolId : null);

        return view('admin.reports.dre-print', [
            'report' => $report,
            'school' => $school,
            'generatedAt' => now()->format('d/m/Y H:i'),
        ]);
    }
}

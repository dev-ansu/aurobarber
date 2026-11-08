<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Installment;
use App\Models\School;
use App\Services\CurrencyInWords;
use Illuminate\Contracts\View\View;

class InstallmentReceiptController extends Controller
{
    public function __invoke(Installment $installment): View
    {
        $installment->loadMissing([
            'contract.student.documents',
            'contract.student.addresses.city.state',
            'contract.student.contacts',
            'contract.responsible.person.documents',
            'contract.responsible.person.contacts',
            'contract.items.formation',
            'contract.items.contractCourses.course',
            'financialType',
            'paymentMethod',
        ]);

        $contract = $installment->contract;
        $school = ($installment->school_id ? School::query()->withoutGlobalScopes()->with('partners')->find($installment->school_id)?->toContractSnapshot() : null)
            ?? $contract?->resolved_school
            ?? (class_exists(\Filament\Facades\Filament::class) && \Filament\Facades\Filament::getTenant() instanceof School ? \Filament\Facades\Filament::getTenant()->toContractSnapshot() : null)
            ?? School::getActiveSchool()?->toContractSnapshot()
            ?? School::query()->withoutGlobalScopes()->with('partners')->latest('id')->first()?->toContractSnapshot();

        $amountPaid = (float) ($installment->amount_paid ?: $installment->amount);
        $amountInWords = CurrencyInWords::toWords($amountPaid);

        return view('contracts.installment-receipt', compact(
            'installment',
            'contract',
            'school',
            'amountPaid',
            'amountInWords'
        ));
    }
}

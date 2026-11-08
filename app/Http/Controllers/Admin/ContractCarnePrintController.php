<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\School;
use App\Services\PixService;
use Illuminate\Contracts\View\View;

class ContractCarnePrintController extends Controller
{
    public function __invoke(Contract $contract): View
    {
        $user = auth()->user();
        if ($user) {
            $isStudentOwner = ($user->person_id !== null && (int) $user->person_id === (int) $contract->student_id)
                || ((int) session('portal_contract_id') === (int) $contract->id);
            $isStaff = $user->isStaff() || $user->isEducator() || $user->hasRole('super_admin');
            abort_unless($isStudentOwner || $isStaff, 403, 'Acesso não autorizado ao carnê.');
        }

        $contract->loadMissing([
            'student.contacts',
            'student.documents',
            'student.addresses.city.state',
            'responsible.person.contacts',
            'responsible.person.documents',
            'collaborator.person',
            'contractType',
            'items.formation',
            'items.contractCourses.course',
            'installments' => fn ($query) => $query->orderBy('installment_number')->orderBy('due_date'),
            'installments.financialType',
            'installments.paymentMethod',
        ]);

        $currentSchool = $contract->school
            ?? (class_exists(\Filament\Facades\Filament::class) && \Filament\Facades\Filament::getTenant() instanceof School ? \Filament\Facades\Filament::getTenant() : null)
            ?? School::getActiveSchool()
            ?? School::query()->with('partners')->first();
        $school = $contract->resolved_school;

        if (is_array($school) && empty($school['pix_key']) && ! empty($currentSchool?->pix_key)) {
            $school['pix_enabled'] = $currentSchool->pix_enabled;
            $school['pix_key'] = $currentSchool->pix_key;
            $school['pix_key_type'] = $currentSchool->pix_key_type;
            $school['pix_merchant_name'] = $currentSchool->pix_merchant_name;
            $school['pix_merchant_city'] = $currentSchool->pix_merchant_city;
            $school['pix_instructions'] = $currentSchool->pix_instructions;
        }

        if ($currentSchool && ! $currentSchool->pix_enabled && is_array($school)) {
            $school['pix_enabled'] = false;
        }

        $installments = $contract->installments;

        $pixService = app(PixService::class);
        $pixDataByInstallment = [];
        foreach ($installments as $installment) {
            $pixDataByInstallment[$installment->id] = $pixService->generateForInstallment($installment, $school);
        }

        return view('contracts.carne', compact('contract', 'school', 'installments', 'pixDataByInstallment'));
    }
}

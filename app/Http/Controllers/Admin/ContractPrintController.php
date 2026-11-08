<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;

class ContractPrintController extends Controller
{
    public function __invoke(Contract $contract)
    {
        // Carrega todas as relações necessárias para a impressão completa e jurídica
        $contract->loadMissing([
            'student.addresses.city.state',
            'student.documents',
            'student.contacts',
            'responsible.person.addresses.city.state',
            'responsible.person.documents',
            'responsible.person.contacts',
            'collaborator.person',
            'contractType',
            'installments.financialType',
            'items.formation.courses',
            'items.contractCourses.course',
            'items.schedules.classroom',
        ]);

        $school = $contract->resolved_school;
        $schoolRepresentative = collect($school['partners'] ?? [])
            ->first(fn (array $partner): bool => (bool) ($partner['legal_representative'] ?? false))['name'] ?? null;

        return view('contracts.print', compact('contract', 'school', 'schoolRepresentative'));
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\ContractType;
use App\Models\DebtAgreement;
use App\Models\StudentAttendance;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ContractDocumentController extends Controller
{
    public function declaracaoMatricula(Contract $contract): View
    {
        $user = auth()->user();
        if ($user) {
            $isStudentOwner = ($user->person_id !== null && (int) $user->person_id === (int) $contract->student_id)
                || ((int) session('portal_contract_id') === (int) $contract->id);
            $isStaff = $user->isStaff() || $user->isEducator() || $user->hasRole('super_admin');
            abort_unless($isStudentOwner || $isStaff, 403, 'Acesso não autorizado.');
        }

        $contract->loadMissing([
            'student.documents',
            'student.contacts',
            'student.addresses.city.state',
            'responsible.person.documents',
            'responsible.person.contacts',
            'items.formation',
            'items.contractCourses.course',
            'schedules.classroom',
        ]);

        $school = $contract->resolved_school;

        $schoolRepresentative = collect($school['partners'] ?? [])
            ->first(fn (array $partner): bool => (bool) ($partner['legal_representative'] ?? false))['name'] ?? null;

        $formations = $contract->items->map(fn ($item) => $item->formation?->name)->filter()->unique()->join(', ');
        $courses = $contract->items->flatMap(fn ($item) => $item->contractCourses->map(fn ($cc) => $cc->course?->name))->filter()->unique()->join(', ');
        $courseTitle = $formations ?: ($courses ?: 'Cursos Profissionalizantes');

        $totalWorkload = $contract->items->flatMap(fn ($i) => $i->contractCourses)->sum(fn ($cc) => (int) ($cc->course?->workload ?? 0));

        // Montagem amigável dos dias e horários
        $weekdaysMap = [
            1 => 'Segunda-feira',
            2 => 'Terça-feira',
            3 => 'Quarta-feira',
            4 => 'Quinta-feira',
            5 => 'Sexta-feira',
            6 => 'Sábado',
            0 => 'Domingo',
        ];
        $scheduleSummary = $contract->schedules->map(function ($s) use ($weekdaysMap) {
            $dia = $weekdaysMap[$s->weekday] ?? 'Dia '.$s->weekday;
            $inicio = Carbon::parse($s->start_hour)->format('H:i');
            $fim = Carbon::parse($s->end_hour)->format('H:i');
            $sala = $s->classroom?->name ? " ({$s->classroom->name})" : '';

            return "{$dia} das {$inicio} às {$fim}{$sala}";
        })->unique()->join('; ');

        return view('contracts.declaracao-matricula', compact(
            'contract',
            'school',
            'schoolRepresentative',
            'courseTitle',
            'totalWorkload',
            'scheduleSummary'
        ));
    }

    public function declaracaoQuitacao(Contract $contract, Request $request): View
    {
        $contract->loadMissing([
            'student.documents',
            'responsible.person.documents',
        ]);

        $school = $contract->resolved_school;

        $schoolRepresentative = collect($school['partners'] ?? [])
            ->first(fn (array $partner): bool => (bool) ($partner['legal_representative'] ?? false))['name'] ?? null;

        // Ano da declaração: padrão ano anterior ou ano corrente informado na query
        $selectedYear = (int) $request->query('year', now()->year > 2000 ? now()->year - 1 : now()->year);
        if ($selectedYear < 2000 || $selectedYear > now()->year) {
            $selectedYear = now()->year;
        }

        $installmentsOfYear = $contract->installments()
            ->whereYear('due_date', $selectedYear)
            ->orderBy('due_date')
            ->orderBy('installment_number')
            ->get();

        $isFullyPaid = $installmentsOfYear->isNotEmpty() && $installmentsOfYear->every(fn ($inst) => $inst->status === 'paid' || $inst->paid_at !== null);

        // Lista de anos disponíveis para seleção rápida
        $availableYears = $contract->installments()
            ->pluck('due_date')
            ->filter()
            ->map(fn ($d) => (int) Carbon::parse($d)->year)
            ->unique()
            ->sortDesc()
            ->values();

        if ($availableYears->isEmpty()) {
            $availableYears = collect([now()->year, now()->subYear()->year]);
        }

        return view('contracts.declaracao-quitacao', compact(
            'contract',
            'school',
            'schoolRepresentative',
            'selectedYear',
            'installmentsOfYear',
            'isFullyPaid',
            'availableYears'
        ));
    }

    public function fichaMatricula(Contract $contract): View
    {
        $contract->loadMissing([
            'student.documents',
            'student.contacts',
            'student.addresses.city.state',
            'responsible.person.documents',
            'responsible.person.contacts',
            'items.formation',
            'items.contractCourses.course',
            'schedules.classroom',
        ]);

        $school = $contract->resolved_school;

        $formations = $contract->items->map(fn ($item) => $item->formation?->name)->filter()->unique()->join(', ');
        $courses = $contract->items->flatMap(fn ($item) => $item->contractCourses->map(fn ($cc) => $cc->course?->name))->filter()->unique()->join(', ');
        $courseTitle = $formations ?: ($courses ?: 'Cursos Profissionalizantes');

        $totalWorkload = $contract->items->flatMap(fn ($i) => $i->contractCourses)->sum(fn ($cc) => (int) ($cc->course?->workload ?? 0));

        $weekdaysMap = [
            1 => 'Segunda-feira',
            2 => 'Terça-feira',
            3 => 'Quarta-feira',
            4 => 'Quinta-feira',
            5 => 'Sexta-feira',
            6 => 'Sábado',
            0 => 'Domingo',
        ];
        $scheduleSummary = $contract->schedules->map(function ($s) use ($weekdaysMap) {
            $dia = $weekdaysMap[$s->weekday] ?? 'Dia '.$s->weekday;
            $inicio = Carbon::parse($s->start_hour)->format('H:i');
            $fim = Carbon::parse($s->end_hour)->format('H:i');
            $sala = $s->classroom?->name ? " ({$s->classroom->name})" : '';

            return "{$dia} das {$inicio} às {$fim}{$sala}";
        })->unique()->join('; ');

        return view('contracts.ficha-matricula', compact(
            'contract',
            'school',
            'courseTitle',
            'totalWorkload',
            'scheduleSummary'
        ));
    }

    public function termoAcordo(Contract $contract, ?DebtAgreement $debtAgreement = null): View
    {
        if (! $debtAgreement || $debtAgreement->contract_id !== $contract->getKey()) {
            $debtAgreement = $contract->debtAgreements()->latest('id')->firstOrFail();
        }

        $contract->loadMissing([
            'student.documents',
            'responsible.person.documents',
            'responsible.person.addresses.city.state',
        ]);

        $school = $contract->resolved_school;

        $schoolRepresentative = collect($school['partners'] ?? [])
            ->first(fn (array $partner): bool => (bool) ($partner['legal_representative'] ?? false))['name'] ?? null;

        $originalInstallments = $debtAgreement->original_installments;
        $newInstallments = $debtAgreement->new_installments;

        return view('contracts.termo-acordo', compact(
            'contract',
            'debtAgreement',
            'school',
            'schoolRepresentative',
            'originalInstallments',
            'newInstallments'
        ));
    }

    public function relatorioFrequencia(Contract $contract, Request $request): View
    {
        $contract->loadMissing([
            'student.documents',
            'student.contacts',
            'student.addresses.city.state',
            'responsible.person.documents',
            'items.formation',
            'items.contractCourses.course',
            'schedules.classroom',
        ]);

        $school = $contract->resolved_school;

        $schoolRepresentative = collect($school['partners'] ?? [])
            ->first(fn (array $partner): bool => (bool) ($partner['legal_representative'] ?? false))['name'] ?? null;

        $formations = $contract->items->map(fn ($item) => $item->formation?->name)->filter()->unique()->join(', ');
        $courses = $contract->items->flatMap(fn ($item) => $item->contractCourses->map(fn ($cc) => $cc->course?->name))->filter()->unique()->join(', ');
        $courseTitle = $formations ?: ($courses ?: 'Cursos Profissionalizantes');

        $weekdaysMap = [
            1 => 'Segunda-feira',
            2 => 'Terça-feira',
            3 => 'Quarta-feira',
            4 => 'Quinta-feira',
            5 => 'Sexta-feira',
            6 => 'Sábado',
            0 => 'Domingo',
        ];

        $scheduleSummary = $contract->schedules->map(function ($s) use ($weekdaysMap) {
            $dia = $weekdaysMap[$s->weekday] ?? 'Dia '.$s->weekday;
            $inicio = Carbon::parse($s->start_hour)->format('H:i');
            $fim = Carbon::parse($s->end_hour)->format('H:i');
            $sala = $s->classroom?->name ? " ({$s->classroom->name})" : '';

            return "{$dia} das {$inicio} às {$fim}{$sala}";
        })->unique()->join('; ');

        $from = $request->query('from');
        $until = $request->query('until');
        $statusFilter = $request->query('status');

        $attendances = StudentAttendance::query()
            ->whereHas(
                'studentSchedule.contractItem',
                fn (Builder $query): Builder => $query->where('contract_id', $contract->getKey()),
            )
            ->when(filled($from), fn (Builder $q) => $q->whereDate('attendance_date', '>=', (string) $from))
            ->when(filled($until), fn (Builder $q) => $q->whereDate('attendance_date', '<=', (string) $until))
            ->when(in_array($statusFilter, ['present', 'absent'], true), fn (Builder $q) => $q->where('status', $statusFilter))
            ->with([
                'session.classroom',
                'studentSchedule.contractItem.formation',
                'markedBy.person',
            ])
            ->orderByDesc('attendance_date')
            ->orderByDesc('id')
            ->get();

        $totalClasses = $attendances->count();
        $presentCount = $attendances->where('status', 'present')->count();
        $absentCount = $totalClasses - $presentCount;
        $attendanceRate = $totalClasses > 0 ? round(($presentCount / $totalClasses) * 100, 1) : 0.0;

        return view('contracts.attendance-report', compact(
            'contract',
            'school',
            'schoolRepresentative',
            'courseTitle',
            'scheduleSummary',
            'attendances',
            'totalClasses',
            'presentCount',
            'absentCount',
            'attendanceRate',
            'from',
            'until',
            'statusFilter',
        ));
    }

    public function declaracaoComparecimento(Contract $contract, Request $request): View
    {
        $contract->loadMissing([
            'student.documents',
            'student.contacts',
            'student.addresses.city.state',
            'responsible.person.documents',
            'responsible.person.contacts',
            'items.formation',
            'items.contractCourses.course',
        ]);

        $school = $contract->resolved_school;
        $schoolRepresentative = collect($school['partners'] ?? [])
            ->first(fn (array $partner): bool => (bool) ($partner['legal_representative'] ?? false))['name'] ?? null;

        $formations = $contract->items->map(fn ($item) => $item->formation?->name)->filter()->unique()->join(', ');
        $courses = $contract->items->flatMap(fn ($item) => $item->contractCourses->map(fn ($cc) => $cc->course?->name))->filter()->unique()->join(', ');
        $courseTitle = $formations ?: ($courses ?: 'Cursos Profissionalizantes');

        $date = $request->query('date') ? Carbon::parse((string) $request->query('date')) : today();
        $startTime = (string) $request->query('start_time', '14:00');
        $endTime = (string) $request->query('end_time', '17:00');
        $purpose = (string) $request->query('purpose', 'atividades pedagógicas, aulas regulares e avaliações');

        $variables = array_merge($this->buildBaseDocumentVariables($contract, $school, $courseTitle), [
            'data_comparecimento' => $date->format('d/m/Y'),
            'horario_inicio' => $startTime,
            'horario_fim' => $endTime,
            'finalidade_comparecimento' => $purpose,
        ]);
        $customContent = $this->resolveCustomContent(ContractType::TYPE_ATTENDANCE, $variables);

        return view('contracts.declaracao-comparecimento', compact(
            'contract',
            'school',
            'schoolRepresentative',
            'courseTitle',
            'date',
            'startTime',
            'endTime',
            'purpose',
            'customContent',
        ));
    }

    public function termoCancelamento(Contract $contract, Request $request): View
    {
        $contract->loadMissing([
            'student.documents',
            'student.contacts',
            'student.addresses.city.state',
            'responsible.person.documents',
            'responsible.person.contacts',
            'items.formation',
            'items.contractCourses.course',
            'installments',
        ]);

        $school = $contract->resolved_school;
        $schoolRepresentative = collect($school['partners'] ?? [])
            ->first(fn (array $partner): bool => (bool) ($partner['legal_representative'] ?? false))['name'] ?? null;

        $formations = $contract->items->map(fn ($item) => $item->formation?->name)->filter()->unique()->join(', ');
        $courses = $contract->items->flatMap(fn ($item) => $item->contractCourses->map(fn ($cc) => $cc->course?->name))->filter()->unique()->join(', ');
        $courseTitle = $formations ?: ($courses ?: 'Cursos Profissionalizantes');

        $cancellationReason = (string) ($request->query('cancellation_reason') ?: ($contract->cancellation_reason ?: 'Rescisão em comum acordo por iniciativa do contratante.'));
        $cancellationDate = $contract->cancelled_at ? Carbon::parse($contract->cancelled_at) : today();

        // Identifica taxa rescisória se houver
        $feeAmount = (float) ($request->query('fee_amount')
            ?: ($contract->installments()->whereHas('financialType', fn ($q) => $q->where('slug', 'like', '%rescisao%')->orWhere('code', '1.2.1'))->value('amount') ?? 0.0));

        $feeFormatted = $feeAmount > 0 ? 'R$ ' . number_format($feeAmount, 2, ',', '.') : 'Isento de taxa rescisória';

        $variables = array_merge($this->buildBaseDocumentVariables($contract, $school, $courseTitle), [
            'data_cancelamento' => $cancellationDate->format('d/m/Y'),
            'motivo_cancelamento' => $cancellationReason,
            'valor_multa' => $feeFormatted,
        ]);
        $customContent = $this->resolveCustomContent(ContractType::TYPE_CANCELLATION, $variables);

        return view('contracts.termo-cancelamento', compact(
            'contract',
            'school',
            'schoolRepresentative',
            'courseTitle',
            'cancellationReason',
            'cancellationDate',
            'feeAmount',
            'customContent',
        ));
    }

    public function termoTrancamento(Contract $contract, Request $request): View
    {
        $contract->loadMissing([
            'student.documents',
            'student.contacts',
            'student.addresses.city.state',
            'responsible.person.documents',
            'responsible.person.contacts',
            'items.formation',
            'items.contractCourses.course',
        ]);

        $school = $contract->resolved_school;
        $schoolRepresentative = collect($school['partners'] ?? [])
            ->first(fn (array $partner): bool => (bool) ($partner['legal_representative'] ?? false))['name'] ?? null;

        $formations = $contract->items->map(fn ($item) => $item->formation?->name)->filter()->unique()->join(', ');
        $courses = $contract->items->flatMap(fn ($item) => $item->contractCourses->map(fn ($cc) => $cc->course?->name))->filter()->unique()->join(', ');
        $courseTitle = $formations ?: ($courses ?: 'Cursos Profissionalizantes');

        $startDate = $request->query('start_date')
            ? Carbon::parse((string) $request->query('start_date'))
            : ($contract->suspended_at ? Carbon::parse($contract->suspended_at) : today());

        $returnDate = $request->query('return_date')
            ? Carbon::parse((string) $request->query('return_date'))
            : ($contract->suspension_expected_return_date ? Carbon::parse($contract->suspension_expected_return_date) : $startDate->copy()->addDays(30));

        $days = (int) ($request->query('days') ?: max(1, $startDate->diffInDays($returnDate)));
        $reason = (string) ($request->query('reason') ?: ($contract->suspension_reason ?: 'motivos de ordem particular e temporária'));

        $variables = array_merge($this->buildBaseDocumentVariables($contract, $school, $courseTitle), [
            'dias_trancamento' => $days,
            'data_inicio_trancamento' => $startDate->format('d/m/Y'),
            'data_retorno_trancamento' => $returnDate->format('d/m/Y'),
            'motivo_trancamento' => $reason,
        ]);
        $customContent = $this->resolveCustomContent(ContractType::TYPE_SUSPENSION, $variables);

        return view('contracts.termo-trancamento', compact(
            'contract',
            'school',
            'schoolRepresentative',
            'courseTitle',
            'days',
            'startDate',
            'returnDate',
            'reason',
            'customContent',
        ));
    }

    public function autorizacaoImagem(Contract $contract, Request $request): View
    {
        $contract->loadMissing([
            'student.documents',
            'student.contacts',
            'student.addresses.city.state',
            'responsible.person.documents',
            'responsible.person.contacts',
            'items.formation',
            'items.contractCourses.course',
        ]);

        $school = $contract->resolved_school;
        $schoolRepresentative = collect($school['partners'] ?? [])
            ->first(fn (array $partner): bool => (bool) ($partner['legal_representative'] ?? false))['name'] ?? null;

        $formations = $contract->items->map(fn ($item) => $item->formation?->name)->filter()->unique()->join(', ');
        $courses = $contract->items->flatMap(fn ($item) => $item->contractCourses->map(fn ($cc) => $cc->course?->name))->filter()->unique()->join(', ');
        $courseTitle = $formations ?: ($courses ?: 'Cursos Profissionalizantes');

        $variables = $this->buildBaseDocumentVariables($contract, $school, $courseTitle);
        $customContent = $this->resolveCustomContent(ContractType::TYPE_IMAGE_RIGHTS, $variables);

        return view('contracts.autorizacao-imagem', compact(
            'contract',
            'school',
            'schoolRepresentative',
            'courseTitle',
            'customContent',
        ));
    }

    public function carteirinhaEstudantil(Contract $contract, Request $request): View
    {
        $contract->loadMissing([
            'student.documents',
            'student.contacts',
            'student.addresses.city.state',
            'responsible.person.documents',
            'items.formation',
            'items.contractCourses.course',
            'schedules.classroom',
        ]);

        $school = $contract->resolved_school;
        $formations = $contract->items->map(fn ($item) => $item->formation?->name)->filter()->unique()->join(', ');
        $courses = $contract->items->flatMap(fn ($item) => $item->contractCourses->map(fn ($cc) => $cc->course?->name))->filter()->unique()->join(', ');
        $courseTitle = $formations ?: ($courses ?: 'Cursos Profissionalizantes');

        $validUntil = $contract->end_date ? Carbon::parse($contract->end_date) : today()->endOfYear();

        return view('contracts.carteirinha-estudantil', compact(
            'contract',
            'school',
            'courseTitle',
            'validUntil',
        ));
    }

    protected function buildBaseDocumentVariables(Contract $contract, array $school, string $courseTitle): array
    {
        $student = $contract->student;
        $studentName = $student?->name ?? 'Não informado';
        $studentCpf = $student?->documents?->firstWhere('type', 'cpf')?->document_number
            ?: ($student?->document_number ?? '');
        $studentRg = $student?->documents?->firstWhere('type', 'rg')?->document_number ?: '';

        $resp = $contract->responsible?->person;
        $respName = $resp?->name ?: $studentName;
        $respCpf = $resp?->documents?->firstWhere('type', 'cpf')?->document_number
            ?: ($resp?->document_number ?: $studentCpf);

        $schoolName = $school['trade_name'] ?? ($school['name'] ?? config('app.name', 'Aurosoft LMS'));
        $schoolLegal = $school['legal_name'] ?? $schoolName;
        $schoolCnpj = $school['cnpj'] ?? '';
        $schoolAddress = $school['address'] ?? '';
        $city = $school['city'] ?? 'Fortaleza';
        $state = $school['state'] ?? 'CE';

        return [
            'aluno_nome' => $studentName,
            'aluno' => $studentName,
            'cpf_aluno' => $studentCpf,
            'rg_aluno' => $studentRg,
            'responsavel_nome' => $respName,
            'responsavel' => $respName,
            'cpf_responsavel' => $respCpf,
            'escola_nome' => $schoolName,
            'escola' => $schoolName,
            'escola_razao_social' => $schoolLegal,
            'cnpj_escola' => $schoolCnpj,
            'endereco_escola' => $schoolAddress,
            'cidade_escola' => $city,
            'estado_escola' => $state,
            'curso_nome' => $courseTitle,
            'curso' => $courseTitle,
            'numero_contrato' => $contract->contract_number,
            'valor_total' => 'R$ ' . number_format((float) $contract->total_value, 2, ',', '.'),
        ];
    }

    protected function resolveCustomContent(string $documentType, array $variables): ?string
    {
        $template = ContractType::getActiveForType($documentType);
        if (! $template || blank($template->content)) {
            return null;
        }

        $map = [];
        foreach ($variables as $key => $val) {
            $str = is_scalar($val) || is_null($val) ? (string) $val : '';
            $map['{' . strtolower((string) $key) . '}'] = $str;
            $map['{' . strtoupper((string) $key) . '}'] = $str;
        }

        return strtr($template->content, $map);
    }
}

<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Boletim Escolar{{ $selectedCourse ? ' — '.$selectedCourse->course?->name : '' }} | {{ $contract->student?->name }}</title>
    <style>
        :root {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: #0f172a;
            background: #f8fafc;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            padding: 30px 15px;
            color: #1e293b;
        }

        .page {
            max-width: 960px;
            margin: 0 auto;
            background: #ffffff;
            padding: 35px 40px;
            border-radius: 10px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }

        .no-print-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            max-width: 960px;
            margin-left: auto;
            margin-right: auto;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            border: none;
            transition: all 0.2s;
        }

        .btn-primary {
            background-color: #2563eb;
            color: #ffffff;
        }
        .btn-primary:hover {
            background-color: #1d4ed8;
        }

        .btn-secondary {
            background-color: #e2e8f0;
            color: #334155;
        }
        .btn-secondary:hover {
            background-color: #cbd5e1;
        }

        /* CABEÇALHO INSTITUCIONAL OFICIAL */
        .institution-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 15px;
            margin-bottom: 18px;
        }

        .institution-brand h1 {
            font-size: 20px;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: -0.3px;
            text-transform: uppercase;
        }

        .institution-brand p {
            font-size: 11px;
            color: #475569;
            margin-top: 3px;
            line-height: 1.4;
        }

        .doc-title-box {
            text-align: right;
        }

        .doc-title-badge {
            display: inline-block;
            background: #0f172a;
            color: #ffffff;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 4px 12px;
            border-radius: 4px;
        }

        .doc-subtitle {
            font-size: 10px;
            color: #64748b;
            margin-top: 4px;
            font-weight: 600;
        }

        /* GRID DE IDENTIFICAÇÃO DO ALUNO */
        .section-box {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 12px 16px;
            margin-bottom: 16px;
            background: #ffffff;
        }

        .section-box-title {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #475569;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 5px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px 14px;
        }

        .info-item {
            font-size: 11px;
        }

        .info-item span {
            display: block;
            color: #64748b;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 2px;
        }

        .info-item strong {
            color: #0f172a;
            font-size: 12px;
            font-weight: 700;
            word-break: break-word;
        }

        /* RÉGUA DE RESUMO E METRICAS */
        .summary-strip {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 18px;
        }

        .summary-card {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 14px;
            background: #f8fafc;
            text-align: center;
        }

        .summary-card .number {
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.1;
        }

        .summary-card .label {
            font-size: 10px;
            color: #64748b;
            font-weight: 700;
            text-transform: uppercase;
            margin-top: 4px;
        }

        /* TABELA DE RENDIMENTO ESCOLAR */
        .grades-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            margin-bottom: 16px;
        }

        .grades-table th {
            background-color: #f1f5f9;
            color: #334155;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 10px;
            padding: 8px 10px;
            border: 1px solid #cbd5e1;
            text-align: left;
        }

        .grades-table td {
            padding: 8px 10px;
            border: 1px solid #cbd5e1;
            color: #1e293b;
            vertical-align: middle;
        }

        .grades-table tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .grades-table tfoot td {
            background-color: #f1f5f9;
            font-weight: 800;
            border-top: 2px solid #0f172a;
        }

        .badge-status {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .badge-completed {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .badge-progress {
            background-color: #e0f2fe;
            color: #0369a1;
            border: 1px solid #bae6fd;
        }

        .badge-pending {
            background-color: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
        }

        /* SEÇÃO E TABELA DE ATIVIDADES LMS (AULAS E TESTES) */
        .lms-section {
            margin-top: 18px;
            page-break-inside: avoid;
        }

        .lms-details-box {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 14px 16px;
            background: #ffffff;
            margin-top: 10px;
        }

        .om-activities {
            background: #ffffff !important;
            border: none !important;
            padding: 0 !important;
        }

        .om-activities__header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }

        .om-activities__header h4 {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            color: #334155;
            margin: 0;
        }

        .om-activities__header p, .om-activities__header span {
            font-size: 10px;
            color: #64748b;
            margin: 0;
        }

        .om-activities__table-wrap {
            width: 100%;
            overflow-x: auto;
            margin-top: 8px;
        }

        .om-activities__table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            text-align: left;
        }

        .om-activities__table th {
            background-color: #f1f5f9;
            color: #334155;
            font-weight: 800;
            text-transform: uppercase;
            font-size: 10px;
            padding: 10px 12px !important;
            border: 1px solid #cbd5e1;
            white-space: nowrap;
        }

        .om-activities__table td {
            padding: 9px 12px !important;
            border: 1px solid #cbd5e1;
            color: #1e293b;
            vertical-align: middle;
        }

        .om-activities__table tr:nth-child(even) td {
            background-color: #f8fafc;
        }

        .om-activities__number {
            text-align: center;
            font-weight: 700;
            color: #64748b;
            width: 38px;
        }

        .om-activities__test-status {
            display: block;
            font-size: 9px;
            color: #64748b;
            margin-top: 2px;
        }

        /* CRITÉRIOS DE AVALIAÇÃO */
        .legend-box {
            font-size: 10px;
            color: #64748b;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 8px 12px;
            line-height: 1.5;
            margin-top: 14px;
            margin-bottom: 25px;
        }

        /* ASSINATURAS */
        .signatures-section {
            margin-top: 35px;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .signatures-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
            text-align: center;
        }

        .sign-line {
            border-top: 1px solid #334155;
            margin-bottom: 4px;
        }

        .sign-title {
            font-size: 11px;
            font-weight: 800;
            color: #0f172a;
        }

        .sign-subtitle {
            font-size: 10px;
            color: #64748b;
        }

        .doc-footer {
            margin-top: 25px;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            font-size: 9px;
            color: #94a3b8;
        }

        @page {
            size: A4 portrait;
            margin: 10mm 12mm;
        }

        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
            }

            .no-print-toolbar {
                display: none !important;
            }

            .page {
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                max-width: none !important;
                margin: 0 !important;
            }

            .institution-header {
                border-bottom: 2px solid #000000 !important;
            }

            .section-box, .grades-table th, .grades-table td, .om-activities__table th, .om-activities__table td {
                border-color: #94a3b8 !important;
            }

            .om-activities__table th, .om-activities__table td {
                padding: 6px 8px !important;
            }
        }
    </style>
</head>
<body>

    @php
        $schoolName = $school['trade_name'] ?? $school['legal_name'] ?? 'Aurosoft Educacional';
        $schoolLegal = $school['legal_name'] ?? $schoolName;
        $schoolCnpj = $school['cnpj'] ?? null;
        $schoolPhone = $school['phone'] ?? null;
        $schoolEmail = $school['email'] ?? null;
        $schoolCity = ! empty($school['city'])
            ? $school['city']
            : ($contract->student?->addresses?->first()?->city?->name ?? 'Município');
        $schoolState = ! empty($school['state'])
            ? $school['state']
            : ($contract->student?->addresses?->first()?->city?->state?->acronym ?? 'UF');
        $schoolAddress = collect([
            trim(implode(' ', array_filter([$school['street'] ?? null, $school['number'] ?? null]))),
            $school['neighborhood'] ?? null,
            trim(implode(' - ', array_filter([$schoolCity, $schoolState]))),
        ])->filter()->implode(', ');

        $student = $contract->student;
        $studentCpf = $student?->documents?->where('type', 'cpf')->first()?->number
            ?? $student?->cpf
            ?? 'Não informado';

        $studentPhone = $student?->contacts?->firstWhere('type', \App\Enums\ContactTypeEnum::WhatsApp)?->value
            ?? $student?->contacts?->firstWhere('type', \App\Enums\ContactTypeEnum::Celular)?->value
            ?? $student?->contacts?->first()?->value
            ?? '—';

        $studentEmail = $student?->contacts?->firstWhere('type', \App\Enums\ContactTypeEnum::Email)?->value
            ?? $student?->email
            ?? '—';

        $studentCode = $student?->ouro_moderno_cod_int ?: $student?->id;

        $responsible = $contract->responsible?->person;
        $formationName = $contract->items->first()?->formation?->name ?? 'Curso de Formação Profissional';

        $items = $selectedCourse
            ? collect([$selectedCourse->contractItem])->filter()
            : $contract->items;

        $courseLinks = $selectedCourse
            ? collect([$selectedCourse])
            : $items->flatMap(fn ($item) => $item->contractCourses);

        $completedCourses = $courseLinks->filter(fn ($l) => $l->status === 'completed' || $l->completed_at !== null)->count();
        $totalCourses = $courseLinks->count();

        // Médias (normalizadas na escala de 0,0 a 10,0 usada nas normas regimentais do boletim)
        $validGrades = $courseLinks->map(function ($l) {
            if (! is_numeric($l->grade)) return null;
            $g = (float) $l->grade;
            return $g > 10 ? round($g / 10, 1) : $g;
        })->filter();
        $averageGrade = $validGrades->isNotEmpty() ? round($validGrades->avg(), 1) : null;

        // Carga horária
        $totalWorkload = $courseLinks->sum(fn ($l) => (int) ($l->course?->workload ?? 0));
        $completedWorkload = $courseLinks->filter(fn ($l) => $l->status === 'completed' || $l->completed_at !== null)
            ->sum(fn ($l) => (int) ($l->course?->workload ?? 0));

        // Frequência
        $attTotal = $attendanceData['total'] ?? 0;
        $attPresent = $attendanceData['present'] ?? 0;
        $attAbsent = $attendanceData['absent'] ?? 0;
        $attRate = $attendanceData['rate'] ?? null;

        // Status Geral de Conclusão / Aprovação
        $isAllCompleted = ($totalCourses > 0 && $completedCourses === $totalCourses);
        $isApproved = ($isAllCompleted && ($averageGrade === null || $averageGrade >= 7.0));
    @endphp

    <div class="no-print-toolbar">
        <div>
            <strong style="color: #0f172a; font-size: 14px;">Boletim Escolar Oficial:</strong>
            <span style="color: #64748b; font-size: 13px;">{{ $student?->name ?? 'Aluno' }} &middot; Contrato {{ $contract->contract_number }}</span>
        </div>
        <div style="display: flex; gap: 10px;">
            <button onclick="window.print()" class="btn btn-primary">
                <svg width="15" height="15" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
                    <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/>
                </svg>
                Imprimir Boletim
            </button>
            @if(Auth::check() && ! Auth::user()->isStaff() && ! Auth::user()->isEducator() && ! Auth::user()->hasRole('super_admin'))
                <a href="{{ route('student.portal') }}" class="btn btn-secondary">Voltar ao Portal</a>
            @endif
            <a href="javascript:window.close()" class="btn btn-secondary">Fechar</a>
        </div>
    </div>

    <main class="page">

        <!-- CABEÇALHO INSTITUCIONAL -->
        <header class="institution-header">
            <div class="institution-brand">
                <h1>{{ $schoolName }}</h1>
                @if($schoolLegal && $schoolLegal !== $schoolName)
                    <div style="font-size: 10px; color: #64748b; font-weight: 600;">{{ $schoolLegal }}</div>
                @endif
                <p>
                    @if($schoolCnpj) CNPJ: {{ $schoolCnpj }} &middot; @endif
                    @if($schoolPhone) Fone: {{ $schoolPhone }} &middot; @endif
                    @if($schoolEmail) {{ $schoolEmail }} @endif
                </p>
                @if($schoolAddress)
                    <p>{{ $schoolAddress }}</p>
                @endif
            </div>

            <div class="doc-title-box">
                <div class="doc-title-badge">Boletim Escolar</div>
                <div class="doc-subtitle">{{ $selectedCourse ? 'Aproveitamento Individual do Módulo' : 'Histórico Acadêmico e Frequência' }}</div>
                <div style="font-size: 9px; color: #94a3b8; margin-top: 3px;">Emitido em {{ now()->format('d/m/Y \à\s H:i') }}</div>
            </div>
        </header>

        <!-- IDENTIFICAÇÃO DO ALUNO -->
        <section class="section-box">
            <div class="section-box-title">
                <span>Identificação do Aluno e Matrícula</span>
                <span style="font-size: 9px; color: #64748b; font-weight: 700;">REGISTRO ACADÊMICO</span>
            </div>

            <div class="info-grid">
                <div class="info-item" style="grid-column: span 2;">
                    <span>Nome Completo do Aluno</span>
                    <strong style="font-size: 13px;">{{ $student?->name ?? 'Não identificado' }}</strong>
                </div>

                <div class="info-item">
                    <span>CPF / Documento</span>
                    <strong>{{ $studentCpf }}</strong>
                </div>

                <div class="info-item">
                    <span>Matrícula / Código</span>
                    <strong>{{ $studentCode }}</strong>
                </div>

                <div class="info-item" style="grid-column: span 2;">
                    <span>Formação / Programa Matriculado</span>
                    <strong>{{ $formationName }}</strong>
                </div>

                <div class="info-item">
                    <span>Nº do Contrato</span>
                    <strong>{{ $contract->contract_number ?? '—' }}</strong>
                </div>

                <div class="info-item">
                    <span>Situação do Contrato</span>
                    <strong style="text-transform: uppercase;">{{ match($contract->status) { 'active' => 'Ativo', 'finished' => 'Concluído', 'canceled' => 'Cancelado', default => ucfirst($contract->status) } }}</strong>
                </div>

                @if($responsible && $responsible->name !== $student?->name)
                    <div class="info-item" style="grid-column: span 2;">
                        <span>Responsável Legal / Financeiro</span>
                        <strong>{{ $responsible->name }}</strong>
                    </div>
                @endif

                <div class="info-item">
                    <span>Contato / WhatsApp</span>
                    <strong>{{ $studentPhone }}</strong>
                </div>

                <div class="info-item">
                    <span>Período do Curso</span>
                    <strong>{{ $contract->start_date ? $contract->start_date->format('d/m/Y') : 'Início' }} &rarr; {{ $contract->end_date ? $contract->end_date->format('d/m/Y') : 'Conclusão' }}</strong>
                </div>
            </div>
        </section>

        <!-- RÉGUA DE INDICADORES GERAIS -->
        <section class="summary-strip">
            <div class="summary-card">
                <div class="number">{{ $completedCourses }} / {{ $totalCourses }}</div>
                <div class="label">Módulos Concluídos</div>
            </div>

            <div class="summary-card">
                <div class="number">{{ $completedWorkload }}h <span style="font-size: 11px; color: #64748b; font-weight: 500;">/ {{ $totalWorkload }}h</span></div>
                <div class="label">Carga Horária Cumprida</div>
            </div>

            <div class="summary-card">
                <div class="number" style="color: {{ $averageGrade !== null ? ($averageGrade >= 7.0 ? '#166534' : '#b45309') : '#0f172a' }};">
                    {{ $averageGrade !== null ? number_format($averageGrade, 1, ',', '.') : '—' }}
                </div>
                <div class="label">Média Geral (GPA)</div>
            </div>

            <div class="summary-card">
                <div class="number" style="color: {{ $attRate !== null ? ($attRate >= 75.0 ? '#166534' : '#dc2626') : '#0f172a' }};">
                    {{ $attRate !== null ? $attRate.'%' : ($attTotal > 0 ? '—' : '100%') }}
                </div>
                <div class="label">
                    {{ $attTotal > 0 ? "Frequência ({$attPresent}/{$attTotal} aulas)" : 'Frequência Regular' }}
                </div>
            </div>
        </section>

        <!-- QUADRO DE RENDIMENTO ESCOLAR POR DISCIPLINA / CURSO -->
        <section class="section-box" style="padding: 0; overflow: hidden;">
            <div style="padding: 10px 14px; background: #f8fafc; border-bottom: 1px solid #cbd5e1; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #334155;">Quadro de Rendimento por Disciplina / Módulo</h3>
                <span style="font-size: 10px; color: #64748b; font-weight: 600;">Critério Mínimo: Média 7,0 &middot; Frequência 75%</span>
            </div>

            <table class="grades-table">
                <thead>
                    <tr>
                        <th style="width: 38%;">Disciplina / Conteúdo Programático</th>
                        <th style="width: 14%; text-align: center;">Carga Horária</th>
                        <th style="width: 14%; text-align: center;">Nota / Média</th>
                        <th style="width: 18%; text-align: center;">Situação Acadêmica</th>
                        <th style="width: 16%; text-align: center;">Data Conclusão</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($courseLinks as $link)
                        @php
                            $isComp = $link->status === 'completed' || $link->completed_at !== null;
                            $gradeNum = is_numeric($link->grade) ? (float)$link->grade : null;
                        @endphp
                        <tr>
                            <td>
                                <strong style="color: #0f172a; font-size: 12px;">{{ $link->course?->name ?? 'Curso' }}</strong>
                                @if($link->contractItem?->formation && ! $selectedCourse)
                                    <div style="font-size: 9px; color: #64748b;">{{ $link->contractItem->formation->name }}</div>
                                @endif
                            </td>
                            <td style="text-align: center; font-weight: 700; color: #334155;">
                                {{ $link->course?->workload ? $link->course->workload.' h' : '—' }}
                            </td>
                            <td style="text-align: center; font-weight: 800; color: {{ $gradeNum !== null ? ($gradeNum >= 7.0 ? '#166534' : '#dc2626') : '#64748b' }}; font-size: 12px;">
                                {{ $gradeNum !== null ? number_format($gradeNum, 1, ',', '.') : ($link->grade ?? '—') }}
                            </td>
                            <td style="text-align: center;">
                                @if($isComp)
                                    <span class="badge-status badge-completed">Aprovado / Concluído</span>
                                @elseif($link->status === 'in_progress')
                                    <span class="badge-status badge-progress">Em Andamento</span>
                                @else
                                    <span class="badge-status badge-pending">Não Iniciado</span>
                                @endif
                            </td>
                            <td style="text-align: center; font-size: 10px; color: #475569;">
                                {{ $link->completed_at ? $link->completed_at->format('d/m/Y') : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 20px; color: #64748b;">
                                Nenhuma disciplina vinculada a este contrato.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <td style="padding: 9px 10px;">TOTAL GERAL / RESULTADO FINAL</td>
                        <td style="text-align: center; color: #0f172a;">{{ $totalWorkload }} h</td>
                        <td style="text-align: center; color: {{ $averageGrade !== null && $averageGrade >= 7.0 ? '#166534' : '#0f172a' }}; font-size: 12px;">
                            {{ $averageGrade !== null ? number_format($averageGrade, 1, ',', '.') : '—' }}
                        </td>
                        <td style="text-align: center;">
                            @if($isApproved)
                                <span class="badge-status badge-completed" style="font-size: 10px; font-weight: 800;">APROVADO REGULAR</span>
                            @elseif($isAllCompleted)
                                <span class="badge-status badge-completed" style="font-size: 10px; font-weight: 800;">CONCLUÍDO</span>
                            @else
                                <span class="badge-status badge-progress" style="font-size: 10px; font-weight: 800;">CURSANDO</span>
                            @endif
                        </td>
                        <td style="text-align: center; font-size: 9px; color: #64748b;">
                            {{ $completedCourses }} de {{ $totalCourses }} concl.
                        </td>
                    </tr>
                </tfoot>
            </table>
        </section>

        <!-- SEÇÃO DE ATIVIDADES DETALHADAS DO LMS (QUANDO HOUVER) -->
        @php
            $hasLmsData = $courseLinks->contains(fn ($l) => filled($l->ouro_moderno_lesson_status) || filled($l->ouro_moderno_test_status) || filled($l->ouro_moderno_status));
        @endphp

        @if($hasLmsData)
            <section class="section-box lms-section">
                <div class="section-box-title">
                    <span>Atividades Digitais Interativas (Laboratório / LMS)</span>
                    <span style="font-size: 9px; color: #64748b; font-weight: 700;">PROGRESSO COMPUTACIONAL</span>
                </div>

                @foreach($courseLinks as $courseLink)
                    @if(filled($courseLink->ouro_moderno_lesson_status) || filled($courseLink->ouro_moderno_test_status) || filled($courseLink->ouro_moderno_status))
                        <div class="lms-details-box">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                <strong style="font-size: 11px; color: #0f172a;">{{ $courseLink->course?->name }}</strong>
                                <span style="font-size: 10px; color: #64748b;">
                                    Última sincronização: {{ $courseLink->ouro_moderno_status_checked_at?->format('d/m/Y H:i') ?? 'Registrado' }}
                                </span>
                            </div>

                            <x-ouro-moderno-activities
                                :lessons="$courseLink->ouro_moderno_lesson_status"
                                :tests="$courseLink->ouro_moderno_test_status"
                                :lesson-checked-at="$courseLink->ouro_moderno_lesson_status_checked_at"
                                :test-checked-at="$courseLink->ouro_moderno_test_status_checked_at"
                            />
                        </div>
                    @endif
                @endforeach
            </section>
        @endif

        <!-- CRITÉRIOS DE AVALIAÇÃO E LEGENDA -->
        <div class="legend-box">
            <strong>Critérios de Avaliação e Normas Regimentais:</strong><br>
            1. Escala de avaliação de 0,0 a 10,0. Nota mínima para aprovação no módulo: <strong>7,0 (sete)</strong>.<br>
            2. Frequência mínima exigida para aproveitamento curricular: <strong>75% (setenta e cinco por cento)</strong> da carga horária presencial.<br>
            3. A carga horária expressa neste boletim é computada em horas-aula de 60 minutos, em conformidade com as diretrizes de cursos livres e formação profissionalizante.
        </div>

        <!-- ASSINATURAS E CHANCELA INSTITUCIONAL -->
        <section class="signatures-section">
            <div class="signatures-grid">
                <div>
                    <div class="sign-line"></div>
                    <div class="sign-title">Coordenação Pedagógica</div>
                    <div class="sign-subtitle">{{ $schoolName }}</div>
                </div>

                <div>
                    <div class="sign-line"></div>
                    <div class="sign-title">Secretaria Escolar / Direção</div>
                    <div class="sign-subtitle">Visto e Registro Regimental</div>
                </div>
            </div>
        </section>

        <!-- RODAPÉ COM AUTENTICIDADE -->
        <footer class="doc-footer">
            <span>{{ $schoolCity }} - {{ $schoolState }}, {{ now()->translatedFormat('d \d\e F \d\e Y') }}</span>
            <span>Autenticação Regimental: AUT-BOL-{{ $contract->contract_number }}-{{ $studentCode }}-{{ now()->format('Ymd') }}</span>
            <span>Documento oficial expedido pelo sistema Aurosoft</span>
        </footer>

    </main>

</body>
</html>

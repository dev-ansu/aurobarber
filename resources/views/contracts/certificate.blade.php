<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificado - {{ $contract->student?->name }} - {{ $courseTitle }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 6mm;
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body {
            font-family: "Georgia", "Times New Roman", serif;
            color: #1a202c;
            background-color: #f1f5f9;
            margin: 0;
            padding: 15px;
        }

        .no-print {
            max-width: 1080px;
            margin: 0 auto 15px auto;
            background: #ffffff;
            border-radius: 8px;
            padding: 12px 20px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-family: Arial, sans-serif;
            font-size: 13px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            border: 1px solid transparent;
            transition: all 0.2s;
        }

        .btn-primary {
            background-color: #0f172a;
            color: #ffffff;
        }

        .btn-primary:hover {
            background-color: #1e293b;
        }

        .btn-secondary {
            background-color: #ffffff;
            color: #334155;
            border-color: #cbd5e1;
        }

        .btn-secondary:hover {
            background-color: #f8fafc;
        }

        /* ---------------- PÁGINAS DO CERTIFICADO (FRENTE E VERSO) ---------------- */
        .cert-container {
            width: 285mm;
            height: 198mm;
            margin: 0 auto 20px auto;
            background: #ffffff;
            position: relative;
            box-shadow: 0 10px 25px rgba(0,0,0,0.12);
            padding: 6mm;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
        }

        .cert-page-badge {
            position: absolute;
            top: 10px;
            right: 15px;
            background: #e2e8f0;
            color: #475569;
            font-family: Arial, sans-serif;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 4px;
            text-transform: uppercase;
        }

        /* Borda Ornamental Dupla Clássica da Frente */
        .cert-outer-border {
            border: 4px solid #1e3a8a;
            padding: 4mm;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
        }

        .cert-inner-border {
            border: 1.5px solid #d97706;
            padding: 6mm 10mm;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background: radial-gradient(circle at center, #ffffff 60%, #fbfcfe 100%);
            position: relative;
        }

        /* Marca d'água discreta de fundo */
        .cert-watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-25deg);
            font-size: 80px;
            font-weight: 900;
            color: rgba(30, 58, 138, 0.03);
            text-transform: uppercase;
            pointer-events: none;
            letter-spacing: 10px;
            white-space: nowrap;
        }

        /* Cabeçalho do Certificado */
        .cert-header {
            text-align: center;
        }

        .school-name {
            font-size: 18px;
            font-weight: 700;
            color: #1e3a8a;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin: 0 0 2px 0;
        }

        .school-details {
            font-family: Arial, sans-serif;
            font-size: 9px;
            color: #64748b;
            letter-spacing: 0.5px;
        }

        .cert-title-container {
            margin: 10px 0 4px 0;
            position: relative;
        }

        .cert-title {
            font-size: 27px;
            font-weight: 700;
            color: #b45309;
            text-transform: uppercase;
            letter-spacing: 4px;
            margin: 0;
        }

        .cert-subtitle {
            font-family: Arial, sans-serif;
            font-size: 10.5px;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-top: 2px;
        }

        /* Corpo do Texto de Concessão */
        .cert-body {
            text-align: center;
            padding: 0 8mm;
            margin: 8px 0;
            font-size: 13.5px;
            line-height: 1.8;
            color: #1e293b;
        }

        .student-name {
            font-size: 23px;
            font-weight: 700;
            color: #0f172a;
            border-bottom: 2px solid #b45309;
            padding: 0 15px 2px 15px;
            display: inline-block;
            margin: 3px 0;
            font-style: italic;
        }

        .course-highlight {
            font-weight: 700;
            color: #1e3a8a;
            font-size: 16px;
        }

        .data-highlight {
            font-weight: 700;
            color: #0f172a;
        }

        /* Rodapé com Assinaturas */
        .cert-footer {
            margin-top: 10px;
        }

        .cert-date-location {
            text-align: center;
            font-size: 11.5px;
            color: #475569;
            margin-bottom: 20px;
            font-style: italic;
        }

        .signatures-row {
            display: flex;
            justify-content: space-around;
            align-items: flex-end;
            margin-bottom: 12px;
        }

        .signature-block {
            text-align: center;
            width: 38%;
        }

        .signature-line {
            border-top: 1px solid #1e293b;
            padding-top: 4px;
        }

        .signee-name {
            font-size: 11.5px;
            font-weight: 700;
            color: #0f172a;
        }

        .signee-role {
            font-family: Arial, sans-serif;
            font-size: 9px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Barra de Registro e Autenticidade */
        .cert-meta-bar {
            border-top: 1px dashed #cbd5e1;
            padding-top: 4px;
            display: flex;
            justify-content: space-between;
            font-family: Arial, sans-serif;
            font-size: 8px;
            color: #64748b;
        }

        .auth-code {
            font-weight: 700;
            color: #1e3a8a;
            letter-spacing: 1px;
        }

        /* ---------------- VERSO DO CERTIFICADO (HISTÓRICO E MÓDULOS) ---------------- */
        .cert-back {
            border: 2px solid #334155;
            padding: 6mm 8mm;
            font-family: Arial, sans-serif;
            background: #ffffff;
            page-break-before: always;
            break-before: page;
        }

        .back-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 6px;
            margin-bottom: 8px;
        }

        .back-title {
            font-size: 14px;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .back-subtitle {
            font-size: 9px;
            color: #64748b;
            font-weight: 600;
        }

        .student-info-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 6px 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 6px 10px;
            margin-bottom: 8px;
            font-size: 9.5px;
        }

        .info-item {
            line-height: 1.25;
        }

        .info-lbl {
            font-size: 7.5px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            display: block;
        }

        .info-val {
            font-weight: 700;
            color: #0f172a;
        }

        /* Tabela de Módulos / Disciplinas */
        .modules-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
            margin-bottom: 8px;
        }

        .modules-table th {
            background-color: #0f172a;
            color: #ffffff;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 8px;
            letter-spacing: 0.5px;
            padding: 4px 6px;
            text-align: left;
            border: 1px solid #0f172a;
        }

        .modules-table td {
            padding: 3.5px 6px;
            border: 1px solid #cbd5e1;
            color: #1e293b;
        }

        .modules-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }

        .modules-table tr.total-row td {
            background-color: #e2e8f0;
            font-weight: 800;
            color: #0f172a;
            border-top: 1.5px solid #0f172a;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }

        /* Rodapé do Verso */
        .back-footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 12px;
            margin-top: auto;
            border-top: 1px solid #cbd5e1;
            padding-top: 6px;
        }

        .legal-box {
            font-size: 7.5px;
            color: #475569;
            line-height: 1.35;
            background: #fafafa;
            border: 1px solid #e2e8f0;
            border-radius: 3px;
            padding: 5px 8px;
        }

        .legal-box strong {
            color: #0f172a;
        }

        .validation-card {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 4px 8px;
        }

        .validation-qr {
            width: 44px;
            height: 44px;
            flex-shrink: 0;
            display: block;
        }

        .validation-meta {
            font-size: 7.5px;
            line-height: 1.25;
            color: #475569;
        }

        .back-signatures-row {
            display: flex;
            justify-content: space-around;
            margin-top: 10px;
            padding-top: 6px;
        }

        .back-sign-box {
            width: 40%;
            text-align: center;
            border-top: 1px solid #334155;
            padding-top: 2px;
            font-size: 8.5px;
            color: #0f172a;
            font-weight: 700;
        }

        .back-sign-box .role {
            font-size: 7.5px;
            font-weight: normal;
            color: #64748b;
        }

        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
            }

            .no-print {
                display: none !important;
            }

            .cert-container {
                box-shadow: none !important;
                margin: 0 !important;
                width: 100% !important;
                height: 100vh !important;
                page-break-after: always;
                break-after: page;
            }

            .cert-back {
                page-break-before: always !important;
                break-before: page !important;
                page-break-after: avoid !important;
                break-after: avoid !important;
                height: 100vh !important;
            }

            .cert-page-badge {
                display: none !important;
            }
        }
    </style>
</head>
<body>

    @php
        $schoolTrade = $school['trade_name'] ?? $school['legal_name'] ?? 'Aurosoft Cursos Profissionalizantes';
        $schoolCnpj = $school['cnpj'] ?? null;
        $schoolCity = ! empty($school['city'])
            ? $school['city']
            : ($contract->student?->addresses?->first()?->city?->name ?? 'Município');
        $schoolState = ! empty($school['state'])
            ? $school['state']
            : ($contract->student?->addresses?->first()?->city?->state?->acronym ?? 'UF');

        $studentCpf = $contract->student?->cpf
            ?? $contract->student?->documents?->firstWhere('type', 'cpf')?->value;
        $studentRg = $contract->student?->documents?->firstWhere('type', 'rg')?->value ?? '—';

        $diaInicio = $startDate->format('d/m/Y');
        $diaFim = $completionDate->format('d/m/Y');

        $meses = [
            1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril',
            5 => 'maio', 6 => 'junho', 7 => 'julho', 8 => 'agosto',
            9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro'
        ];
        $dia = $completionDate->format('d');
        $mes = $meses[(int)$completionDate->format('m')] ?? $completionDate->format('F');
        $ano = $completionDate->format('Y');

        $bookNumber = $bookNumber ?? '01';
        $sheetNumber = $sheetNumber ?? str_pad((string) (($contract->id % 99) + 1), 3, '0', STR_PAD_LEFT);
        $registrationNumber = $registrationNumber ?? str_pad((string) $contract->id, 4, '0', STR_PAD_LEFT);
        $schoolLogo = !empty($school['logo_url']) ? $school['logo_url'] : (!empty($school['logo_path']) ? \Illuminate\Support\Facades\Storage::disk('public')->url($school['logo_path']) : null);
    @endphp

    @php
        $authUser = auth()->user();
        $isStudentPortal = session('portal_auth_type') === 'student_contract' || session('portal_contract_id') !== null;
        $canStaffEdit = $authUser && ($authUser->isStaff() || $authUser->hasRole('super_admin')) && ! $isStudentPortal;
    @endphp

    <div class="no-print">
        <div>
            <strong>Certificado Oficial:</strong> {{ $contract->student?->name }} | {{ $courseTitle }}
            <span style="color: #64748b; margin-left: 8px;">(Frente: Concessão · Verso: Histórico e Módulos)</span>
        </div>
        <div style="display: flex; gap: 8px;">
            @if($canStaffEdit)
                <button onclick="toggleEditMode()" type="button" class="btn btn-secondary" id="btnEdit">
                    ✏️ Editar Textos no Certificado
                </button>
            @endif
            <button onclick="window.print()" type="button" class="btn btn-primary">
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
                    <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/>
                </svg>
                Imprimir Certificado Completo (Frente e Verso)
            </button>
            <a href="javascript:window.close()" class="btn btn-secondary">Fechar</a>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!--                                FRENTE                                    -->
    <!-- ========================================================================= -->
    <div class="cert-container" id="frente">
        <div class="cert-page-badge">Frente</div>
        <div class="cert-outer-border">
            <div class="cert-inner-border">
                <div class="cert-watermark">{{ $schoolTrade }}</div>

                <!-- CABEÇALHO DA FRENTE -->
                <div class="cert-header">
                    @if ($schoolLogo)
                        <div style="margin-bottom: 8px;">
                            <img src="{{ $schoolLogo }}" alt="Logotipo da Escola" style="max-height: 48px; max-width: 180px; object-fit: contain;">
                        </div>
                    @endif
                    <h1 class="school-name">{{ $schoolTrade }}</h1>
                    <div class="school-details">
                        @if($schoolCnpj) CNPJ: {{ $schoolCnpj }} · @endif
                        {{ $schoolCity }} - {{ $schoolState }} · Registro Institucional de Ensino Profissionalizante
                    </div>

                    <div class="cert-title-container">
                        <div class="cert-title">Certificado de Conclusão</div>
                        <div class="cert-subtitle">Educação Profissional & Desenvolvimento Técnico</div>
                    </div>
                </div>

                <!-- TEXTO PRINCIPAL DE CONCESSÃO -->
                <div class="cert-body">
                    Certificamos para os devidos fins legais e comprobatórios que
                    <br>
                    <span class="student-name">{{ $contract->student?->name ?? 'Aluno(a)' }}</span>
                    <br>
                    @if($studentCpf)
                        portador(a) do CPF sob o nº <span class="data-highlight">{{ $studentCpf }}</span>,
                    @endif
                    concluiu com êxito e aproveitamento satisfatório os estudos relativos à formação no curso de
                    <br>
                    <span class="course-highlight">{{ $courseTitle }}</span>,
                    <br>
                    totalizando a carga horária de <span class="data-highlight">{{ $totalWorkload }} horas</span>
                    @if($averageGrade)
                        com média final de aproveitamento de <span class="data-highlight">{{ number_format($averageGrade, 1, ',', '.') }}%</span>,
                    @else
                        com aproveitamento satisfatório nos exames teóricos e práticos,
                    @endif
                    tendo cumprido integralmente todas as exigências do plano pedagógico e curricular.
                </div>

                <!-- RODAPÉ & ASSINATURAS DA FRENTE -->
                <div class="cert-footer">
                    <div class="cert-date-location">
                        {{ $schoolCity }} - {{ $schoolState }}, {{ $dia }} de {{ $mes }} de {{ $ano }}.
                    </div>

                    <div class="signatures-row">
                        <div class="signature-block">
                            <div class="signature-line">
                                <div class="signee-name">{{ $schoolRepresentative ?: ($schoolTrade) }}</div>
                                <div class="signee-role">Direção Geral / Representante Legal</div>
                            </div>
                        </div>

                        <div class="signature-block">
                            <div class="signature-line">
                                <div class="signee-name">Coordenação Pedagógica</div>
                                <div class="signee-role">Secretaria & Registro Escolar</div>
                            </div>
                        </div>
                    </div>

                    <div class="cert-meta-bar">
                        <div>Contrato: #{{ $contract->contract_number }} · Matrícula Aluno: {{ $contract->student?->ouro_moderno_cod_int ?: $contract->student_id }}</div>
                        <div>Histórico Escolar e Conteúdo Programático detalhado no verso · Validação Digital: <span class="auth-code">{{ $verificationCode }}</span></div>
                        <div>Emitido em: {{ now()->format('d/m/Y H:i') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!--                                VERSO                                     -->
    <!-- ========================================================================= -->
    <div class="cert-container cert-back" id="verso">
        <div class="cert-page-badge">Verso</div>

        <div>
            <!-- CABEÇALHO DO VERSO -->
            <div class="back-header" style="display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    @if ($schoolLogo)
                        <img src="{{ $schoolLogo }}" alt="Logotipo da Escola" style="max-height: 38px; max-width: 120px; object-fit: contain;">
                    @endif
                    <div>
                        <div class="back-title">Histórico Escolar & Conteúdo Programático</div>
                        <div class="back-subtitle">{{ $schoolTrade }} @if($schoolCnpj)· CNPJ {{ $schoolCnpj }}@endif · {{ $schoolCity }} - {{ $schoolState }}</div>
                    </div>
                </div>
                <div style="text-align: right; font-size: 8px; color: #475569;">
                    <div>Livro de Registro: <strong>{{ $bookNumber }}</strong></div>
                    <div>Folha nº: <strong>{{ $sheetNumber }}</strong> · Reg. nº: <strong>{{ $registrationNumber }}</strong></div>
                </div>
            </div>

            <!-- DADOS DO ALUNO E PERÍODO -->
            <div class="student-info-grid">
                <div class="info-item">
                    <span class="info-lbl">Aluno(a)</span>
                    <span class="info-val">{{ $contract->student?->name ?? '—' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-lbl">CPF</span>
                    <span class="info-val">{{ $studentCpf ?: 'Não informado' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-lbl">RG</span>
                    <span class="info-val">{{ $studentRg }}</span>
                </div>
                <div class="info-item">
                    <span class="info-lbl">Cód. Matrícula</span>
                    <span class="info-val">{{ $contract->student?->ouro_moderno_cod_int ?: $contract->student_id }}</span>
                </div>

                <div class="info-item" style="grid-column: span 2;">
                    <span class="info-lbl">Curso / Formação</span>
                    <span class="info-val">{{ $courseTitle }}</span>
                </div>
                <div class="info-item">
                    <span class="info-lbl">Data de Início</span>
                    <span class="info-val">{{ $diaInicio }}</span>
                </div>
                <div class="info-item">
                    <span class="info-lbl">Data de Término</span>
                    <span class="info-val">{{ $diaFim }}</span>
                </div>
            </div>

            <!-- TABELA DE MÓDULOS / DISCIPLINAS -->
            <table class="modules-table">
                <thead>
                    <tr>
                        <th style="width: 32px;" class="text-center">#</th>
                        <th>Módulo / Disciplina Cursada</th>
                        <th style="width: 70px;" class="text-center">Carga Horária</th>
                        <th style="width: 70px;" class="text-center">Aproveitamento</th>
                        <th style="width: 140px;" class="text-center">Período de Realização</th>
                        <th style="width: 80px;" class="text-center">Situação</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $itemIndex = 1;
                    @endphp
                    @forelse($courses as $cc)
                        @php
                            $courseName = $cc->course?->name ?? 'Módulo Profissionalizante';
                            $workload = (int) ($cc->course?->workload ?? 0);
                            $grade = is_numeric($cc->grade) ? (float) $cc->grade : null;
                            $mStart = $cc->created_at ? $cc->created_at->format('d/m/Y') : $diaInicio;
                            $mEnd = $cc->completed_at ? $cc->completed_at->format('d/m/Y') : $diaFim;
                            $statusLabel = match($cc->status) {
                                'completed' => 'Concluído',
                                'in_progress' => 'Em andamento',
                                default => 'Aprovado',
                            };
                        @endphp
                        <tr>
                            <td class="text-center" style="font-weight: 700;">{{ str_pad((string) $itemIndex++, 2, '0', STR_PAD_LEFT) }}</td>
                            <td><strong>{{ $courseName }}</strong></td>
                            <td class="text-center">{{ $workload }} horas</td>
                            <td class="text-center">
                                @if($grade !== null)
                                    <strong>{{ number_format($grade, 1, ',', '.') }}%</strong>
                                @else
                                    Satisfatório
                                @endif
                            </td>
                            <td class="text-center">{{ $mStart }} a {{ $mEnd }}</td>
                            <td class="text-center" style="color: #166534; font-weight: 700;">{{ $statusLabel }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="text-center">01</td>
                            <td><strong>{{ $courseTitle }}</strong></td>
                            <td class="text-center">{{ $totalWorkload }} horas</td>
                            <td class="text-center">{{ $averageGrade ? number_format($averageGrade, 1, ',', '.').'%' : 'Satisfatório' }}</td>
                            <td class="text-center">{{ $diaInicio }} a {{ $diaFim }}</td>
                            <td class="text-center" style="color: #166534; font-weight: 700;">Concluído</td>
                        </tr>
                    @endforelse

                    <tr class="total-row">
                        <td colspan="2" style="text-align: right; text-transform: uppercase;">Carga Horária Total & Média Geral:</td>
                        <td class="text-center">{{ $totalWorkload }} horas</td>
                        <td class="text-center">{{ $averageGrade ? number_format($averageGrade, 1, ',', '.').'%' : 'Aprovado' }}</td>
                        <td colspan="2" class="text-center" style="font-size: 8px;">Frequência Global: Atingida (&ge; 75%)</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div>
            <!-- BASE LEGAL E VALIDAÇÃO DIGITAL -->
            <div class="back-footer-grid">
                <div class="legal-box">
                    <strong>Amparo Legal e Dispositivos Normativos:</strong>
                    {{ $customLegalBase ?: 'Certificado de Educação Profissional emitido em conformidade com o Artigo 39 a 42 da Lei Federal nº 9.394/1996 (Diretrizes e Bases da Educação Nacional - LDB), regulamentado pelo Decreto Federal nº 5.154/2004 e deliberações pertinentes aos cursos livres de formação e qualificação profissional.' }}
                </div>

                <div class="validation-card">
                    <img src="{{ $verificationQr }}" alt="QR Code de Autenticidade" class="validation-qr" />
                    <div class="validation-meta">
                        <div><strong>Código de Autenticação:</strong></div>
                        <div style="font-family: monospace; font-size: 9px; font-weight: 800; color: #1e3a8a;">{{ $verificationCode }}</div>
                        <div style="color: #64748b; font-size: 7px; margin-top: 2px;">Aponte a câmera ou consulte em:</div>
                        <div style="color: #1e3a8a; font-size: 7.5px; font-weight: 700; word-break: break-all;">{{ url('/validar-certificado') }}</div>
                    </div>
                </div>
            </div>

            <!-- ASSINATURAS DO VERSO -->
            <div class="back-signatures-row">
                <div class="back-sign-box">
                    <div>Secretaria Escolar / Registro de Diplomas</div>
                    <div class="role">Responsável pelo Registro de Livro e Folha</div>
                </div>
                <div class="back-sign-box">
                    <div>{{ $schoolRepresentative ?: ($schoolTrade) }}</div>
                    <div class="role">Direção da Instituição de Ensino</div>
                </div>
            </div>
        </div>
    </div>

    @if($canStaffEdit)
    <script>
        function toggleEditMode() {
            const frente = document.getElementById('frente');
            const verso = document.getElementById('verso');
            const isEditable = frente.isContentEditable;
            const newEditable = !isEditable;

            frente.contentEditable = newEditable ? 'true' : 'false';
            verso.contentEditable = newEditable ? 'true' : 'false';

            const btn = document.getElementById('btnEdit');
            if (newEditable) {
                btn.textContent = '💾 Concluir Edição';
                btn.style.backgroundColor = '#dcfce7';
                btn.style.color = '#166534';
                btn.style.borderColor = '#86efac';
            } else {
                btn.textContent = '✏️ Editar Textos no Certificado';
                btn.style.backgroundColor = '#ffffff';
                btn.style.color = '#334155';
                btn.style.borderColor = '#cbd5e1';
            }
        }
    </script>
    @endif
</body>
</html>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Declaração de Matrícula - {{ $contract->student?->name }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 15mm 20mm;
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body {
            font-family: "Times New Roman", Times, Georgia, serif;
            color: #111827;
            background-color: #f3f4f6;
            margin: 0;
            padding: 20px;
            font-size: 13pt;
            line-height: 1.8;
        }

        .no-print {
            max-width: 800px;
            margin: 0 auto 20px auto;
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

        .doc-page {
            max-width: 800px;
            min-height: 297mm;
            margin: 0 auto;
            background: #ffffff;
            padding: 25mm 25mm;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .school-header {
            text-align: center;
            border-bottom: 2px solid #1e3a8a;
            padding-bottom: 12px;
            margin-bottom: 30px;
        }

        .school-name {
            font-size: 18pt;
            font-weight: bold;
            color: #1e3a8a;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }

        .school-info {
            font-family: Arial, sans-serif;
            font-size: 9pt;
            color: #475569;
            line-height: 1.4;
        }

        .doc-title-box {
            text-align: center;
            margin: 25px 0 35px 0;
        }

        .doc-title {
            font-size: 16pt;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            text-decoration: underline;
            text-underline-offset: 4px;
        }

        .doc-body {
            text-align: justify;
            text-indent: 40px;
            margin-bottom: 30px;
        }

        .doc-body p {
            margin-bottom: 18px;
        }

        .highlight {
            font-weight: bold;
            color: #0f172a;
        }

        .date-location {
            text-align: right;
            margin: 40px 0 60px 0;
            font-style: italic;
        }

        .signatures-area {
            display: flex;
            justify-content: space-around;
            text-align: center;
            margin-top: auto;
        }

        .sign-box {
            width: 42%;
            border-top: 1px solid #1f2937;
            padding-top: 6px;
            font-size: 10pt;
        }

        .sign-box .role {
            font-family: Arial, sans-serif;
            font-size: 8.5pt;
            color: #64748b;
            text-transform: uppercase;
        }

        .doc-footer {
            border-top: 1px dashed #cbd5e1;
            padding-top: 8px;
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
            font-family: Arial, sans-serif;
            font-size: 8pt;
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

            .doc-page {
                box-shadow: none !important;
                padding: 10mm 15mm !important;
                margin: 0 !important;
                min-height: auto !important;
            }
        }
    </style>
</head>
<body>

    @php
        $schoolTrade = $school['trade_name'] ?? $school['legal_name'] ?? 'Aurosoft Cursos Profissionalizantes';
        $schoolLogo = !empty($school['logo_url']) ? $school['logo_url'] : (!empty($school['logo_path']) ? \Illuminate\Support\Facades\Storage::disk('public')->url($school['logo_path']) : null);
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
            trim(($school['street'] ?? '').' '.($school['number'] ?? '')),
            $school['complement'] ?? null,
            $school['neighborhood'] ?? null,
            trim(($school['city'] ?? '').(!empty($school['state']) ? ' - '.$school['state'] : '')),
        ])->filter()->join(', ');

        $studentCpf = $contract->student?->cpf
            ?? $contract->student?->documents?->firstWhere('type', 'cpf')?->value;
        $studentRg = $contract->student?->documents?->firstWhere('type', 'rg')?->value;
        $studentBirth = $contract->student?->birth_date ? \Carbon\Carbon::parse($contract->student->birth_date)->format('d/m/Y') : null;

        $startDateStr = $contract->start_date ? $contract->start_date->format('d/m/Y') : now()->format('d/m/Y');
        $endDateStr = $contract->end_date ? $contract->end_date->format('d/m/Y') : ($contract->finished_at ? $contract->finished_at->format('d/m/Y') : 'Em andamento');

        $meses = [
            1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril',
            5 => 'maio', 6 => 'junho', 7 => 'julho', 8 => 'agosto',
            9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro'
        ];
        $hoje = now();
        $dataExtenso = $schoolCity . ' - ' . $schoolState . ', ' . $hoje->format('d') . ' de ' . ($meses[(int)$hoje->format('m')] ?? $hoje->format('F')) . ' de ' . $hoje->format('Y');
    @endphp

    <div class="no-print">
        <div>
            <strong>Documento Oficial:</strong> Declaração de Matrícula e Frequência · {{ $contract->student?->name }}
        </div>
        <div style="display: flex; gap: 8px;">
            <button onclick="window.print()" class="btn btn-primary">
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
                    <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/>
                </svg>
                Imprimir Declaração
            </button>
            <a href="javascript:window.close()" class="btn btn-secondary">Fechar</a>
        </div>
    </div>

    <div class="doc-page">
        <div>
            <!-- CABEÇALHO -->
            <div class="school-header">
                @if ($schoolLogo)
                    <div style="margin-bottom: 6px;">
                        <img src="{{ $schoolLogo }}" alt="Logotipo da Escola" style="max-height: 50px; max-width: 180px; object-fit: contain;">
                    </div>
                @endif
                <div class="school-name">{{ $schoolTrade }}</div>
                <div class="school-info">
                    @if($schoolCnpj) CNPJ: {{ $schoolCnpj }} · @endif
                    @if($schoolAddress) {{ $schoolAddress }} · @endif
                    @if($schoolPhone) Tel: {{ $schoolPhone }} @endif
                    @if($schoolEmail) · E-mail: {{ $schoolEmail }} @endif
                </div>
            </div>

            <!-- TÍTULO -->
            <div class="doc-title-box">
                <div class="doc-title">Declaração de Matrícula e Frequência</div>
            </div>

            <!-- CORPO DO TEXTO -->
            <div class="doc-body">
                <p>
                    Declaramos, para os devidos fins de direito e a quem possa interessar, que o(a) aluno(a)
                    <span class="highlight">{{ $contract->student?->name ?? '—' }}</span>,
                    @if($studentCpf)
                        inscrito(a) no CPF sob o nº <span class="highlight">{{ $studentCpf }}</span>,
                    @endif
                    @if($studentRg)
                        portador(a) do RG nº <span class="highlight">{{ $studentRg }}</span>,
                    @endif
                    @if($studentBirth)
                        nascido(a) em <span class="highlight">{{ $studentBirth }}</span>,
                    @endif
                    encontra-se <span class="highlight">regularmente matriculado(a) e com frequência ativa</span> nesta instituição de ensino, sob o Contrato nº <span class="highlight">#{{ $contract->contract_number }}</span>, cursando a formação profissionalizante em <span class="highlight">{{ $courseTitle }}</span>, com carga horária total estimada de <span class="highlight">{{ $totalWorkload }} horas</span>.
                </p>

                <p>
                    O referido curso teve início em <span class="highlight">{{ $startDateStr }}</span>, com previsão de término em <span class="highlight">{{ $endDateStr }}</span>.
                    @if($scheduleSummary)
                        As aulas são ministradas regularmente nos seguintes dias e horários: <span class="highlight">{{ $scheduleSummary }}</span>.
                    @endif
                </p>

                <p>
                    Atestamos que o(a) estudante vem cumprindo satisfatoriamente o plano de estudos pedagógico e as normas regimentais da instituição, mantendo assiduidade e pontualidade compatíveis com as exigências curriculares.
                </p>

                <p>
                    Por ser a expressão da verdade, firmamos e expedimos a presente declaração para que surta seus legais e jurídicos efeitos.
                </p>
            </div>

            <!-- LOCAL E DATA -->
            <div class="date-location">
                {{ $dataExtenso }}
            </div>
        </div>

        <!-- ASSINATURAS E RODAPÉ -->
        <div>
            <div class="signatures-area">
                <div class="sign-box">
                    <div style="font-weight: bold; color: #0f172a;">{{ $schoolRepresentative ?: ($schoolTrade) }}</div>
                    <div class="role">Direção da Instituição</div>
                </div>
                <div class="sign-box">
                    <div style="font-weight: bold; color: #0f172a;">Secretaria Escolar</div>
                    <div class="role">Coordenação de Registros</div>
                </div>
            </div>

            <div class="doc-footer">
                <div>Contrato: #{{ $contract->contract_number }} · Matrícula Aluno: {{ $contract->student?->ouro_moderno_cod_int ?: $contract->student_id }}</div>
                <div>Declaração emitida via Aurosoft Gestão Escolar</div>
                <div>Emissão: {{ now()->format('d/m/Y H:i') }}</div>
            </div>
        </div>
    </div>

</body>
</html>


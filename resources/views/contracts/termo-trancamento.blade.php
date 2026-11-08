<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Termo de Trancamento de Matrícula - {{ $contract->student?->name }}</title>
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
            font-size: 12pt;
            line-height: 1.7;
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
            background-color: #d97706;
            color: #ffffff;
        }

        .btn-primary:hover {
            background-color: #b45309;
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
            padding: 22mm 25mm;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .school-header {
            text-align: center;
            border-bottom: 2px solid #d97706;
            padding-bottom: 12px;
            margin-bottom: 25px;
        }

        .school-logo {
            max-height: 65px;
            max-width: 250px;
            object-fit: contain;
            margin-bottom: 6px;
        }

        .school-name {
            font-size: 17pt;
            font-weight: bold;
            color: #d97706;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 3px;
        }

        .school-info {
            font-family: Arial, sans-serif;
            font-size: 8.5pt;
            color: #475569;
            line-height: 1.3;
        }

        .doc-title-box {
            text-align: center;
            margin: 20px 0 30px 0;
        }

        .doc-title {
            font-size: 15pt;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            text-decoration: underline;
            text-underline-offset: 5px;
        }

        .doc-clause-title {
            font-weight: bold;
            color: #0f172a;
            margin-top: 14px;
            margin-bottom: 4px;
        }

        .doc-body {
            text-align: justify;
        }

        .doc-body p {
            margin-bottom: 12px;
            text-indent: 30px;
        }

        .highlight {
            font-weight: bold;
            color: #0f172a;
        }

        .date-location {
            text-align: right;
            margin: 30px 0 45px 0;
            font-style: italic;
        }

        .signatures-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 15px;
        }

        .signature-box {
            text-align: center;
        }

        .signature-line {
            border-top: 1px solid #1e293b;
            margin-bottom: 6px;
        }

        .signature-name {
            font-weight: bold;
            font-size: 11pt;
            color: #0f172a;
        }

        .signature-role {
            font-family: Arial, sans-serif;
            font-size: 9pt;
            color: #64748b;
        }

        .doc-footer {
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
            font-family: Arial, sans-serif;
            font-size: 8pt;
            color: #94a3b8;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
        }

        @media print {
            body {
                background: none;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
            .doc-page {
                box-shadow: none;
                margin: 0;
                padding: 10mm 15mm;
                min-height: auto;
            }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <div>
            <strong>Documento Oficial:</strong> Termo de Trancamento Provisório de Matrícula
        </div>
        <div style="display: flex; gap: 8px;">
            <button onclick="window.print()" class="btn btn-primary">
                🖨️ Imprimir Termo de Trancamento
            </button>
            <button onclick="window.close()" class="btn btn-secondary">
                Fechar
            </button>
        </div>
    </div>

    @php
        $student = $contract->student;
        $studentName = $student?->name ?? 'Não informado';
        $studentCpf = $student?->documents?->firstWhere('type', 'cpf')?->document_number
            ?: ($student?->document_number ?? 'Não informado');

        $resp = $contract->responsible?->person;
        $respName = $resp?->name ?: $studentName;
        $respCpf = $resp?->documents?->firstWhere('type', 'cpf')?->document_number
            ?: ($resp?->document_number ?: $studentCpf);

        $schoolName = $school['trade_name'] ?? ($school['name'] ?? config('app.name', 'Aurosoft LMS'));
        $schoolLegal = $school['legal_name'] ?? $schoolName;
        $schoolCnpj = $school['cnpj'] ?? null;
        $schoolAddress = $school['address'] ?? null;
        $schoolPhone = $school['phone'] ?? null;
        $schoolLogo = $school['logo_url'] ?? null;

        $city = $school['city'] ?? 'Fortaleza';
        $state = $school['state'] ?? 'CE';
        $fullDateExtenso = \Carbon\Carbon::parse($startDate)->translatedFormat('d \d\e F \d\e Y');
    @endphp

    <div class="doc-page">
        <div>
            {{-- Cabeçalho da Escola --}}
            <div class="school-header">
                @if($schoolLogo)
                    <img src="{{ $schoolLogo }}" alt="{{ $schoolName }}" class="school-logo">
                @endif
                <div class="school-name">{{ $schoolName }}</div>
                <div class="school-info">
                    {{ $schoolLegal }} @if($schoolCnpj) | CNPJ: {{ $schoolCnpj }} @endif<br>
                    @if($schoolAddress) {{ $schoolAddress }} @endif
                    @if($schoolPhone) | Tel: {{ $schoolPhone }} @endif
                </div>
            </div>

            {{-- Título --}}
            <div class="doc-title-box">
                <div class="doc-title">Termo de Trancamento Provisório de Matrícula</div>
            </div>

            {{-- Conteúdo do Documento --}}
            <div class="doc-body">
                @if(filled($customContent ?? null))
                    {!! $customContent !!}
                @else
                <p>
                    Pelo presente instrumento, a instituição de ensino <span class="highlight">{{ $schoolLegal }}</span> (CONTRATADA) e o(a) aluno(a) / contratante <span class="highlight">{{ $respName }}</span>, inscrito(a) no CPF nº <span class="highlight">{{ $respCpf }}</span>, referente à Matrícula / Contrato nº <span class="highlight">#{{ $contract->contract_number }}</span> do(a) educando(a) <span class="highlight">{{ $studentName }}</span> no curso de <span class="highlight">{{ $courseTitle }}</span>, formalizam o trancamento temporário de estudos mediante as cláusulas a seguir:
                </p>

                <div class="doc-clause-title">CLÁUSULA PRIMEIRA – DO PERÍODO DE PAUSA:</div>
                <p>
                    Fica deferido o trancamento provisório das atividades pedagógicas pelo período de <span class="highlight">{{ $days }} dias</span>, com início em <span class="highlight">{{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }}</span> e data limite de retorno fixada para <span class="highlight">{{ \Carbon\Carbon::parse($returnDate)->format('d/m/Y') }}</span>.
                </p>

                <div class="doc-clause-title">CLÁUSULA SEGUNDA – DA MOTIVAÇÃO:</div>
                <p>
                    A solicitação fundamenta-se na seguinte justificativa apresentada pelo(a) requerente: <span class="highlight">"{{ $reason }}"</span>.
                </p>

                <div class="doc-clause-title">CLÁUSULA TERCEIRA – DO RETORNO E REMANEJAMENTO:</div>
                <p>
                    O(a) aluno(a) compromete-se a comparecer à secretaria acadêmica até a data limite estipulada para reativação das aulas. O retorno dar-se-á em turma e horário compatíveis com a disponibilidade de vagas e cronograma pedagógico da escola, mantendo-se o aproveitamento de todos os módulos já concluídos.
                </p>

                <div class="doc-clause-title">CLÁUSULA QUARTA – DO NÃO RETORNO (ABANDONO):</div>
                <p>
                    Caso o(a) aluno(a) não compareça para reativar seus estudos no prazo de até 15 (quinze) dias corridos após a data de retorno prevista, a matrícula será considerada cancelada por abandono, aplicando-se as regras contratuais pertinentes.
                </p>
                @endif
            </div>

            {{-- Data e Local --}}
            <div class="date-location">
                {{ $city }} - {{ $state }}, {{ $fullDateExtenso }}.
            </div>

            {{-- Assinaturas --}}
            <div class="signatures-grid">
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-name">{{ $schoolRepresentative ?: $schoolName }}</div>
                    <div class="signature-role">Secretaria & Coordenação de Ensino</div>
                </div>

                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-name">{{ $respName }}</div>
                    <div class="signature-role">Assinatura do Aluno(a) ou Responsável Legal</div>
                </div>
            </div>
        </div>

        {{-- Rodapé / Autenticação --}}
        <div class="doc-footer">
            <span>Aurosoft LMS &bullet; Trancamento #{{ $contract->contract_number }}</span>
            <span>Registrado em {{ now()->format('d/m/Y \à\s H:i:s') }} &bullet; Código: {{ strtoupper(substr(md5($contract->id . $startDate), 0, 12)) }}</span>
        </div>
    </div>

</body>
</html>


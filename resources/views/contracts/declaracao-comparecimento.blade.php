<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Declaração de Comparecimento - {{ $contract->student?->name }}</title>
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

        .btn-secondary:hover {
            background-color: #f8fafc;
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

        .school-logo {
            max-height: 70px;
            max-width: 260px;
            object-fit: contain;
            margin-bottom: 8px;
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
            margin: 30px 0 40px 0;
        }

        .doc-title {
            font-size: 17pt;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            text-decoration: underline;
            text-underline-offset: 6px;
        }

        .doc-body {
            text-align: justify;
            text-indent: 40px;
            margin-bottom: 30px;
        }

        .doc-body p {
            margin-bottom: 22px;
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

        .signature-box {
            text-align: center;
            width: 380px;
            margin: 0 auto 30px auto;
        }

        .signature-line {
            border-top: 1px solid #1e293b;
            margin-bottom: 8px;
        }

        .signature-name {
            font-weight: bold;
            font-size: 12pt;
            color: #0f172a;
        }

        .signature-role {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            color: #64748b;
        }

        .doc-footer {
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
            font-family: Arial, sans-serif;
            font-size: 8pt;
            color: #94a3b8;
            display: flex;
            justify-content: space-between;
            align-items: center;
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
            <strong>Documento Oficial:</strong> Declaração de Comparecimento / Atestado
        </div>
        <div style="display: flex; gap: 8px;">
            <button onclick="window.print()" class="btn btn-primary">
                🖨️ Imprimir Declaração
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
        $studentRg = $student?->documents?->firstWhere('type', 'rg')?->document_number ?: 'Não informado';

        $schoolName = $school['trade_name'] ?? ($school['name'] ?? config('app.name', 'Aurosoft LMS'));
        $schoolLegal = $school['legal_name'] ?? $schoolName;
        $schoolCnpj = $school['cnpj'] ?? null;
        $schoolAddress = $school['address'] ?? null;
        $schoolPhone = $school['phone'] ?? null;
        $schoolEmail = $school['email'] ?? null;
        $schoolLogo = $school['logo_url'] ?? null;

        $city = $school['city'] ?? 'Fortaleza';
        $state = $school['state'] ?? 'CE';
        $fullDateExtenso = \Carbon\Carbon::parse($date)->translatedFormat('d \d\e F \d\e Y');
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
                    @if($schoolEmail) | E-mail: {{ $schoolEmail }} @endif
                </div>
            </div>

            {{-- Título --}}
            <div class="doc-title-box">
                <div class="doc-title">Declaração de Comparecimento</div>
            </div>

            {{-- Conteúdo do Documento --}}
            <div class="doc-body">
                @if(filled($customContent ?? null))
                    {!! $customContent !!}
                @else
                <p>
                    Declaramos para os devidos fins de comprovação e abono de faltas junto a quem de direito, inclusive para fins trabalhistas ou de estágio, que o(a) aluno(a) <span class="highlight">{{ $studentName }}</span>, 
                    portador(a) do RG nº <span class="highlight">{{ $studentRg }}</span> e inscrito(a) no CPF sob o nº <span class="highlight">{{ $studentCpf }}</span>, 
                    regularmente matriculado(a) nesta instituição de ensino sob a Matrícula / Contrato nº <span class="highlight">#{{ $contract->contract_number }}</span> no curso de <span class="highlight">{{ $courseTitle }}</span>, 
                    compareceu presencialmente a esta unidade escolar no dia <span class="highlight">{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</span>, no período das <span class="highlight">{{ $startTime }}</span> às <span class="highlight">{{ $endTime }}</span> horas, participando ativamente de <span class="highlight">{{ $purpose }}</span>.
                </p>

                <p>
                    Por ser a expressão fiel da verdade e a pedido do(a) interessado(a), expedimos a presente declaração para que produza todos os efeitos legais, escolares e trabalhistas cabíveis.
                </p>
                @endif
            </div>

            {{-- Data e Local --}}
            <div class="date-location">
                {{ $city }} - {{ $state }}, {{ $fullDateExtenso }}.
            </div>

            {{-- Assinatura --}}
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-name">{{ $schoolRepresentative ?: $schoolName }}</div>
                <div class="signature-role">Secretaria Acadêmica & Coordenação Pedagógica</div>
            </div>
        </div>

        {{-- Rodapé / Autenticação --}}
        <div class="doc-footer">
            <span>Aurosoft LMS &bullet; Contrato #{{ $contract->contract_number }}</span>
            <span>Emitido em {{ now()->format('d/m/Y \à\s H:i:s') }} &bullet; Validação Interna: {{ strtoupper(substr(md5($contract->id . $date . $startTime), 0, 12)) }}</span>
        </div>
    </div>

</body>
</html>


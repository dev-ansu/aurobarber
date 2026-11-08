<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Autorização de Uso de Imagem e Voz - {{ $contract->student?->name }}</title>
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
            font-size: 11.5pt;
            line-height: 1.6;
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
            padding: 22mm 25mm;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .school-header {
            text-align: center;
            border-bottom: 2px solid #1e3a8a;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .school-logo {
            max-height: 60px;
            max-width: 240px;
            object-fit: contain;
            margin-bottom: 6px;
        }

        .school-name {
            font-size: 16pt;
            font-weight: bold;
            color: #1e3a8a;
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
            margin: 15px 0 25px 0;
        }

        .doc-title {
            font-size: 14pt;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            text-decoration: underline;
            text-underline-offset: 4px;
        }

        .doc-body {
            text-align: justify;
        }

        .doc-body p {
            margin-bottom: 12px;
            text-indent: 25px;
        }

        .highlight {
            font-weight: bold;
            color: #0f172a;
        }

        .date-location {
            text-align: right;
            margin: 25px 0 40px 0;
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
            font-size: 10.5pt;
            color: #0f172a;
        }

        .signature-role {
            font-family: Arial, sans-serif;
            font-size: 8.5pt;
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
            <strong>Documento Institucional:</strong> Termo de Autorização de Uso de Imagem e Voz (LGPD)
        </div>
        <div style="display: flex; gap: 8px;">
            <button onclick="window.print()" class="btn btn-primary">
                🖨️ Imprimir Autorização
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
        $fullDateExtenso = now()->translatedFormat('d \d\e F \d\e Y');
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
                <div class="doc-title">Autorização de Uso de Imagem e Voz</div>
            </div>

            {{-- Conteúdo do Documento --}}
            <div class="doc-body">
                @if(filled($customContent ?? null))
                    {!! $customContent !!}
                @else
                <p>
                    Eu, <span class="highlight">{{ $respName }}</span>, inscrito(a) no CPF sob o nº <span class="highlight">{{ $respCpf }}</span>@if($respName !== $studentName), na qualidade de responsável legal pelo(a) educando(a) <span class="highlight">{{ $studentName }}</span>, CPF nº <span class="highlight">{{ $studentCpf }}</span>@endif, vinculado(a) à instituição de ensino <span class="highlight">{{ $schoolLegal }}</span> através do Contrato nº <span class="highlight">#{{ $contract->contract_number }}</span> no curso de <span class="highlight">{{ $courseTitle }}</span>:
                </p>

                <p>
                    <strong>AUTORIZO</strong>, a título gratuito e por prazo indeterminado, nos termos do art. 5º, inciso X da Constituição Federal, da Lei de Direitos Autorais (Lei nº 9.610/98) e da Lei Geral de Proteção de Dados (LGPD - Lei nº 13.709/18), a captação e veiculação da minha imagem e voz (ou do educando sob minha tutela legal), em fotografias, gravações de vídeo e depoimentos obtidos em sala de aula, laboratórios, cerimônias de formatura e eventos pedagógicos da instituição.
                </p>

                <p>
                    A presente autorização destina-se exclusivamente à divulgação institucional e pedagógica das atividades da escola, podendo ser utilizada em:
                </p>

                <ul style="margin-left: 30px; margin-bottom: 14px;">
                    <li>Redes sociais institucionais (Instagram, Facebook, TikTok, YouTube, LinkedIn);</li>
                    <li>Site institucional e portal do aluno na internet;</li>
                    <li>Materiais impressos de circulação local (folders, banners em murais da escola, faixas de formatura);</li>
                    <li>Vídeos institucionais e registros comemorativos internos.</li>
                </ul>

                <p>
                    Fica expressamente vedada a utilização do material em contextos que atentem contra a honra, a boa-fama ou a integridade moral do educando, ou para finalidades comerciais que divirjam das atividades educacionais da escola.
                </p>

                <p>
                    Declaro que a presente autorização é firmada de livre e espontânea vontade, sem que seja devida qualquer contraprestação financeira a qualquer tempo.
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
                    <div class="signature-name">{{ $respName }}</div>
                    <div class="signature-role">Assinatura do Aluno(a) ou Responsável Legal</div>
                </div>

                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-name">{{ $schoolRepresentative ?: $schoolName }}</div>
                    <div class="signature-role">Pela Instituição de Ensino</div>
                </div>
            </div>
        </div>

        {{-- Rodapé / Autenticação --}}
        <div class="doc-footer">
            <span>Aurosoft LMS &bullet; Autorização LGPD #{{ $contract->contract_number }}</span>
            <span>Emitido em {{ now()->format('d/m/Y \à\s H:i:s') }}</span>
        </div>
    </div>

</body>
</html>


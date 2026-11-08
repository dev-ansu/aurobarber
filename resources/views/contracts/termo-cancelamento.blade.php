<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Termo de Rescisão Contratual - {{ $contract->student?->name }}</title>
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
            background-color: #991b1b;
            color: #ffffff;
        }

        .btn-primary:hover {
            background-color: #7f1d1d;
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
            padding: 20mm 25mm;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .school-header {
            text-align: center;
            border-bottom: 2px solid #991b1b;
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
            color: #991b1b;
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
            font-size: 14.5pt;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            text-decoration: underline;
            text-underline-offset: 4px;
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
            margin-bottom: 10px;
            text-indent: 25px;
        }

        .highlight {
            font-weight: bold;
            color: #0f172a;
        }

        .date-location {
            text-align: right;
            margin: 25px 0 35px 0;
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
            <strong>Documento Jurídico:</strong> Termo de Rescisão e Distrato Contratual
        </div>
        <div style="display: flex; gap: 8px;">
            <button onclick="window.print()" class="btn btn-primary">
                🖨️ Imprimir Termo de Rescisão
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
        $schoolEmail = $school['email'] ?? null;
        $schoolLogo = $school['logo_url'] ?? null;

        $city = $school['city'] ?? 'Fortaleza';
        $state = $school['state'] ?? 'CE';
        $fullDateExtenso = \Carbon\Carbon::parse($cancellationDate)->translatedFormat('d \d\e F \d\e Y');
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
                <div class="doc-title">Termo de Rescisão e Distrato Contratual</div>
            </div>

            {{-- Conteúdo do Documento --}}
            <div class="doc-body">
                @if(filled($customContent ?? null))
                    {!! $customContent !!}
                @else
                <p>
                    Pelo presente instrumento particular de distrato e rescisão, de um lado denominada <strong>CONTRATADA</strong>, <span class="highlight">{{ $schoolLegal }}</span>, inscrita no CNPJ sob o nº <span class="highlight">{{ $schoolCnpj }}</span>, e de outro lado como <strong>CONTRATANTE / ALUNO(A)</strong>, <span class="highlight">{{ $respName }}</span>, inscrito(a) no CPF nº <span class="highlight">{{ $respCpf }}</span> (em favor do(a) aluno(a) <span class="highlight">{{ $studentName }}</span>, CPF: <span class="highlight">{{ $studentCpf }}</span>), têm entre si justo e acordado o encerramento do <strong>Contrato de Prestação de Serviços Educacionais nº #{{ $contract->contract_number }}</strong>, referente ao curso de <span class="highlight">{{ $courseTitle }}</span>, mediante as seguintes cláusulas:
                </p>

                <div class="doc-clause-title">CLÁUSULA PRIMEIRA – DA RESCISÃO:</div>
                <p>
                    Fica expressamente rescindido, a contar desta data, o vínculo contratual e pedagógico estabelecido pelo Contrato nº #{{ $contract->contract_number }}, cessando a partir deste momento a obrigação de disponibilização de aulas presenciais, assentos em laboratórios e suporte de instrutores.
                </p>

                <div class="doc-clause-title">CLÁUSULA SEGUNDA – DA MOTIVAÇÃO:</div>
                <p>
                    A rescisão ocorre a pedido da parte CONTRATANTE, sob a seguinte justificativa registrada perante a secretaria: <span class="highlight">"{{ $cancellationReason }}"</span>.
                </p>

                <div class="doc-clause-title">CLÁUSULA TERCEIRA – DO ACERTO FINANCEIRO E MULTA:</div>
                <p>
                    @if($feeAmount > 0)
                        Em cumprimento às cláusulas rescisórias contratadas, fica fixada a taxa/multa rescisória no valor líquido de <span class="highlight">R$ {{ number_format($feeAmount, 2, ',', '.') }}</span>, com vencimento em até 5 (cinco) dias úteis. As parcelas vincendas futuras não vencidas do curso foram canceladas e baixadas no sistema financeiro da CONTRATADA.
                    @else
                        A CONTRATADA, por mera liberalidade e em comum acordo, isenta a cobrança de multa rescisória, procedendo ao cancelamento imediato de todas as parcelas de mensalidades futuras vincendas no sistema.
                    @endif
                </p>

                <div class="doc-clause-title">CLÁUSULA QUARTA – DOS ACESSOS E PLATAFORMAS:</div>
                <p>
                    Com a homologação deste termo, os acessos do(a) aluno(a) aos sistemas e à plataforma de ensino e laboratório interativo são suspensos, assegurando-se o histórico e registros das aulas e notas até então cursadas.
                </p>

                <div class="doc-clause-title">CLÁUSULA QUINTA – DA PLENA E GERAL QUITAÇÃO:</div>
                <p>
                    Com o adimplemento da taxa estipulada (caso aplicável) e a formalização deste termo, as partes dão-se mútua, plena, rasa e irrevogável quitação quanto a quaisquer obrigações pedagógicas e contratuais futuras, nada mais tendo a reclamar em juízo ou fora dele.
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
                    <div class="signature-role">Pela CONTRATADA (Instituição de Ensino)</div>
                </div>

                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-name">{{ $respName }}</div>
                    <div class="signature-role">Pelo(a) CONTRATANTE / RESPONSÁVEL</div>
                </div>
            </div>
        </div>

        {{-- Rodapé / Autenticação --}}
        <div class="doc-footer">
            <span>Aurosoft LMS &bullet; Distrato Contratual #{{ $contract->contract_number }}</span>
            <span>Registrado em {{ now()->format('d/m/Y \à\s H:i:s') }} &bullet; Protocolo: {{ strtoupper(substr(md5($contract->id . $cancellationDate), 0, 12)) }}</span>
        </div>
    </div>

</body>
</html>


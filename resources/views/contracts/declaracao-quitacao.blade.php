<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Declaração de Quitação Anual de Débitos (Lei 12.007/09) - {{ $contract->student?->name }}</title>
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

        .year-pill {
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            text-decoration: none;
            border: 1px solid #cbd5e1;
            color: #475569;
        }

        .year-pill.active {
            background-color: #0f172a;
            color: #ffffff;
            border-color: #0f172a;
        }

        .doc-page {
            max-width: 800px;
            min-height: 297mm;
            margin: 0 auto;
            background: #ffffff;
            padding: 22mm 24mm;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .school-header {
            text-align: center;
            border-bottom: 2px solid #1e3a8a;
            padding-bottom: 12px;
            margin-bottom: 25px;
        }

        .school-name {
            font-size: 17pt;
            font-weight: bold;
            color: #1e3a8a;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }

        .school-info {
            font-family: Arial, sans-serif;
            font-size: 8.5pt;
            color: #475569;
            line-height: 1.4;
        }

        .doc-title-box {
            text-align: center;
            margin: 15px 0 25px 0;
        }

        .doc-title {
            font-size: 15pt;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .doc-subtitle {
            font-family: Arial, sans-serif;
            font-size: 9.5pt;
            color: #475569;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 3px;
        }

        .doc-body {
            text-align: justify;
            text-indent: 35px;
            margin-bottom: 20px;
        }

        .doc-body p {
            margin-bottom: 14px;
        }

        .highlight {
            font-weight: bold;
            color: #0f172a;
        }

        /* Tabela de Parcelas */
        .installments-table {
            width: 100%;
            border-collapse: collapse;
            font-family: Arial, sans-serif;
            font-size: 8.5pt;
            margin: 15px 0 25px 0;
        }

        .installments-table th {
            background-color: #0f172a;
            color: #ffffff;
            font-weight: bold;
            text-transform: uppercase;
            padding: 5px 8px;
            border: 1px solid #0f172a;
            font-size: 8pt;
        }

        .installments-table td {
            padding: 4px 8px;
            border: 1px solid #cbd5e1;
            color: #1e293b;
        }

        .installments-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }

        .installments-table tr.total-row td {
            background-color: #e2e8f0;
            font-weight: bold;
            border-top: 2px solid #0f172a;
        }

        .status-badge-paid {
            color: #166534;
            font-weight: bold;
        }

        .status-badge-pending {
            color: #991b1b;
            font-weight: bold;
        }

        .date-location {
            text-align: right;
            margin: 30px 0 50px 0;
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
            padding-top: 5px;
            font-size: 9.5pt;
        }

        .sign-box .role {
            font-family: Arial, sans-serif;
            font-size: 8pt;
            color: #64748b;
            text-transform: uppercase;
        }

        .doc-footer {
            border-top: 1px dashed #cbd5e1;
            padding-top: 6px;
            margin-top: 25px;
            display: flex;
            justify-content: space-between;
            font-family: Arial, sans-serif;
            font-size: 7.5pt;
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

        $respName = $contract->responsible?->person?->name ?: $contract->student?->name;
        $respCpf = $contract->responsible?->person?->cpf
            ?? $contract->responsible?->person?->documents?->firstWhere('type', 'cpf')?->value
            ?? $studentCpf;

        $meses = [
            1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril',
            5 => 'maio', 6 => 'junho', 7 => 'julho', 8 => 'agosto',
            9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro'
        ];
        $hoje = now();
        $dataExtenso = $schoolCity . ' - ' . $schoolState . ', ' . $hoje->format('d') . ' de ' . ($meses[(int)$hoje->format('m')] ?? $hoje->format('F')) . ' de ' . $hoje->format('Y');

        $totalPagoNoAno = $installmentsOfYear->where('status', 'paid')->sum(fn($i) => (float) ($i->amount_paid ?: $i->amount));
    @endphp

    <div class="no-print">
        <div style="display: flex; align-items: center; gap: 15px;">
            <strong>Ano de Referência:</strong>
            <div style="display: flex; gap: 5px;">
                @foreach($availableYears as $yr)
                    <a href="?year={{ $yr }}" class="year-pill {{ $selectedYear == $yr ? 'active' : '' }}">{{ $yr }}</a>
                @endforeach
            </div>
        </div>
        <div style="display: flex; gap: 8px;">
            <button onclick="window.print()" class="btn btn-primary">
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
                    <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/>
                </svg>
                Imprimir Quitação
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
                <div class="doc-title">Declaração de Quitação Anual de Débitos</div>
                <div class="doc-subtitle">Emitida nos termos da Lei Federal nº 12.007, de 29 de julho de 2009</div>
            </div>

            <!-- CORPO DO TEXTO -->
            <div class="doc-body">
                <p>
                    A instituição de ensino <span class="highlight">{{ $schoolTrade }}</span>, inscrita no CNPJ sob o nº <span class="highlight">{{ $schoolCnpj ?: '—' }}</span>, em estrito cumprimento ao disposto na <span class="highlight">Lei Federal nº 12.007/2009</span>, DECLARA, para os devidos fins de direito, que o(a) aluno(a)
                    <span class="highlight">{{ $contract->student?->name ?? '—' }}</span>,
                    @if($studentCpf)
                        inscrito(a) no CPF sob o nº <span class="highlight">{{ $studentCpf }}</span>,
                    @endif
                    tendo como responsável financeiro <span class="highlight">{{ $respName }}</span>,
                    @if($respCpf)
                        portador(a) do CPF sob o nº <span class="highlight">{{ $respCpf }}</span>,
                    @endif
                    vinculado(a) ao Contrato de Prestação de Serviços Educacionais <span class="highlight">#{{ $contract->contract_number }}</span>:
                </p>

                @if($isFullyPaid)
                    <p>
                        <span class="highlight">QUITOU INTEGRALMENTE</span> todas as mensalidades e obrigações financeiras contratadas correspondentes ao ano civil e letivo de <span class="highlight">{{ $selectedYear }}</span>, nada mais restando a pagar ou reclamar relativamente ao referido exercício.
                    </p>
                @else
                    <p>
                        Apresenta o seguinte demonstrativo de quitação e movimentações financeiras relativamente ao ano civil e letivo de <span class="highlight">{{ $selectedYear }}</span>:
                    </p>
                @endif
            </div>

            <!-- TABELA DE DEMONSTRATIVO DAS PARCELAS DO ANO -->
            <table class="installments-table">
                <thead>
                    <tr>
                        <th style="width: 70px; text-align: center;">Parcela</th>
                        <th style="text-align: center;">Vencimento</th>
                        <th style="text-align: center;">Data Pagamento</th>
                        <th style="text-align: right;">Valor Base</th>
                        <th style="text-align: right;">Valor Pago</th>
                        <th style="width: 100px; text-align: center;">Situação</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($installmentsOfYear as $inst)
                        @php
                            $paid = $inst->status === 'paid' || $inst->paid_at !== null;
                        @endphp
                        <tr>
                            <td style="text-align: center; font-weight: bold;">
                                {{ str_pad((string) $inst->installment_number, 2, '0', STR_PAD_LEFT) }} / {{ str_pad((string) ($inst->total_installments ?: 1), 2, '0', STR_PAD_LEFT) }}
                            </td>
                            <td style="text-align: center;">{{ $inst->due_date ? $inst->due_date->format('d/m/Y') : '—' }}</td>
                            <td style="text-align: center;">{{ $inst->paid_at ? $inst->paid_at->format('d/m/Y') : '—' }}</td>
                            <td style="text-align: right;">R$ {{ number_format($inst->amount, 2, ',', '.') }}</td>
                            <td style="text-align: right; font-weight: bold;">
                                R$ {{ number_format($inst->amount_paid ?: ($paid ? $inst->amount : 0), 2, ',', '.') }}
                            </td>
                            <td style="text-align: center;">
                                @if($paid)
                                    <span class="status-badge-paid">Quitada</span>
                                @else
                                    <span class="status-badge-pending">Pendente</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; color: #64748b; padding: 12px;">
                                Nenhuma parcela contratada com vencimento no ano de {{ $selectedYear }}.
                            </td>
                        </tr>
                    @endforelse

                    @if($installmentsOfYear->isNotEmpty())
                        <tr class="total-row">
                            <td colspan="4" style="text-align: right; text-transform: uppercase;">Total Quitado no Exercício de {{ $selectedYear }}:</td>
                            <td style="text-align: right; font-weight: bold;">R$ {{ number_format($totalPagoNoAno, 2, ',', '.') }}</td>
                            <td style="text-align: center;">
                                @if($isFullyPaid)
                                    <span class="status-badge-paid">100% Quitado</span>
                                @else
                                    <span class="status-badge-pending">Saldo Pendente</span>
                                @endif
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>

            <div class="doc-body">
                <p>
                    A presente declaração substitui, para a comprovação do cumprimento das obrigações do consumidor relativas ao ano de <span class="highlight">{{ $selectedYear }}</span>, os comprovantes de pagamentos mensais emitidos pela instituição, em cumprimento ao § 1º do art. 2º da Lei Federal nº 12.007/2009.
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
                    <div style="font-weight: bold; color: #0f172a;">Departamento Financeiro / Tesouraria</div>
                    <div class="role">Setor de Contas a Receber</div>
                </div>
                <div class="sign-box">
                    <div style="font-weight: bold; color: #0f172a;">{{ $schoolRepresentative ?: ($schoolTrade) }}</div>
                    <div class="role">Direção Geral da Instituição</div>
                </div>
            </div>

            <div class="doc-footer">
                <div>Contrato: #{{ $contract->contract_number }} · Exercício de Referência: {{ $selectedYear }}</div>
                <div>Declaração de Quitação Anual válida nos termos da Lei Federal nº 12.007/2009</div>
                <div>Emissão: {{ now()->format('d/m/Y H:i') }}</div>
            </div>
        </div>
    </div>

</body>
</html>


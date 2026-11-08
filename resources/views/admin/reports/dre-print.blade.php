<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>DRE Gerencial - {{ $school?->trade_name ?? $school?->legal_name ?? 'Escola' }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 15mm;
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: #1f2937;
            line-height: 1.4;
            font-size: 11px;
            margin: 0;
            padding: 0;
            background: #fff;
        }

        .header {
            border-bottom: 2px solid #111827;
            padding-bottom: 10px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .header-title h1 {
            font-size: 17px;
            margin: 0 0 3px 0;
            text-transform: uppercase;
            font-weight: 900;
            color: #111827;
            letter-spacing: 0.5px;
        }

        .header-title p {
            margin: 0;
            font-size: 11px;
            color: #4b5563;
        }

        .header-meta {
            text-align: right;
            font-size: 10px;
            color: #6b7280;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 15px;
        }

        .card {
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 8px 10px;
            background: #f9fafb;
        }

        .card-label {
            font-size: 9px;
            text-transform: uppercase;
            font-weight: bold;
            color: #6b7280;
            margin-bottom: 3px;
        }

        .card-value {
            font-size: 15px;
            font-weight: 900;
        }

        .text-emerald { color: #059669; }
        .text-rose { color: #e11d48; }
        .text-blue { color: #2563eb; }
        .text-amber { color: #d97706; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        th {
            background-color: #f3f4f6;
            color: #374151;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 6px 10px;
            border: 1px solid #d1d5db;
        }

        td {
            padding: 5px 10px;
            border: 1px solid #e5e7eb;
            vertical-align: middle;
        }

        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .row-group {
            font-weight: bold;
            font-size: 11px;
        }

        .row-sub {
            padding-left: 25px;
            font-size: 10.5px;
            color: #4b5563;
        }

        .bg-green-light { background-color: #ecfdf5; }
        .bg-rose-light { background-color: #fff1f2; }
        .bg-blue-light { background-color: #eff6ff; }
        .bg-emerald-total { background-color: #d1fae5; font-weight: 900; font-size: 12px; }
        .bg-result-final { background-color: #dbeafe; font-weight: 900; font-size: 13px; }

        .footer-signatures {
            margin-top: 35px;
            display: flex;
            justify-content: space-between;
            page-break-inside: avoid;
        }

        .sig-block {
            width: 42%;
            text-align: center;
            border-top: 1px solid #111827;
            padding-top: 6px;
            font-size: 10.5px;
        }

        .sig-block strong {
            display: block;
            font-size: 11px;
            color: #111827;
        }

        .no-print-bar {
            background: #1e293b;
            color: #fff;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
        }

        .no-print-bar button {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 7px 16px;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            font-size: 12px;
        }

        @media print {
            .no-print-bar {
                display: none !important;
            }
        }
    </style>
</head>
<body onload="window.print()">

    <!-- Barra de Ações Superior (Não sai na impressão) -->
    <div class="no-print-bar">
        <span>Visualização para Impressão A4 - DRE Gerencial</span>
        <div>
            <button type="button" onclick="window.print()">🖨️ Imprimir Agora</button>
            <button type="button" onclick="window.close()" style="background: #64748b; margin-left: 8px;">Fechar</button>
        </div>
    </div>

    <div style="padding: 15px 20px;">
        <!-- Cabeçalho Institucional -->
        <div class="header">
            <div class="header-title">
                <h1>Demonstrativo do Resultado do Exercício (DRE)</h1>
                <p>
                    <strong>{{ $school?->trade_name ?? $school?->legal_name ?? 'Aurosoft Academy' }}</strong>
                    @if ($school?->cnpj) &bull; CNPJ: {{ $school->cnpj }} @endif
                    @if ($school?->phone) &bull; Tel: {{ $school->phone }} @endif
                </p>
                <p style="margin-top: 2px;">
                    Período Apurado: <strong>{{ $report['period']['start'] ?? '' }} até {{ $report['period']['end'] ?? '' }}</strong>
                    &bull; {{ $report['period']['regime_label'] ?? '' }}
                </p>
            </div>
            <div class="header-meta">
                <div>Emissão: {{ $generatedAt }}</div>
                <div>Software Aurosoft v2.0</div>
            </div>
        </div>

        <!-- Cards de Visão Executiva -->
        <div class="summary-grid">
            <div class="card">
                <div class="card-label">Receita Líquida</div>
                <div class="card-value text-emerald">
                    R$ {{ number_format((float) ($report['revenue']['net'] ?? 0), 2, ',', '.') }}
                </div>
            </div>
            <div class="card">
                <div class="card-label">Despesas Operacionais</div>
                <div class="card-value text-rose">
                    R$ {{ number_format((float) ($report['expenses']['total'] ?? 0), 2, ',', '.') }}
                </div>
            </div>
            <div class="card">
                <div class="card-label">Resultado Líquido</div>
                <div class="card-value {{ ($report['result']['is_profit'] ?? true) ? 'text-blue' : 'text-rose' }}">
                    R$ {{ number_format((float) ($report['result']['net_amount'] ?? 0), 2, ',', '.') }}
                </div>
            </div>
            <div class="card">
                <div class="card-label">Margem Operacional</div>
                <div class="card-value text-amber">
                    {{ number_format((float) ($report['result']['operating_margin'] ?? 0), 1, ',', '.') }}%
                </div>
            </div>
        </div>

        <!-- Estrutura Contábil -->
        <table>
            <thead>
                <tr>
                    <th class="text-left" style="width: 65%;">Classificação Contábil</th>
                    <th class="text-right" style="width: 20%;">Valor Nominal (R$)</th>
                    <th class="text-right" style="width: 15%;">% Sobre Receita</th>
                </tr>
            </thead>
            <tbody>
                <!-- 1. Receita Bruta -->
                <tr class="row-group bg-green-light">
                    <td>1. (+) RECEITA OPERACIONAL BRUTA</td>
                    <td class="text-right">R$ {{ number_format((float) $report['revenue']['gross'], 2, ',', '.') }}</td>
                    <td class="text-right">—</td>
                </tr>
                <tr>
                    <td class="row-sub">Mensalidades, Cursos e Matrículas</td>
                    <td class="text-right">R$ {{ number_format((float) $report['revenue']['installments_amount'], 2, ',', '.') }}</td>
                    <td class="text-right">—</td>
                </tr>
                @if ((float) $report['revenue']['fines'] > 0)
                    <tr>
                        <td class="row-sub">Multas e Juros Recebidos</td>
                        <td class="text-right text-emerald">+ R$ {{ number_format((float) $report['revenue']['fines'], 2, ',', '.') }}</td>
                        <td class="text-right">—</td>
                    </tr>
                @endif

                <!-- 2. Deduções -->
                <tr class="row-group bg-rose-light">
                    <td>2. (-) DEDUÇÕES DA RECEITA BRUTA</td>
                    <td class="text-right text-rose">- R$ {{ number_format((float) $report['revenue']['deductions'], 2, ',', '.') }}</td>
                    <td class="text-right">—</td>
                </tr>
                <tr>
                    <td class="row-sub">Descontos Comerciais e Pontualidade</td>
                    <td class="text-right text-rose">- R$ {{ number_format((float) $report['revenue']['deductions'], 2, ',', '.') }}</td>
                    <td class="text-right">—</td>
                </tr>

                <!-- 3. Receita Líquida -->
                <tr class="bg-emerald-total">
                    <td>3. (=) RECEITA OPERACIONAL LÍQUIDA</td>
                    <td class="text-right">R$ {{ number_format((float) $report['revenue']['net'], 2, ',', '.') }}</td>
                    <td class="text-right">100,0%</td>
                </tr>

                @if (!empty($report['revenue']['by_type']))
                    @foreach ($report['revenue']['by_type'] as $revType)
                        <tr>
                            <td class="row-sub" style="font-style: italic;">• {{ $revType['name'] }} ({{ $revType['count'] }} títulos)</td>
                            <td class="text-right">R$ {{ number_format((float) $revType['total'], 2, ',', '.') }}</td>
                            <td class="text-right">{{ $revType['percentage'] }}%</td>
                        </tr>
                    @endforeach
                @endif

                <!-- 4. Despesas Operacionais -->
                <tr class="row-group bg-rose-light">
                    <td>4. (-) DESPESAS E CUSTOS OPERACIONAIS (CONTAS A PAGAR)</td>
                    <td class="text-right text-rose">- R$ {{ number_format((float) $report['expenses']['total'], 2, ',', '.') }}</td>
                    <td class="text-right text-rose">
                        {{ $report['revenue']['net'] > 0 ? number_format(((float) $report['expenses']['total'] / (float) $report['revenue']['net']) * 100, 1, ',', '.') : '0' }}%
                    </td>
                </tr>

                @forelse ($report['expenses']['by_type'] as $expType)
                    <tr>
                        <td class="row-sub">
                            • {{ $expType['name'] }}
                            <span style="font-size: 9px; color: #9ca3af;">({{ $expType['count'] }} pagamentos)</span>
                        </td>
                        <td class="text-right text-rose">
                            - R$ {{ number_format((float) $expType['total'], 2, ',', '.') }}
                        </td>
                        <td class="text-right" style="color: #6b7280;">
                            {{ $expType['pct_revenue'] }}%
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="row-sub" style="color: #9ca3af; font-style: italic;">Nenhuma despesa operacional registrada no período.</td>
                    </tr>
                @endforelse

                <!-- 5. Resultado Final -->
                <tr class="bg-result-final">
                    <td>
                        5. (=) RESULTADO OPERACIONAL LÍQUIDO
                        <span style="font-size: 10px; font-weight: normal; margin-left: 5px;">
                            ({{ ($report['result']['is_profit'] ?? true) ? 'LUCRO DO PERÍODO' : 'PREJUÍZO DO PERÍODO' }})
                        </span>
                    </td>
                    <td class="text-right {{ ($report['result']['is_profit'] ?? true) ? 'text-blue' : 'text-rose' }}">
                        R$ {{ number_format((float) $report['result']['net_amount'], 2, ',', '.') }}
                    </td>
                    <td class="text-right">
                        {{ number_format((float) $report['result']['operating_margin'], 1, ',', '.') }}%
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Bloco de Assinaturas -->
        <div class="footer-signatures">
            <div class="sig-block">
                <strong>Direção Geral / Mantenedor</strong>
                {{ $school?->trade_name ?? $school?->legal_name ?? 'Direção Escolar' }}
            </div>
            <div class="sig-block">
                <strong>Responsável Financeiro / Contabilidade</strong>
                Gestão Financeira &bull; Aurosoft
            </div>
        </div>
    </div>

</body>
</html>


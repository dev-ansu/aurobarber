<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo de Pagamento - Parcela {{ $installment->installment_number }} - Contrato {{ $contract?->contract_number }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: #1e293b;
        }

        body {
            background-color: #f8fafc;
            padding: 20px 0;
        }

        .no-print {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 12px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1000;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
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
            transition: all 0.2s;
            border: none;
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

        .page-container {
            max-width: 800px;
            margin: 60px auto 20px auto;
            background: #ffffff;
            padding: 30px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        .receipt-card {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 20px 24px;
            background: #ffffff;
            position: relative;
        }

        .receipt-copy-badge {
            position: absolute;
            top: 15px;
            right: 20px;
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 3px 8px;
            border-radius: 4px;
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 12px;
            margin-bottom: 14px;
        }

        .school-info h1 {
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 2px;
        }

        .school-info p {
            font-size: 11px;
            color: #64748b;
            line-height: 1.4;
        }

        .receipt-title-box {
            text-align: right;
            padding-right: 90px;
        }

        .receipt-title {
            font-size: 18px;
            font-weight: 900;
            color: #2563eb;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .receipt-number {
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
        }

        .grid-two {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 14px;
        }

        .info-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 14px;
        }

        .info-box-title {
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            margin-bottom: 6px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 3px;
        }

        .info-line {
            font-size: 11px;
            margin-bottom: 3px;
            color: #334155;
        }

        .info-line strong {
            color: #0f172a;
        }

        .amount-highlight-box {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 6px;
            padding: 12px 16px;
            margin-bottom: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .amount-highlight-val {
            font-size: 20px;
            font-weight: 900;
            color: #166534;
        }

        .amount-in-words {
            font-size: 11px;
            font-weight: 600;
            color: #15803d;
            font-style: italic;
            margin-top: 2px;
        }

        .values-summary-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            margin-bottom: 12px;
        }

        .values-summary-table th {
            background-color: #f1f5f9;
            color: #475569;
            font-weight: 700;
            text-align: left;
            padding: 6px 10px;
            border: 1px solid #e2e8f0;
        }

        .values-summary-table td {
            padding: 6px 10px;
            border: 1px solid #e2e8f0;
            color: #334155;
        }

        .declaration-text {
            font-size: 11px;
            line-height: 1.5;
            color: #475569;
            text-align: justify;
            margin-bottom: 18px;
            background: #ffffff;
            padding: 4px 0;
        }

        .footer-receipt {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 10px;
            padding-top: 10px;
        }

        .date-city {
            font-size: 11px;
            color: #475569;
        }

        .sign-area {
            width: 280px;
            text-align: center;
        }

        .sign-line {
            border-top: 1px solid #334155;
            margin-bottom: 4px;
        }

        .sign-label {
            font-size: 10px;
            font-weight: 700;
            color: #0f172a;
        }

        .sign-sub {
            font-size: 9px;
            color: #64748b;
        }

        .cut-divider {
            margin: 25px 0;
            border-top: 1px dashed #94a3b8;
            position: relative;
            text-align: center;
        }

        .cut-label {
            position: absolute;
            top: -10px;
            left: 50%;
            transform: translateX(-50%);
            background: #ffffff;
            padding: 0 12px;
            font-size: 10px;
            color: #94a3b8;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
            }

            .no-print {
                display: none !important;
            }

            .page-container {
                max-width: none !important;
                margin: 0 !important;
                padding: 10mm 15mm !important;
                box-shadow: none !important;
                border-radius: 0 !important;
            }

            .receipt-card {
                border: 1px solid #94a3b8 !important;
                page-break-inside: avoid;
            }

            .cut-divider {
                margin: 20px 0 !important;
            }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <div>
            <strong>Recibo de Pagamento:</strong> Parcela {{ $installment->installment_number ?? 'Avulsa' }} &middot; Contrato {{ $contract?->contract_number ?? '—' }} &middot; {{ $contract?->student?->name ?? 'Aluno' }}
        </div>
        <div style="display: flex; gap: 10px;">
            <button onclick="window.print()" class="btn btn-primary">
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
                    <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/>
                </svg>
                Imprimir Recibo
            </button>
            <a href="javascript:window.close()" class="btn btn-secondary">Fechar</a>
        </div>
    </div>

    <div class="page-container">

        @php
            $schoolName = $school['trade_name'] ?? $school['legal_name'] ?? 'Aurosoft Escola Profissional';
            $schoolLogo = !empty($school['logo_url']) ? $school['logo_url'] : (!empty($school['logo_path']) ? \Illuminate\Support\Facades\Storage::disk('public')->url($school['logo_path']) : null);
            $schoolCnpj = $school['cnpj'] ?? null;
            $schoolPhone = $school['phone'] ?? null;
            $schoolEmail = $school['email'] ?? null;
            $schoolCity = ! empty($school['city'])
                ? $school['city']
                : ($contract?->student?->addresses?->first()?->city?->name ?? 'Município');
            $schoolState = ! empty($school['state'])
                ? $school['state']
                : ($contract?->student?->addresses?->first()?->city?->state?->acronym ?? 'UF');
            $paymentDate = $installment->paid_at ? \Carbon\Carbon::parse($installment->paid_at) : now();

            $courseName = $contract?->items?->first()?->formation?->name
                ?? $contract?->items?->first()?->contractCourses?->first()?->course?->name
                ?? 'Curso de Formação Profissional';

            $studentCpf = $contract?->student?->documents?->where('type', 'cpf')->first()?->number
                ?? $contract?->student?->cpf
                ?? '—';

            $respName = $contract?->responsible?->person?->name;
            $respCpf = $contract?->responsible?->person?->documents?->where('type', 'cpf')->first()?->number
                ?? $contract?->responsible?->person?->cpf;
        @endphp

        {{-- 1ª VIA: DO ALUNO --}}
        <div class="receipt-card">
            <span class="receipt-copy-badge">1ª Via · Aluno</span>

            <div class="header">
                <div style="display: flex; align-items: center; gap: 10px;">
                    @if ($schoolLogo)
                        <img src="{{ $schoolLogo }}" alt="Logo" style="max-height: 40px; max-width: 100px; object-fit: contain;">
                    @endif
                    <div class="school-info">
                        <h1>{{ $schoolName }}</h1>
                        <p>
                            @if($schoolCnpj) CNPJ: {{ $schoolCnpj }} &middot; @endif
                            @if($schoolPhone) Fone: {{ $schoolPhone }} &middot; @endif
                            @if($schoolEmail) {{ $schoolEmail }} @endif
                        </p>
                        <p>{{ $schoolCity }} - {{ $schoolState }}</p>
                    </div>
                </div>
                <div class="receipt-title-box">
                    <div class="receipt-title">Recibo</div>
                    <div class="receipt-number">Nº REC-{{ str_pad((string)$installment->id, 6, '0', STR_PAD_LEFT) }}</div>
                </div>
            </div>

            <div class="grid-two">
                <div class="info-box">
                    <div class="info-box-title">Dados do Aluno e Matrícula</div>
                    <div class="info-line"><strong>Aluno(a):</strong> {{ $contract?->student?->name ?? 'Não informado' }}</div>
                    <div class="info-line"><strong>CPF:</strong> {{ $studentCpf }}</div>
                    @if($respName && $respName !== $contract?->student?->name)
                        <div class="info-line"><strong>Resp. Financeiro:</strong> {{ $respName }} (CPF: {{ $respCpf ?? '—' }})</div>
                    @endif
                    <div class="info-line"><strong>Contrato:</strong> {{ $contract?->contract_number ?? '—' }}</div>
                    <div class="info-line"><strong>Formação / Curso:</strong> {{ $courseName }}</div>
                </div>

                <div class="info-box">
                    <div class="info-box-title">Dados da Parcela e Quitação</div>
                    <div class="info-line"><strong>Identificação:</strong> Parcela {{ $installment->installment_number ?? 1 }} de {{ $installment->total_installments ?? 1 }}</div>
                    <div class="info-line"><strong>Tipo de Cobrança:</strong> {{ $installment->financialType?->name ?? 'Mensalidade' }}</div>
                    <div class="info-line"><strong>Vencimento:</strong> {{ $installment->due_date ? \Carbon\Carbon::parse($installment->due_date)->format('d/m/Y') : '—' }}</div>
                    <div class="info-line"><strong>Data do Pagamento:</strong> {{ $paymentDate->format('d/m/Y') }}</div>
                    <div class="info-line"><strong>Forma de Pagamento:</strong> {{ $installment->paymentMethod?->name ?? 'Dinheiro / Pix' }}</div>
                </div>
            </div>

            <table class="values-summary-table">
                <thead>
                    <tr>
                        <th>Valor Base</th>
                        <th>Desconto Concedido</th>
                        <th>Multa / Acréscimo</th>
                        <th>Valor Líquido Quitado</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>R$ {{ number_format((float)$installment->amount, 2, ',', '.') }}</td>
                        <td>R$ {{ number_format((float)($installment->discount ?? 0), 2, ',', '.') }}</td>
                        <td>R$ {{ number_format((float)($installment->fine ?? 0), 2, ',', '.') }}</td>
                        <td><strong style="color: #166534; font-size: 13px;">R$ {{ number_format($amountPaid, 2, ',', '.') }}</strong></td>
                    </tr>
                </tbody>
            </table>

            <div class="amount-highlight-box">
                <div>
                    <span style="font-size: 10px; font-weight: 700; text-transform: uppercase; color: #166534;">Total Efetivamente Pago:</span>
                    <div class="amount-highlight-val">R$ {{ number_format($amountPaid, 2, ',', '.') }}</div>
                    <div class="amount-in-words">Valor por extenso: {{ $amountInWords }}</div>
                </div>
                <div style="text-align: right; font-size: 11px; color: #15803d; font-weight: 700;">
                    STATUS: QUITADO
                </div>
            </div>

            <div class="declaration-text">
                Recebemos de <strong>{{ $respName ?: $contract?->student?->name }}</strong> a quantia de <strong>R$ {{ number_format($amountPaid, 2, ',', '.') }} ({{ $amountInWords }})</strong>, correspondente à quitação integral da parcela descrita acima, para a qual damos plena, geral e irrevogável quitação.
            </div>

            <div class="footer-receipt">
                <div class="date-city">
                    {{ $schoolCity }} - {{ $schoolState }}, {{ $paymentDate->translatedFormat('d \d\e F \d\e Y') }}
                </div>
                <div class="sign-area">
                    <div class="sign-line"></div>
                    <div class="sign-label">{{ $schoolName }}</div>
                    <div class="sign-sub">Secretaria Escolar / Tesouraria</div>
                </div>
            </div>
        </div>

        {{-- LINHA DE CORTE --}}
        <div class="cut-divider">
            <span class="cut-label">&#9986; Destaque aqui · 2ª Via Escola &#9986;</span>
        </div>

        {{-- 2ª VIA: DA ESCOLA --}}
        <div class="receipt-card">
            <span class="receipt-copy-badge">2ª Via · Arquivo da Escola</span>

            <div class="header">
                <div style="display: flex; align-items: center; gap: 10px;">
                    @if ($schoolLogo)
                        <img src="{{ $schoolLogo }}" alt="Logo" style="max-height: 40px; max-width: 100px; object-fit: contain;">
                    @endif
                    <div class="school-info">
                        <h1>{{ $schoolName }}</h1>
                        <p>Controle de Caixa e Tesouraria &middot; Comprovante de Recebimento</p>
                    </div>
                </div>
                <div class="receipt-title-box">
                    <div class="receipt-title">Recibo</div>
                    <div class="receipt-number">Nº REC-{{ str_pad((string)$installment->id, 6, '0', STR_PAD_LEFT) }}</div>
                </div>
            </div>

            <div class="grid-two">
                <div class="info-box">
                    <div class="info-box-title">Dados do Aluno e Matrícula</div>
                    <div class="info-line"><strong>Aluno(a):</strong> {{ $contract?->student?->name ?? 'Não informado' }}</div>
                    <div class="info-line"><strong>CPF:</strong> {{ $studentCpf }}</div>
                    @if($respName && $respName !== $contract?->student?->name)
                        <div class="info-line"><strong>Resp. Financeiro:</strong> {{ $respName }}</div>
                    @endif
                    <div class="info-line"><strong>Contrato:</strong> {{ $contract?->contract_number ?? '—' }}</div>
                    <div class="info-line"><strong>Curso:</strong> {{ $courseName }}</div>
                </div>

                <div class="info-box">
                    <div class="info-box-title">Dados da Parcela e Quitação</div>
                    <div class="info-line"><strong>Identificação:</strong> Parcela {{ $installment->installment_number ?? 1 }} de {{ $installment->total_installments ?? 1 }}</div>
                    <div class="info-line"><strong>Tipo de Cobrança:</strong> {{ $installment->financialType?->name ?? 'Mensalidade' }}</div>
                    <div class="info-line"><strong>Data de Vencimento:</strong> {{ $installment->due_date ? \Carbon\Carbon::parse($installment->due_date)->format('d/m/Y') : '—' }}</div>
                    <div class="info-line"><strong>Data do Pagamento:</strong> {{ $paymentDate->format('d/m/Y') }}</div>
                    <div class="info-line"><strong>Forma de Pagto:</strong> {{ $installment->paymentMethod?->name ?? 'Dinheiro / Pix' }}</div>
                </div>
            </div>

            <table class="values-summary-table">
                <thead>
                    <tr>
                        <th>Valor Base</th>
                        <th>Desconto</th>
                        <th>Multa / Acréscimo</th>
                        <th>Valor Quitado</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>R$ {{ number_format((float)$installment->amount, 2, ',', '.') }}</td>
                        <td>R$ {{ number_format((float)($installment->discount ?? 0), 2, ',', '.') }}</td>
                        <td>R$ {{ number_format((float)($installment->fine ?? 0), 2, ',', '.') }}</td>
                        <td><strong style="color: #166534; font-size: 13px;">R$ {{ number_format($amountPaid, 2, ',', '.') }}</strong></td>
                    </tr>
                </tbody>
            </table>

            <div class="amount-highlight-box" style="padding: 8px 14px;">
                <div>
                    <span style="font-size: 10px; font-weight: 700; color: #166534;">VALOR QUITADO:</span>
                    <strong style="font-size: 16px; color: #166534; margin-left: 6px;">R$ {{ number_format($amountPaid, 2, ',', '.') }}</strong>
                    <span style="font-size: 10px; color: #15803d; margin-left: 6px;">({{ $amountInWords }})</span>
                </div>
            </div>

            <div class="footer-receipt" style="margin-top: 15px;">
                <div class="date-city">
                    {{ $schoolCity }} - {{ $schoolState }}, {{ $paymentDate->translatedFormat('d \d\e F \d\e Y') }}
                </div>
                <div class="sign-area">
                    <div class="sign-line"></div>
                    <div class="sign-label">Assinatura do Aluno / Responsável</div>
                    <div class="sign-sub">Declaro haver pago o valor acima discriminado</div>
                </div>
            </div>
        </div>

    </div>

</body>
</html>

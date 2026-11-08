<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carnê de Pagamento - Contrato #{{ $contract->contract_number }} - {{ $contract->student?->name }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 6mm 8mm;
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body {
            font-family: Arial, "Helvetica Neue", Helvetica, sans-serif;
            font-size: 10px;
            color: #111;
            background-color: #f3f4f6;
            margin: 0;
            padding: 15px;
        }

        .no-print {
            max-width: 1050px;
            margin: 0 auto 15px auto;
            background: #ffffff;
            border-radius: 8px;
            padding: 12px 20px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 13px;
        }

        .no-print .info {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .no-print .info strong {
            color: #0f172a;
        }

        .no-print .actions {
            display: flex;
            gap: 10px;
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

        .carne-page {
            max-width: 1050px;
            margin: 0 auto 20px auto;
            background: #ffffff;
            padding: 4mm 4mm;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }

        .slip-wrapper {
            margin-bottom: 3.5mm;
        }

        .slip-wrapper:last-child {
            margin-bottom: 0;
        }

        .cut-line {
            display: flex;
            align-items: center;
            margin: 2.5mm 0;
            color: #71717a;
            font-size: 9px;
            user-select: none;
        }

        .cut-line .scissors {
            margin-right: 6px;
            font-size: 11px;
        }

        .cut-line .line {
            flex: 1;
            border-bottom: 1px dashed #94a3b8;
            height: 1px;
        }

        .carne-slip {
            display: flex;
            border: 1.2px solid #1e293b;
            background: #ffffff;
            height: 57mm;
            overflow: hidden;
            border-radius: 3px;
        }

        /* ---------------- CANHOTO (ESCOLAR) ---------------- */
        .stub-school {
            width: 28%;
            border-right: 1.5px dashed #475569;
            padding: 4px 6px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background: #fafafa;
            position: relative;
        }

        .stub-header {
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 2px;
            margin-bottom: 3px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stub-header .title {
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            color: #475569;
            letter-spacing: 0.5px;
        }

        .stub-header .contract-badge {
            font-size: 8.5px;
            font-weight: bold;
            color: #0f172a;
        }

        .stub-field {
            margin-bottom: 2.5px;
            font-size: 8.5px;
            line-height: 1.15;
        }

        .stub-field .lbl {
            font-size: 7.5px;
            text-transform: uppercase;
            color: #64748b;
            display: block;
            font-weight: 600;
        }

        .stub-field .val {
            font-size: 9px;
            font-weight: 600;
            color: #0f172a;
        }

        .stub-highlight {
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 2px;
            padding: 2px 4px;
            margin: 2px 0;
            display: flex;
            justify-content: space-between;
        }

        .stub-sign-area {
            border-top: 1px dashed #cbd5e1;
            padding-top: 2px;
            margin-top: 2px;
            font-size: 7.5px;
            color: #64748b;
        }

        .stub-sign-line {
            border-top: 1px solid #334155;
            margin-top: 12px;
            text-align: center;
            font-size: 7px;
            color: #475569;
        }

        /* ---------------- CORPO PRINCIPAL (ALUNO) ---------------- */
        .body-student {
            width: 72%;
            padding: 5px 8px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background: #ffffff;
            position: relative;
        }

        .main-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 1.2px solid #0f172a;
            padding-bottom: 3px;
            margin-bottom: 3px;
        }

        .school-info {
            max-width: 60%;
        }

        .school-name {
            font-size: 11px;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            line-height: 1.1;
            margin-bottom: 1px;
        }

        .school-meta {
            font-size: 8px;
            color: #475569;
            line-height: 1.15;
        }

        .metric-boxes {
            display: flex;
            gap: 4px;
        }

        .metric-box {
            border: 1px solid #0f172a;
            background: #f8fafc;
            border-radius: 3px;
            padding: 2px 6px;
            text-align: center;
            min-width: 65px;
        }

        .metric-box.highlight {
            background: #0f172a;
            color: #ffffff;
        }

        .metric-box .box-lbl {
            font-size: 7px;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.3px;
            color: #64748b;
            margin-bottom: 1px;
        }

        .metric-box.highlight .box-lbl {
            color: #94a3b8;
        }

        .metric-box .box-val {
            font-size: 10px;
            font-weight: 800;
            color: #0f172a;
            line-height: 1;
        }

        .metric-box.highlight .box-val {
            color: #ffffff;
        }

        .main-data-grid {
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            gap: 2px 6px;
            margin-bottom: 3px;
            font-size: 8.5px;
        }

        .col-8 { grid-column: span 8; }
        .col-7 { grid-column: span 7; }
        .col-6 { grid-column: span 6; }
        .col-5 { grid-column: span 5; }
        .col-4 { grid-column: span 4; }
        .col-3 { grid-column: span 3; }
        .col-12 { grid-column: span 12; }

        .field-group {
            line-height: 1.15;
        }

        .field-group .f-lbl {
            font-size: 7px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
        }

        .field-group .f-val {
            font-size: 8.5px;
            font-weight: 600;
            color: #0f172a;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .instructions-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 2px;
            padding: 3px 5px;
            font-size: 7px;
            color: #334155;
            line-height: 1.25;
            margin-top: 1px;
        }

        .instructions-box strong {
            color: #0f172a;
        }

        /* ---------------- BLOCO DO PIX NO CARNÊ ---------------- */
        .instructions-pix-row {
            display: flex;
            gap: 5px;
            align-items: stretch;
            margin-top: 1px;
        }

        .instructions-col {
            flex: 1;
            min-width: 0;
            display: flex;
        }

        .instructions-col .instructions-box {
            width: 100%;
            margin-top: 0;
        }

        .pix-col {
            flex: 0 0 auto;
        }

        .pix-card {
            display: flex;
            align-items: center;
            gap: 5px;
            background: #f8fafc;
            border: 1px solid #0f172a;
            border-radius: 3px;
            padding: 2px 4px;
            height: 100%;
        }

        .pix-qr-wrapper {
            flex: 0 0 46px;
            width: 46px;
            height: 46px;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #cbd5e1;
            border-radius: 2px;
        }

        .pix-qr-img {
            width: 42px;
            height: 42px;
            display: block;
        }

        .pix-meta {
            display: flex;
            flex-direction: column;
            justify-content: center;
            font-size: 7px;
            line-height: 1.15;
            min-width: 110px;
            max-width: 150px;
        }

        .pix-badge {
            background: #0f172a;
            color: #ffffff;
            font-weight: 800;
            font-size: 6.5px;
            letter-spacing: 0.4px;
            padding: 1px 3px;
            border-radius: 2px;
            margin-bottom: 2px;
            display: inline-block;
            width: fit-content;
        }

        .pix-line {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .pix-lbl {
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            font-size: 6.5px;
        }

        .pix-val {
            font-weight: 700;
            color: #0f172a;
            font-size: 7px;
        }

        .main-footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            border-top: 1px dashed #cbd5e1;
            padding-top: 2px;
            margin-top: 2px;
            font-size: 7.5px;
            color: #64748b;
        }

        .auth-space {
            font-size: 7.5px;
            color: #475569;
        }

        .auth-space span {
            display: inline-block;
            border-bottom: 1px solid #64748b;
            min-width: 170px;
            margin-left: 3px;
        }

        /* ---------------- SELO DE PAGO / STATUS ---------------- */
        .stamp-paid {
            display: inline-block;
            border: 1.5px solid #16a34a;
            color: #16a34a;
            font-weight: 800;
            font-size: 8px;
            text-transform: uppercase;
            padding: 1px 5px;
            border-radius: 3px;
            letter-spacing: 0.5px;
        }

        .stamp-pending {
            display: inline-block;
            border: 1px solid #94a3b8;
            color: #475569;
            font-weight: 600;
            font-size: 7.5px;
            text-transform: uppercase;
            padding: 1px 4px;
            border-radius: 2px;
        }

        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
            }

            .no-print {
                display: none !important;
            }

            .carne-page {
                box-shadow: none !important;
                padding: 0 !important;
                margin: 0 !important;
                page-break-after: always;
                break-after: page;
            }

            .carne-page:last-child {
                page-break-after: avoid;
                break-after: avoid;
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

        $schoolAddress = collect([
            trim(($school['street'] ?? '').' '.($school['number'] ?? '')),
            $school['complement'] ?? null,
            $school['neighborhood'] ?? null,
            trim(($school['city'] ?? '').(!empty($school['state']) ? ' - '.$school['state'] : '')),
        ])->filter()->join(', ');

        $studentCpf = $contract->student?->cpf
            ?? $contract->student?->documents?->firstWhere('type', 'cpf')?->value
            ?? 'Não informado';

        $responsibleName = $contract->responsible?->person?->name;
        $responsibleCpf = $contract->responsible?->person?->cpf
            ?? $contract->responsible?->person?->documents?->firstWhere('type', 'cpf')?->value;

        $formations = $contract->items->map(fn($item) => $item->formation?->name)->filter()->unique()->join(', ');
        $courses = $contract->items->flatMap(fn($item) => $item->contractCourses->map(fn($cc) => $cc->course?->name))->filter()->unique()->join(', ');
        $courseLabel = $formations ?: ($courses ?: 'Cursos Profissionalizantes');

        $totalInstallmentsCount = $contract->installments->count();
        $totalInstallmentsValue = $contract->installments->sum('amount');
    @endphp

    <div class="no-print">
        <div class="info">
            <div>
                <strong>Contrato:</strong> #{{ $contract->contract_number }}
            </div>
            <div>
                <strong>Aluno:</strong> {{ $contract->student?->name ?? '—' }}
            </div>
            <div>
                <strong>Total Parcelas:</strong> {{ $totalInstallmentsCount }}
            </div>
            <div>
                <strong>Valor Global:</strong> R$ {{ number_format($contract->total_value, 2, ',', '.') }}
            </div>
        </div>
        <div class="actions">
            <button onclick="window.print()" class="btn btn-primary">
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
                    <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/>
                </svg>
                Imprimir Carnê
            </button>
            <a href="javascript:window.close()" class="btn btn-secondary">Fechar</a>
        </div>
    </div>

    @forelse($installments->chunk(3) as $pageIndex => $chunk)
        <div class="carne-page">
            @foreach($chunk as $installment)
                @php
                    $instNumber = $installment->installment_number ?? $loop->iteration;
                    $instTotal = $installment->total_installments ?? $totalInstallmentsCount;
                    $isPaid = $installment->status === 'paid' || $installment->paid_at !== null;
                    $pixData = $pixDataByInstallment[$installment->id] ?? null;
                @endphp

                <div class="slip-wrapper">
                    <div class="carne-slip">
                        <!-- CANHOTO DA ESCOLA / CAIXA -->
                        <div class="stub-school">
                            <div>
                                <div class="stub-header">
                                    <span class="title">Via do Caixa / Escola</span>
                                    <span class="contract-badge">#{{ $contract->contract_number }}</span>
                                </div>

                                <div class="stub-highlight">
                                    <div>
                                        <div style="font-size: 7px; color: #64748b; font-weight: 700; text-transform: uppercase;">PARCELA</div>
                                        <div style="font-size: 9px; font-weight: 800; color: #0f172a;">{{ str_pad($instNumber, 2, '0', STR_PAD_LEFT) }}/{{ str_pad($instTotal, 2, '0', STR_PAD_LEFT) }}</div>
                                    </div>
                                    <div style="text-align: right;">
                                        <div style="font-size: 7px; color: #64748b; font-weight: 700; text-transform: uppercase;">VALOR R$</div>
                                        <div style="font-size: 9px; font-weight: 800; color: #0f172a;">{{ number_format($installment->amount, 2, ',', '.') }}</div>
                                    </div>
                                </div>

                                <div class="stub-field" style="margin-top: 3px;">
                                    <span class="lbl">Vencimento</span>
                                    <span class="val">{{ $installment->due_date?->format('d/m/Y') ?? '—' }}</span>
                                </div>

                                <div class="stub-field">
                                    <span class="lbl">Aluno</span>
                                    <span class="val" title="{{ $contract->student?->name }}">{{ Str::limit($contract->student?->name ?? '—', 26) }}</span>
                                </div>

                                @if($installment->financialType)
                                    <div class="stub-field">
                                        <span class="lbl">Tipo</span>
                                        <span class="val">{{ $installment->financialType->name }}</span>
                                    </div>
                                @endif

                                @if($pixData && !empty($pixData['pix_key']))
                                    <div class="stub-field">
                                        <span class="lbl">Chave PIX</span>
                                        <span class="val" title="{{ $pixData['pix_key'] }}" style="font-size: 8px;">{{ Str::limit($pixData['pix_key'], 20) }}</span>
                                    </div>
                                @endif
                            </div>

                            <div>
                                @if($isPaid)
                                    <div style="text-align: center; margin-bottom: 2px;">
                                        <span class="stamp-paid">Quitado em {{ $installment->paid_at?->format('d/m/Y') ?? '—' }}</span>
                                    </div>
                                @else
                                    <div class="stub-sign-area">
                                        <div>Data Pgto: ____/____/________</div>
                                        <div style="margin-top: 2px;">Valor Recebido: R$ ____________</div>
                                    </div>
                                    <div class="stub-sign-line">
                                        Visto do Caixa / Recebedor
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- VIA DO ALUNO (RECIBO DO SACADO) -->
                        <div class="body-student">
                            <!-- CABEÇALHO -->
                            <div class="main-header" style="display: flex; justify-content: space-between; align-items: flex-start; gap: 8px;">
                                <div style="display: flex; align-items: center; gap: 8px; max-width: 65%;">
                                    @if ($schoolLogo)
                                        <img src="{{ $schoolLogo }}" alt="Logo" style="max-height: 28px; max-width: 75px; object-fit: contain;">
                                    @endif
                                    <div class="school-info" style="max-width: 100%;">
                                        <div class="school-name">{{ $schoolTrade }}</div>
                                        <div class="school-meta">
                                            @if($schoolCnpj) CNPJ: {{ $schoolCnpj }} @endif
                                            @if($schoolPhone) | Tel: {{ $schoolPhone }} @endif
                                        </div>
                                        @if($schoolAddress)
                                            <div class="school-meta" style="color: #64748b;">{{ $schoolAddress }}</div>
                                        @endif
                                    </div>
                                </div>

                                <div class="metric-boxes">
                                    <div class="metric-box">
                                        <div class="box-lbl">Parcela</div>
                                        <div class="box-val">{{ str_pad($instNumber, 2, '0', STR_PAD_LEFT) }} / {{ str_pad($instTotal, 2, '0', STR_PAD_LEFT) }}</div>
                                    </div>
                                    <div class="metric-box">
                                        <div class="box-lbl">Vencimento</div>
                                        <div class="box-val">{{ $installment->due_date?->format('d/m/Y') ?? '—' }}</div>
                                    </div>
                                    <div class="metric-box highlight">
                                        <div class="box-lbl">Valor Documento</div>
                                        <div class="box-val">R$ {{ number_format($installment->amount, 2, ',', '.') }}</div>
                                    </div>
                                </div>
                            </div>

                            <!-- DADOS DO ALUNO E CONTRATO -->
                            <div class="main-data-grid">
                                <div class="field-group col-7">
                                    <span class="f-lbl">Aluno / Pagador</span>
                                    <div class="f-val">{{ $contract->student?->name ?? '—' }}</div>
                                </div>
                                <div class="field-group col-3">
                                    <span class="f-lbl">CPF do Aluno</span>
                                    <div class="f-val">{{ $studentCpf }}</div>
                                </div>
                                <div class="field-group col-2">
                                    <span class="f-lbl">Cód. Aluno</span>
                                    <div class="f-val">{{ $contract->student?->ouro_moderno_cod_int ?: $contract->student_id }}</div>
                                </div>

                                @if($responsibleName && $responsibleName !== $contract->student?->name)
                                    <div class="field-group col-7">
                                        <span class="f-lbl">Responsável Financeiro</span>
                                        <div class="f-val">{{ $responsibleName }}</div>
                                    </div>
                                    <div class="field-group col-3">
                                        <span class="f-lbl">CPF Responsável</span>
                                        <div class="f-val">{{ $responsibleCpf ?: '—' }}</div>
                                    </div>
                                    <div class="field-group col-2">
                                        <span class="f-lbl">Contrato</span>
                                        <div class="f-val">#{{ $contract->contract_number }}</div>
                                    </div>
                                @else
                                    <div class="field-group col-7">
                                        <span class="f-lbl">Formação / Cursos</span>
                                        <div class="f-val" title="{{ $courseLabel }}">{{ Str::limit($courseLabel, 55) }}</div>
                                    </div>
                                    <div class="field-group col-3">
                                        <span class="f-lbl">Tipo Cobrança</span>
                                        <div class="f-val">{{ $installment->financialType?->name ?? 'Mensalidade' }}</div>
                                    </div>
                                    <div class="field-group col-2">
                                        <span class="f-lbl">Contrato</span>
                                        <div class="f-val">#{{ $contract->contract_number }}</div>
                                    </div>
                                @endif

                                @if($responsibleName && $responsibleName !== $contract->student?->name)
                                    <div class="field-group col-12">
                                        <span class="f-lbl">Formação / Cursos</span>
                                        <div class="f-val" title="{{ $courseLabel }}">{{ Str::limit($courseLabel, 85) }}</div>
                                    </div>
                                @endif
                            </div>

                            <!-- INSTRUÇÕES E PIX -->
                            @if($pixData && !empty($pixData['qr_code_uri']))
                                <div class="instructions-pix-row">
                                    <div class="instructions-col">
                                        <div class="instructions-box">
                                            <strong>Instruções ao Aluno e ao Caixa:</strong>
                                            Pagável na secretaria ou canais autorizados. Não receber após 30 dias de vencido sem autorização prévia.
                                            @if(!empty($pixData['instructions']))
                                                <div style="margin-top: 1px; color: #0f172a; font-weight: 700;">
                                                    • {{ $pixData['instructions'] }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="pix-col">
                                        <div class="pix-card">
                                            <div class="pix-qr-wrapper">
                                                <img src="{{ $pixData['qr_code_uri'] }}" alt="QR Code Pix" class="pix-qr-img" />
                                            </div>
                                            <div class="pix-meta">
                                                <div class="pix-badge">
                                                    <svg width="8" height="8" viewBox="0 0 24 24" fill="currentColor" style="display:inline-block; vertical-align:-1px; margin-right:1px;">
                                                        <path d="M12.001 2c-.414 0-.828.158-1.144.474L3.474 9.856a1.616 1.616 0 0 0 0 2.288l7.383 7.382c.631.631 1.657.631 2.288 0l7.382-7.382a1.616 1.616 0 0 0 0-2.288l-7.382-7.382A1.611 1.611 0 0 0 12.001 2zm-1.06 4.708l3.182 3.182-3.182 3.182-3.182-3.182 3.182-3.182z"/>
                                                    </svg>
                                                    PAGUE COM PIX
                                                </div>
                                                <div class="pix-line"><span class="pix-lbl">Chave:</span> <span class="pix-val" title="{{ $pixData['pix_key'] }}">{{ Str::limit($pixData['pix_key'], 22) }}</span></div>
                                                <div class="pix-line"><span class="pix-lbl">Benef:</span> <span class="pix-val" title="{{ $pixData['merchant_name'] }}">{{ Str::limit($pixData['merchant_name'], 18) }}</span></div>
                                                <div class="pix-line"><span class="pix-lbl">Ident:</span> <span class="pix-val">{{ $pixData['txid'] }}</span></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="instructions-box">
                                    <strong>Instruções ao Aluno e ao Caixa:</strong>
                                    Pagável preferencialmente na secretaria da instituição ou canais digitais autorizados.
                                    Não receber após 30 dias de vencido sem a prévia autorização da secretaria.
                                    Após o vencimento, incidirão juros moratórios e encargos contratuais.
                                </div>
                            @endif

                            <!-- RODAPÉ E AUTENTICAÇÃO -->
                            <div class="main-footer">
                                <div>
                                    @if($isPaid)
                                        <span class="stamp-paid">Quitado em {{ $installment->paid_at?->format('d/m/Y') ?? '—' }} - R$ {{ number_format($installment->amount_paid ?: $installment->amount, 2, ',', '.') }}</span>
                                    @else
                                        <span class="stamp-pending">Pendente de Pagamento</span>
                                    @endif
                                </div>

                                <div class="auth-space">
                                    Autenticação Mecânica / Visto: <span></span>
                                </div>

                                <div>
                                    Emissão: {{ now()->format('d/m/Y H:i') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @if(!$loop->last)
                    <div class="cut-line">
                        <span class="scissors">✂</span>
                        <div class="line"></div>
                    </div>
                @endif
            @endforeach
        </div>
    @empty
        <div class="carne-page" style="text-align: center; padding: 40px;">
            <p style="font-size: 14px; color: #64748b;">Nenhuma parcela encontrada para este contrato.</p>
        </div>
    @endforelse

</body>
</html>


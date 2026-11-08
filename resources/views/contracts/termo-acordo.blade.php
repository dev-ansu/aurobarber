<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Termo de Acordo e Confissão de Dívida - {{ $contract->contract_number }} - {{ $contract->student?->name }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 18mm;
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
            padding: 15px;
            font-size: 11pt;
            line-height: 1.6;
        }

        .no-print {
            max-width: 820px;
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

        .doc-page {
            max-width: 820px;
            margin: 0 auto;
            background: #ffffff;
            padding: 18mm 20mm;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .school-header {
            text-align: center;
            border-bottom: 2px solid #1e3a8a;
            padding-bottom: 10px;
            margin-bottom: 20px;
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
            line-height: 1.35;
        }

        .doc-title-box {
            text-align: center;
            margin: 15px 0 20px 0;
        }

        .doc-title {
            font-size: 13pt;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .doc-subtitle {
            font-family: Arial, sans-serif;
            font-size: 9pt;
            color: #475569;
            font-weight: bold;
            margin-top: 2px;
        }

        .clause-title {
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            font-size: 10pt;
            margin-top: 14px;
            margin-bottom: 4px;
        }

        .clause-text {
            text-align: justify;
            text-indent: 25px;
            margin-bottom: 8px;
        }

        .highlight {
            font-weight: bold;
            color: #0f172a;
        }

        /* Tabelas */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-family: Arial, sans-serif;
            font-size: 8.5pt;
            margin: 8px 0 12px 0;
        }

        .items-table th {
            background-color: #0f172a;
            color: #ffffff;
            font-weight: bold;
            text-transform: uppercase;
            padding: 4px 6px;
            border: 1px solid #0f172a;
            font-size: 7.5pt;
        }

        .items-table td {
            padding: 3.5px 6px;
            border: 1px solid #cbd5e1;
            color: #1e293b;
        }

        .items-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }

        .items-table tr.total-row td {
            background-color: #e2e8f0;
            font-weight: bold;
            border-top: 1.5px solid #0f172a;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }

        .date-location {
            text-align: right;
            margin: 25px 0 35px 0;
            font-style: italic;
        }

        /* Assinaturas */
        .signatures-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 25px;
        }

        .sign-col {
            text-align: center;
        }

        .sign-line {
            border-top: 1px solid #1e293b;
            padding-top: 4px;
            font-size: 9pt;
            font-weight: bold;
            color: #0f172a;
        }

        .sign-sub {
            font-family: Arial, sans-serif;
            font-size: 7.5pt;
            color: #64748b;
            text-transform: uppercase;
        }

        .witness-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-top: 10px;
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
                padding: 5mm 10mm !important;
                margin: 0 !important;
            }
        }
    </style>
</head>
<body>

    @php
        $schoolTrade = $school['trade_name'] ?? $school['legal_name'] ?? 'Aurosoft Cursos Profissionalizantes';
        $schoolLegal = $school['legal_name'] ?? $schoolTrade;
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

        $student = $contract->student;
        $studentCpf = $student?->cpf
            ?? $student?->documents?->firstWhere('type', 'cpf')?->value ?? '—';

        $responsible = $contract->responsible?->person ?? $student;
        $respName = $responsible?->name ?? 'Responsável';
        $respCpf = $responsible?->cpf
            ?? $responsible?->documents?->firstWhere('type', 'cpf')?->value ?? $studentCpf;
        $respRg = $responsible?->documents?->firstWhere('type', 'rg')?->value ?? 'Não informado';

        $respAddressObj = $responsible?->addresses?->first() ?? $student?->addresses?->first();
        $respAddress = $respAddressObj
            ? trim(($respAddressObj->street ?? '').' '.($respAddressObj->number ?? '').', '.($respAddressObj->neighborhood ?? '').' - '.($respAddressObj->city?->name ?? '').'/'.($respAddressObj->city?->state?->uf ?? ''))
            : 'Endereço cadastrado nos registros da contratada';

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
            <strong>Acordo Financeiro:</strong> Termo de Confissão de Dívida #{{ $debtAgreement->agreement_number }} · Contrato #{{ $contract->contract_number }}
        </div>
        <div style="display: flex; gap: 8px;">
            <button onclick="window.print()" class="btn btn-primary">
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
                    <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/>
                </svg>
                Imprimir Termo de Acordo
            </button>
            <a href="javascript:window.close()" class="btn btn-secondary">Fechar</a>
        </div>
    </div>

    <div class="doc-page">
        <!-- CABEÇALHO -->
        <div class="school-header">
            <div class="school-name">{{ $schoolTrade }}</div>
            <div class="school-info">
                {{ $schoolLegal }} @if($schoolCnpj) · CNPJ: {{ $schoolCnpj }} @endif<br>
                @if($schoolAddress) {{ $schoolAddress }} @endif
                @if($schoolPhone) · Tel: {{ $schoolPhone }} @endif
            </div>
        </div>

        <!-- TÍTULO -->
        <div class="doc-title-box">
            <div class="doc-title">Termo de Acordo e Confissão de Dívida</div>
            <div class="doc-subtitle">Instrumento Particular de Reconhecimento e Renegociação nº {{ $debtAgreement->agreement_number }} · Contrato #{{ $contract->contract_number }}</div>
        </div>

        <!-- PREÂMBULO -->
        <div class="clause-text">
            Por este instrumento particular, de um lado:
        </div>
        <div class="clause-text">
            <strong>CREDORA:</strong> <span class="highlight">{{ $schoolLegal }}</span>, pessoa jurídica de direito privado, inscrita no CNPJ sob o nº <span class="highlight">{{ $schoolCnpj ?: '—' }}</span>, com sede em <span class="highlight">{{ $schoolAddress }}</span>, neste ato representada na forma de seus atos constitutivos; e de outro lado:
        </div>
        <div class="clause-text">
            <strong>DEVEDOR(A) / RESPONSÁVEL:</strong> <span class="highlight">{{ $respName }}</span>, inscrito(a) no CPF sob o nº <span class="highlight">{{ $respCpf }}</span>, portador(a) do RG nº <span class="highlight">{{ $respRg }}</span>, residente e domiciliado(a) em <span class="highlight">{{ $respAddress }}</span>, na qualidade de responsável legal e financeiro do(a) aluno(a) <span class="highlight">{{ $student?->name }}</span> (Matrícula: {{ $student?->ouro_moderno_cod_int ?: $student?->id }}).
        </div>

        <div class="clause-text" style="margin-top: 10px;">
            Têm entre si, justo e acordado, o presente Termo de Confissão de Dívida e Renegociação Financeira, que se regerá pelas seguintes cláusulas e condições:
        </div>

        <!-- CLÁUSULA 1 -->
        <div class="clause-title">Cláusula 1ª — Da Origem do Débito e Parcelas Renegociadas</div>
        <div class="clause-text">
            O(A) DEVEDOR(A) reconhece que inadimpliu com os pagamentos das mensalidades decorrentes do Contrato de Prestação de Serviços Educacionais nº <span class="highlight">#{{ $contract->contract_number }}</span>, totalizando o débito original bruto de <span class="highlight">R$ {{ number_format($debtAgreement->total_original_amount, 2, ',', '.') }}</span>, composto pelas seguintes parcelas:
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 80px;" class="text-center">Parcela Orig.</th>
                    <th class="text-center">Vencimento Original</th>
                    <th class="text-right">Valor Original</th>
                    <th class="text-center">Tipo de Cobrança</th>
                    <th class="text-center">Situação</th>
                </tr>
            </thead>
            <tbody>
                @foreach($originalInstallments as $origInst)
                    <tr>
                        <td class="text-center" style="font-weight: bold;">
                            {{ str_pad((string) $origInst->installment_number, 2, '0', STR_PAD_LEFT) }} / {{ str_pad((string) ($origInst->total_installments ?: 1), 2, '0', STR_PAD_LEFT) }}
                        </td>
                        <td class="text-center">{{ $origInst->due_date ? $origInst->due_date->format('d/m/Y') : '—' }}</td>
                        <td class="text-right">R$ {{ number_format($origInst->amount, 2, ',', '.') }}</td>
                        <td class="text-center">{{ $origInst->financialType?->name ?? 'Mensalidade' }}</td>
                        <td class="text-center" style="color: #991b1b; font-weight: bold;">Substituída por Acordo</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="2" style="text-align: right; text-transform: uppercase;">Total Bruto Renegociado:</td>
                    <td class="text-right">R$ {{ number_format($debtAgreement->total_original_amount, 2, ',', '.') }}</td>
                    <td colspan="2"></td>
                </tr>
            </tbody>
        </table>

        <!-- CLÁUSULA 2 -->
        <div class="clause-title">Cláusula 2ª — Da Confissão Irrevogável da Dívida</div>
        <div class="clause-text">
            O(A) DEVEDOR(A) confessa expressamente e de forma irrevogável e irretratável o débito acima discriminado, reconhecendo-o como dívida líquida, certa e exigível, constituindo o presente termo em <span class="highlight">Título Executivo Extrajudicial</span>, nos moldes do Artigo 784, Inciso III, do Código de Processo Civil brasileiro.
        </div>

        <!-- CLÁUSULA 3 -->
        <div class="clause-title">Cláusula 3ª — Das Condições do Acordo e Forma de Pagamento</div>
        <div class="clause-text">
            Por mera liberalidade da CREDORA e com a finalidade exclusiva de viabilizar a quitação e continuidade dos estudos do aluno, foi concedido um desconto promocional de <span class="highlight">R$ {{ number_format($debtAgreement->discount_amount, 2, ',', '.') }}</span>, fixando-se o valor total líquido acordado em <span class="highlight">R$ {{ number_format($debtAgreement->total_agreed_amount, 2, ',', '.') }}</span>, a ser quitado nas seguintes condições:
        </div>

        @if($debtAgreement->down_payment_amount > 0)
            <div class="clause-text" style="text-indent: 15px;">
                • <strong>Entrada:</strong> R$ {{ number_format($debtAgreement->down_payment_amount, 2, ',', '.') }}
                @if($debtAgreement->down_payment_paid)
                    <span style="color: #166534; font-weight: bold;">(QUITADA NO ATO DA ASSINATURA)</span>.
                @else
                    (a ser paga na data estipulada em carnê/recibo).
                @endif
            </div>
        @endif

        <div class="clause-text" style="text-indent: 15px;">
            • <strong>Saldo Restante:</strong> Parcelado em <span class="highlight">{{ $debtAgreement->installments_count }} parcela(s)</span> conforme cronograma abaixo:
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 80px;" class="text-center">Nova Parcela</th>
                    <th class="text-center">Novo Vencimento</th>
                    <th class="text-right">Valor da Parcela</th>
                    <th class="text-center">Tipo</th>
                    <th class="text-center">Situação Atual</th>
                </tr>
            </thead>
            <tbody>
                @foreach($newInstallments as $newInst)
                    <tr>
                        <td class="text-center" style="font-weight: bold;">
                            {{ str_pad((string) $newInst->installment_number, 2, '0', STR_PAD_LEFT) }} / {{ str_pad((string) ($newInst->total_installments ?: 1), 2, '0', STR_PAD_LEFT) }}
                        </td>
                        <td class="text-center">{{ $newInst->due_date ? $newInst->due_date->format('d/m/Y') : '—' }}</td>
                        <td class="text-right" style="font-weight: bold;">R$ {{ number_format($newInst->amount, 2, ',', '.') }}</td>
                        <td class="text-center">Parcela de Acordo</td>
                        <td class="text-center">
                            @if($newInst->status === 'paid')
                                <span style="color: #166534; font-weight: bold;">Pago</span>
                            @else
                                <span style="color: #b45309; font-weight: bold;">Pendente</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="2" style="text-align: right; text-transform: uppercase;">Total Acordado a Pagar:</td>
                    <td class="text-right">R$ {{ number_format($debtAgreement->total_agreed_amount, 2, ',', '.') }}</td>
                    <td colspan="2"></td>
                </tr>
            </tbody>
        </table>

        <!-- CLÁUSULA 4 -->
        <div class="clause-title">Cláusula 4ª — Do Inadimplemento e Vencimento Antecipado</div>
        <div class="clause-text">
            O atraso no pagamento de qualquer uma das parcelas estipuladas neste acordo implicará, de pleno direito e independentemente de qualquer aviso ou notificação, no <span class="highlight">vencimento antecipado de todas as parcelas vincendas</span>, perda automática e integral do desconto concedido na Cláusula 3ª, com incidência de multa moratória de 2% (dois por cento) e juros de 1% (um por cento) ao mês sobre o saldo remanescente, autorizando a imediata cobrança judicial e extrajudicial do débito.
        </div>

        <!-- CLÁUSULA 5 -->
        <div class="clause-title">Cláusula 5ª — Do Foro</div>
        <div class="clause-text">
            Para dirimir quaisquer dúvidas ou litígios decorrentes da execução do presente instrumento, as partes elegem o Foro da Comarca de <span class="highlight">{{ $schoolCity }} - {{ $schoolState }}</span>, renunciando a qualquer outro, por mais privilegiado que seja.
        </div>

        <div class="clause-text" style="margin-top: 15px;">
            E, por estarem justas e acordadas, as partes assinam o presente termo em 2 (duas) vias de igual teor e forma, na presença de 2 (duas) testemunhas.
        </div>

        <!-- DATA -->
        <div class="date-location">
            {{ $dataExtenso }}
        </div>

        <!-- ASSINATURAS PRINCIPAIS -->
        <div class="signatures-grid">
            <div class="sign-col">
                <div class="sign-line">{{ $respName }}</div>
                <div class="sign-sub">DEVEDOR(A) / RESPONSÁVEL FINANCEIRO<br>CPF: {{ $respCpf }}</div>
            </div>
            <div class="sign-col">
                <div class="sign-line">{{ $schoolRepresentative ?: ($schoolTrade) }}</div>
                <div class="sign-sub">CREDORA / DIREÇÃO ESCOLAR<br>CNPJ: {{ $schoolCnpj }}</div>
            </div>
        </div>

        <!-- TESTEMUNHAS -->
        <div style="font-family: Arial, sans-serif; font-size: 8pt; font-weight: bold; color: #475569; text-transform: uppercase; margin-bottom: 5px;">
            Testemunhas Instrumentárias:
        </div>
        <div class="witness-grid">
            <div class="sign-col">
                <div class="sign-line">Nome: _________________________________________</div>
                <div class="sign-sub">CPF: ___________________</div>
            </div>
            <div class="sign-col">
                <div class="sign-line">Nome: _________________________________________</div>
                <div class="sign-sub">CPF: ___________________</div>
            </div>
        </div>

        <div class="doc-footer">
            <div>Termo de Acordo Extrajudicial nº {{ $debtAgreement->agreement_number }} · Contrato #{{ $contract->contract_number }}</div>
            <div>Emitido via Aurosoft Gestão Financeira Escolar</div>
            <div>Data: {{ now()->format('d/m/Y H:i') }}</div>
        </div>
    </div>

</body>
</html>

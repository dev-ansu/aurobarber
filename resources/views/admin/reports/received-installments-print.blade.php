<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Contas Recebidas & ISS - {{ $school?->trade_name ?? $school?->legal_name ?? 'Aurosoft' }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm 12mm;
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
            font-size: 10px;
            margin: 0;
            padding: 0;
            background: #fff;
        }

        .no-print-bar {
            background: #1e1b4b;
            color: #fff;
            padding: 10px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            margin-bottom: 15px;
        }

        .btn-print {
            background: #4f46e5;
            color: #fff;
            border: none;
            padding: 6px 14px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
        }

        .header {
            border-bottom: 2px solid #111827;
            padding-bottom: 8px;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .header h1 {
            font-size: 16px;
            margin: 0 0 2px 0;
            text-transform: uppercase;
            font-weight: 900;
            color: #111827;
        }

        .header p {
            margin: 0;
            font-size: 10px;
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
            margin-bottom: 14px;
        }

        .summary-card {
            border: 1px solid #e5e7eb;
            background: #f9fafb;
            padding: 8px 10px;
            border-radius: 6px;
        }

        .summary-card .label {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            color: #6b7280;
            margin-bottom: 3px;
        }

        .summary-card .value {
            font-size: 14px;
            font-weight: 800;
            font-family: monospace;
            color: #111827;
        }

        .table-data {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5px;
        }

        .table-data th {
            background: #f3f4f6;
            color: #111827;
            font-weight: 700;
            text-align: left;
            padding: 5px 6px;
            border-bottom: 1px solid #d1d5db;
            text-transform: uppercase;
            font-size: 8.5px;
        }

        .table-data td {
            padding: 5px 6px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: middle;
        }

        .table-data tr:nth-child(even) {
            background-color: #fafafa;
        }

        .badge {
            display: inline-block;
            padding: 1px 5px;
            border-radius: 4px;
            font-size: 8.5px;
            font-weight: 600;
        }

        .badge-matricula {
            background: #e0f2fe;
            color: #0369a1;
        }

        .badge-mensalidade {
            background: #dcfce7;
            color: #15803d;
        }

        .footer {
            margin-top: 15px;
            border-top: 1px solid #e5e7eb;
            padding-top: 8px;
            display: flex;
            justify-content: space-between;
            font-size: 9px;
            color: #6b7280;
        }

        @media print {
            .no-print-bar {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="no-print-bar">
        <span>Relatório de Contas Recebidas & Matrículas para Emissão Fiscal (ISS Fortaleza)</span>
        <button class="btn-print" onclick="window.print()">Imprimir / Salvar PDF</button>
    </div>

    <div class="header">
        <div>
            <h1>{{ $school?->trade_name ?? $school?->legal_name ?? 'Aurosoft LMS' }}</h1>
            <p>Relatório de Contas Recebidas & Gestão de Matrículas (Conferência de Notas Fiscais - ISS)</p>
            <p>Período de Baixa: <strong>{{ $startDate->format('d/m/Y') }}</strong> a <strong>{{ $endDate->format('d/m/Y') }}</strong></p>
        </div>
        <div class="header-meta">
            <div>CNPJ: {{ $school?->cnpj ?? 'Não informado' }}</div>
            <div>Emitido em: {{ $generatedAt }}</div>
            <div>Total de Registros: {{ $records->count() }}</div>
        </div>
    </div>

    <div class="summary-grid">
        <div class="summary-card">
            <div class="label">Total Geral Recebido</div>
            <div class="value" style="color: #047857;">R$ {{ number_format($totalRecebido, 2, ',', '.') }}</div>
        </div>
        <div class="summary-card">
            <div class="label">Matrículas & Entradas</div>
            <div class="value" style="color: #0284c7;">R$ {{ number_format($totalMatriculas, 2, ',', '.') }}</div>
        </div>
        <div class="summary-card">
            <div class="label">Mensalidades Cursos</div>
            <div class="value" style="color: #4338ca;">R$ {{ number_format($totalMensalidades, 2, ',', '.') }}</div>
        </div>
        <div class="summary-card">
            <div class="label">Alunos Faturados</div>
            <div class="value">{{ $alunosUnicos }}</div>
        </div>
    </div>

    <table class="table-data">
        <thead>
            <tr>
                <th style="width: 70px;">Contrato</th>
                <th>Aluno (Beneficiário)</th>
                <th style="width: 100px;">CPF Aluno</th>
                <th>Responsável / Tomador Fiscal (ISS)</th>
                <th style="width: 105px;">CPF Tomador</th>
                <th>Tipo Cobrança</th>
                <th style="width: 55px;">Parcela</th>
                <th style="width: 75px;">Data Baixa</th>
                <th style="width: 80px;">Forma Pagto</th>
                <th style="text-align: right; width: 85px;">Valor Pago</th>
            </tr>
        </thead>
        <tbody>
            @forelse($records as $rec)
                @php
                    $student = $rec->contract?->student;
                    $resp = $rec->contract?->responsible?->person;
                    $tomador = $resp ?: $student;
                    $tipoSlug = $rec->financialType?->slug ?? '';
                    $badgeClass = str_contains($tipoSlug, 'matricula') || str_contains($tipoSlug, 'entrada') ? 'badge-matricula' : 'badge-mensalidade';
                @endphp
                <tr>
                    <td style="font-weight: bold; font-family: monospace;">{{ $rec->contract?->contract_number ?? '-' }}</td>
                    <td style="font-weight: 600;">{{ $student?->name ?? 'Aluno' }}</td>
                    <td style="font-family: monospace;">{{ \App\Filament\Pages\ReceivedInstallmentsReport::formatCpf($student?->cpf) ?: '—' }}</td>
                    <td>
                        {{ $resp ? $resp->name : 'O Próprio Aluno' }}
                        @if($rec->contract?->responsible?->relationship)
                            <span style="font-size: 8px; color: #6b7280;">({{ $rec->contract->responsible->relationship->getLabel() }})</span>
                        @endif
                    </td>
                    <td style="font-family: monospace; font-weight: bold;">{{ \App\Filament\Pages\ReceivedInstallmentsReport::formatCpf($tomador?->cpf) ?: '⚠️ SEM CPF' }}</td>
                    <td>
                        <span class="badge {{ $badgeClass }}">{{ $rec->financialType?->name ?? 'Serviço' }}</span>
                    </td>
                    <td>{{ $rec->installment_number ? "{$rec->installment_number}/{$rec->total_installments}" : 'Entrada' }}</td>
                    <td>{{ $rec->paid_at ? $rec->paid_at->format('d/m/Y') : ($rec->updated_at ? $rec->updated_at->format('d/m/Y') : '—') }}</td>
                    <td>{{ $rec->paymentMethod?->name ?? 'Outro' }}</td>
                    <td style="text-align: right; font-weight: bold; font-family: monospace; color: #047857;">
                        R$ {{ number_format((float) ($rec->amount_paid ?? $rec->amount), 2, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" style="text-align: center; padding: 20px; color: #6b7280;">
                        Nenhum recebimento registrado para o período informado.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <div>Documento de conferência financeira gerado pelo Sistema Aurosoft LMS</div>
        <div>Total de receitas declaradas: R$ {{ number_format($totalRecebido, 2, ',', '.') }}</div>
    </div>
</body>
</html>


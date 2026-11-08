<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Caixa - {{ $escola?->trade_name ?? $escola?->legal_name ?? 'Escola' }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #333; line-height: 1.4; font-size: 12px; margin: 20px; }
        .header { text-align: center; margin-bottom: 25px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 20px; text-transform: uppercase; letter-spacing: 1px; }
        .header p { margin: 5px 0 0 0; color: #666; font-size: 13px; }
        .section-title { font-size: 14px; font-weight: bold; text-transform: uppercase; background: #f2f2f2; padding: 5px 10px; margin-top: 25px; margin-bottom: 10px; border-left: 4px solid #333; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        table, th, td { border: 1px solid #ddd; }
        th { background-color: #f9f9f9; padding: 8px; text-align: left; font-weight: bold; font-size: 11px; text-transform: uppercase; }
        td { padding: 8px; vertical-align: top; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        .badge { padding: 2px 6px; border-radius: 3px; font-size: 10px; font-weight: bold; text-transform: uppercase; border: 1px solid #ccc; }
        .grid { display: grid; grid-template-columns: repeat(2, 1px); gap: 20px; }
        .col { flex: 1; }
        .footer-signatures { margin-top: 60px; display: flex; justify-content: space-between; }
        .signature-block { text-align: center; width: 45%; border-top: 1px solid #333; padding-top: 8px; font-size: 11px; }
        
        /* Estilos específicos para a folha impressa física */
        @media print {
            body { margin: 10mm; font-size: 11px; }
            .no-print { display: none; }
            tr { page-break-inside: avoid; }
        }
    </style>
</head>
<body onload="window.print();">

    <div class="header">
        <h1>Relatório de Prestação de Contas - Caixa Diário</h1>
        <p>{{ $escola?->trade_name ?? $escola?->legal_name ?? 'Aurosoft Educacional' }}
            @if ($escola?->cnpj) &bull; CNPJ: {{ $escola->cnpj }} @endif
            &bull; Relatório emitido pelo Aurosoft em {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <div class="section-title">Resumo Operacional do Caixa</div>
    <table>
        <tr>
            <td class="bold" width="25%">Identificação do Caixa:</td>
            <td width="25%">{{ $caixa->name }}</td>
            <td class="bold" width="25%">Status do Turno:</td>
            <td>{{ $caixa->status === 'open' ? 'ABERTO' : 'FECHADO' }}</td>
        </tr>
        <tr>
            <td class="bold">Operador Responsável:</td>
            <td>{{ $caixa->operator?->name ?? 'Não identificado' }}</td>
            <td class="bold">Abertura do Turno:</td>
            <td>{{ $caixa->opened_at?->format('d/m/Y H:i') ?? '-' }}</td>
        </tr>
        <tr>
            <td class="bold">Saldo Inicial (Troco):</td>
            <td class="bold">R$ {{ number_format($caixa->opening_balance, 2, ',', '.') }}</td>
            <td class="bold">Fechamento do Turno:</td>
            <td>{{ $caixa->closed_at?->format('d/m/Y H:i') ?? 'Em andamento' }}</td>
        </tr>
    </table>

    <div style="display: flex; gap: 20px;">
        <div style="flex: 1;">
            <div class="section-title">Entradas por Meio de Pagamento</div>
            <table>
                <thead>
                    <tr>
                        <th>Meio de Recebimento</th>
                        <th class="text-right">Total Acumulado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($resumoMeios as $meio)
                        <tr>
                            <td>
                                {{ $meio->payment_method ?? '—' }}
                            </td>
                            <td class="text-right bold">R$ {{ number_format($meio->total, 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" style="color: #999;">Nenhum recebimento registrado neste turno.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div style="flex: 1;">
            <div class="section-title">Consolidação e Conferência de Gaveta</div>
            <table>
                <tr>
                    <td>(+) Saldo Inicial (Troco de Abertura):</td>
                    <td class="text-right">R$ {{ number_format($caixa->opening_balance, 2, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>(+) Total de Entradas (Recebimentos/Suprimentos):</td>
                    <td class="text-right" style="color: green;">R$ {{ number_format($caixa->transactions()->where('type', 'input')->sum('amount'), 2, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>(-) Total de Saídas (Sangrias/Despesas):</td>
                    <td class="text-right" style="color: red;">R$ {{ number_format($caixa->transactions()->where('type', 'output')->sum('amount'), 2, ',', '.') }}</td>
                </tr>
                <tr style="background: #f9f9f9;">
                    <td class="bold">(=) Saldo Esperado em Dinheiro na Gaveta:</td>
                    <td class="text-right bold">R$ {{ number_format($caixa->expectedCashBalance(), 2, ',', '.') }}</td>
                </tr>
                @if ($caixa->status === 'closed')
                    <tr>
                        <td class="bold">Dinheiro Físico Contado na Gaveta:</td>
                        <td class="text-right bold">R$ {{ number_format($caixa->counted_cash ?? 0, 2, ',', '.') }}</td>
                    </tr>
                    <tr style="background: {{ ($caixa->difference ?? 0) < 0 ? '#fee2e2' : (($caixa->difference ?? 0) > 0 ? '#eff6ff' : '#f0fdf4') }};">
                        <td class="bold">Resultado da Conferência:</td>
                        <td class="text-right bold">
                            @if (($caixa->difference ?? 0) == 0.0)
                                <span style="color: green;">Exato (Sem Quebra)</span>
                            @elseif (($caixa->difference ?? 0) > 0)
                                <span style="color: #2563eb;">+ R$ {{ number_format($caixa->difference, 2, ',', '.') }} (Sobra)</span>
                            @else
                                <span style="color: #dc2626;">- R$ {{ number_format(abs($caixa->difference), 2, ',', '.') }} (Quebra)</span>
                            @endif
                        </td>
                    </tr>
                    @if ($caixa->closing_notes)
                        <tr>
                            <td colspan="2" style="font-size: 11px; color: #555;">
                                <strong>Obs. do Fechamento:</strong> {{ $caixa->closing_notes }}
                            </td>
                        </tr>
                    @endif
                @endif
                <tr style="background: #f1f5f9; font-size: 13px;">
                    <td class="bold">(=) Saldo Total Acumulado do Turno:</td>
                    <td class="text-right bold" style="text-decoration: underline;">R$ {{ number_format($caixa->current_balance, 2, ',', '.') }}</td>
                </tr>
            </table>
        </div>
    </div>

    <div class="section-title">Extrato Detalhado de Movimentações</div>
    <table>
        <thead>
            <tr>
                <th width="12%">Horário</th>
                <th width="10%">Operação</th>
                <th width="15%">Meio</th>
                <th width="48%">Descrição do Lançamento</th>
                <th width="15%" class="text-right">Valor</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transacoes as $transacao)
                <tr>
                    <td>{{ $transacao->created_at->format('H:i:s') }}</td>
                    <td class="bold" style="color: {{ $transacao->type === 'input' ? 'green' : 'red' }}">
                        {{ $transacao->type === 'input' ? 'ENTRADA' : 'SAÍDA' }}
                    </td>
                    <td>
                        {{ $transacao->paymentMethod?->name ?? 'Dinheiro' }}
                    </td>
                    <td>{{ $transacao->description }}</td>
                    <td class="text-right bold">R$ {{ number_format($transacao->amount, 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; color: #999; padding: 20px;">Nenhuma movimentação financeira realizada neste período.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer-signatures">
        <div class="signature-block">
            Assinatura do Operador de Caixa<br>
            <span class="bold">{{ $caixa->operator?->name }}</span>
        </div>
        <div class="signature-block">
            Assinatura do Conferente / Gestor<br>
            <span>Data: ____/____/_______</span>
        </div>
    </div>

</body>
</html>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Resumo Semanal da Diretoria</title>
</head>
<body style="margin: 0; padding: 0; background-color: #0b1120; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #e2e8f0;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #0b1120; padding: 30px 15px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" style="max-width: 650px; background-color: #0f172a; border: 1px solid #1e293b; border-radius: 20px; overflow: hidden; box-shadow: 0 15px 35px rgba(0, 0, 0, 0.6);">
                    
                    {{-- CABEÇALHO EXECUTIVO --}}
                    <tr>
                        <td style="padding: 32px; background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); border-bottom: 1px solid #334155;">
                            <table role="presentation" width="100%">
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                                            <span style="display: inline-block; background: linear-gradient(135deg, #f59e0b, #d97706); color: #020617; font-weight: 900; font-size: 16px; padding: 6px 12px; border-radius: 8px;">A</span>
                                            <span style="color: #94a3b8; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">Aurosoft Intelligence</span>
                                        </div>
                                        <h1 style="margin: 0; color: #ffffff; font-size: 22px; font-weight: 800; letter-spacing: -0.5px;">
                                            {{ $school->trade_name ?: $school->legal_name ?: 'Aurosoft Academy' }}
                                        </h1>
                                        <p style="margin: 4px 0 0 0; color: #f59e0b; font-size: 13px; font-weight: 600;">
                                            Boletim Executivo Semanal • {{ $metrics['period_label'] ?? 'Últimos 7 dias' }}
                                        </p>
                                    </td>
                                    <td align="right" style="vertical-align: top;">
                                        <span style="background-color: rgba(56, 189, 248, 0.15); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.3); font-size: 11px; font-weight: 700; padding: 5px 12px; border-radius: 20px; white-space: nowrap;">
                                            DIRETORIA
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- CORPO PRINCIPAL --}}
                    <tr>
                        <td style="padding: 32px;">
                            <p style="margin: 0 0 24px 0; color: #cbd5e1; font-size: 15px; line-height: 1.6;">
                                Olá, <strong>{{ $recipientName }}</strong>! Aqui está a visão consolidada de performance da sua instituição nos últimos 7 dias:
                            </p>

                            {{-- GRID DE 4 KPIS ESTRATÉGICOS --}}
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: 28px;">
                                <tr>
                                    {{-- KPI 1: FATURAMENTO RECEBIDO --}}
                                    <td width="48%" style="background-color: #1e293b; border: 1px solid #334155; border-radius: 14px; padding: 18px; vertical-align: top;">
                                        <div style="color: #10b981; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">
                                            💰 Receita Entrante no Caixa
                                        </div>
                                        <div style="color: #ffffff; font-size: 22px; font-weight: 900; letter-spacing: -0.5px;">
                                            R$ {{ number_format((float)($metrics['total_received'] ?? 0), 2, ',', '.') }}
                                        </div>
                                        <div style="color: #94a3b8; font-size: 11px; margin-top: 4px;">
                                            {{ $metrics['received_count'] ?? 0 }} parcelas e taxas quitadas
                                        </div>
                                    </td>

                                    <td width="4%"></td>

                                    {{-- KPI 2: NOVAS MATRÍCULAS --}}
                                    <td width="48%" style="background-color: #1e293b; border: 1px solid #334155; border-radius: 14px; padding: 18px; vertical-align: top;">
                                        <div style="color: #f59e0b; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">
                                            🎓 Novas Matrículas Fechadas
                                        </div>
                                        <div style="color: #ffffff; font-size: 22px; font-weight: 900; letter-spacing: -0.5px;">
                                            {{ $metrics['new_contracts_count'] ?? 0 }} alunos
                                        </div>
                                        <div style="color: #94a3b8; font-size: 11px; margin-top: 4px;">
                                            VGV total: R$ {{ number_format((float)($metrics['new_contracts_value'] ?? 0), 2, ',', '.') }}
                                        </div>
                                    </td>
                                </tr>
                                <tr><td height="12"></td></tr>
                                <tr>
                                    {{-- KPI 3: PREVISTO A RECEBER NA PRÓXIMA SEMANA --}}
                                    <td width="48%" style="background-color: #1e293b; border: 1px solid #334155; border-radius: 14px; padding: 18px; vertical-align: top;">
                                        <div style="color: #38bdf8; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">
                                            📅 A Receber na Próxima Semana
                                        </div>
                                        <div style="color: #ffffff; font-size: 22px; font-weight: 900; letter-spacing: -0.5px;">
                                            R$ {{ number_format((float)($metrics['upcoming_due_value'] ?? 0), 2, ',', '.') }}
                                        </div>
                                        <div style="color: #94a3b8; font-size: 11px; margin-top: 4px;">
                                            {{ $metrics['upcoming_due_count'] ?? 0 }} parcelas com vencimento
                                        </div>
                                    </td>

                                    <td width="4%"></td>

                                    {{-- KPI 4: RETORNOS DE TRANCAMENTO --}}
                                    <td width="48%" style="background-color: #1e293b; border: 1px solid #334155; border-radius: 14px; padding: 18px; vertical-align: top;">
                                        <div style="color: #ec4899; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">
                                            ⏸️ Retornos de Trancamentos
                                        </div>
                                        <div style="color: #ffffff; font-size: 22px; font-weight: 900; letter-spacing: -0.5px;">
                                            {{ $metrics['pending_suspensions_count'] ?? 0 }} alunos
                                        </div>
                                        <div style="color: #94a3b8; font-size: 11px; margin-top: 4px;">
                                            Com data de retorno prevista
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            {{-- SEÇÃO: CURSOS MAIS VENDIDOS --}}
                            @if(!empty($metrics['top_courses']))
                            <div style="margin-bottom: 28px;">
                                <h3 style="margin: 0 0 12px 0; color: #ffffff; font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                                    🏆 Cursos Destaque da Semana
                                </h3>
                                <table role="presentation" width="100%" style="background-color: #1e293b; border: 1px solid #334155; border-radius: 12px; border-collapse: collapse;">
                                    @foreach($metrics['top_courses'] as $course)
                                    <tr>
                                        <td style="padding: 12px 16px; border-bottom: 1px solid #334155; color: #e2e8f0; font-size: 13px; font-weight: 600;">
                                            {{ $course['name'] }}
                                        </td>
                                        <td align="right" style="padding: 12px 16px; border-bottom: 1px solid #334155; color: #f59e0b; font-size: 13px; font-weight: 800;">
                                            {{ $course['count'] }} matrícula(s)
                                        </td>
                                    </tr>
                                    @endforeach
                                </table>
                            </div>
                            @endif

                            {{-- ALERTA DE AÇÃO PARA A EQUIPE COMERCIAL / RECEPÇÃO --}}
                            <div style="background-color: #020617; border-left: 4px solid #38bdf8; border-radius: 8px; padding: 16px 20px; margin-bottom: 30px;">
                                <h4 style="margin: 0 0 6px 0; color: #38bdf8; font-size: 13px; font-weight: 800; text-transform: uppercase;">
                                    💡 Foco Recomendado para Segunda-Feira:
                                </h4>
                                <p style="margin: 0; color: #cbd5e1; font-size: 13px; line-height: 1.5;">
                                    Sua equipe tem <strong>{{ $metrics['upcoming_due_count'] ?? 0 }} parcelas</strong> vencendo nos próximos 7 dias (totalizando R$ {{ number_format((float)($metrics['upcoming_due_value'] ?? 0), 2, ',', '.') }}). Lembre a recepção de reforçar os envios do código Pix dinâmico pelo WhatsApp com 2 dias de antecedência.
                                </p>
                            </div>

                            {{-- BOTÃO DE ACESSO AO PAINEL --}}
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td align="center">
                                        <a href="{{ $metrics['panel_url'] ?? url('/') }}"
                                           target="_blank"
                                           style="display: inline-block; background: linear-gradient(135deg, #f59e0b, #d97706); color: #020617; font-weight: 800; font-size: 14px; padding: 14px 32px; border-radius: 12px; text-decoration: none; box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);">
                                            Acessar Painel da Escola ➔
                                        </a>
                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                    {{-- RODAPÉ --}}
                    <tr>
                        <td style="padding: 24px 32px; background-color: #020617; border-top: 1px solid #1e293b; text-align: center; color: #64748b; font-size: 12px; line-height: 1.5;">
                            Este resumo foi gerado automaticamente pelo motor inteligente do Aurosoft.<br>
                            © {{ date('Y') }} Aurosoft • Gestão Escolar & LMS Cloud
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>

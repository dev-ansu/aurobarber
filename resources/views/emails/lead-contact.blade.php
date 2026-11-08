<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Novo Lead Aurosoft</title>
</head>
<body style="margin: 0; padding: 0; background-color: #0b1120; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #e2e8f0;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #0b1120; padding: 30px 15px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" style="max-width: 600px; background-color: #0f172a; border: 1px solid #1e293b; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);">
                    
                    {{-- HEADER --}}
                    <tr>
                        <td style="padding: 28px 32px; background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); border-bottom: 1px solid #334155;">
                            <table role="presentation" width="100%">
                                <tr>
                                    <td>
                                        <span style="display: inline-block; background-color: #f59e0b; color: #020617; font-weight: 900; font-size: 18px; padding: 6px 12px; border-radius: 8px; margin-bottom: 8px;">A</span>
                                        <h1 style="margin: 0; color: #ffffff; font-size: 20px; font-weight: 800; letter-spacing: -0.5px;">Aurosoft Cloud</h1>
                                        <p style="margin: 4px 0 0 0; color: #f59e0b; font-size: 13px; font-weight: 600;">Nova Solicitação de Demonstração / Contato</p>
                                    </td>
                                    <td align="right" style="vertical-align: top;">
                                        <span style="background-color: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 20px;">NOVO LEAD</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- CORPO --}}
                    <tr>
                        <td style="padding: 32px;">
                            <p style="margin: 0 0 20px 0; color: #cbd5e1; font-size: 15px; line-height: 1.5;">
                                Uma nova escola solicitou contato através da vitrine oficial <strong>aurosoft.com.br</strong>:
                            </p>

                            {{-- DADOS DO LEAD --}}
                            <table role="presentation" width="100%" style="background-color: #1e293b; border-radius: 12px; padding: 18px; margin-bottom: 24px; border: 1px solid #334155;">
                                <tr>
                                    <td style="padding: 6px 0; color: #94a3b8; font-size: 13px; width: 38%;">Responsável:</td>
                                    <td style="padding: 6px 0; color: #ffffff; font-size: 14px; font-weight: 700;">{{ $lead['name'] }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 6px 0; color: #94a3b8; font-size: 13px;">Instituição / Escola:</td>
                                    <td style="padding: 6px 0; color: #f59e0b; font-size: 14px; font-weight: 700;">{{ $lead['school_name'] }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 6px 0; color: #94a3b8; font-size: 13px;">E-mail:</td>
                                    <td style="padding: 6px 0; color: #38bdf8; font-size: 14px; font-weight: 600;">
                                        <a href="mailto:{{ $lead['email'] }}" style="color: #38bdf8; text-decoration: none;">{{ $lead['email'] }}</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 6px 0; color: #94a3b8; font-size: 13px;">WhatsApp / Telefone:</td>
                                    <td style="padding: 6px 0; color: #10b981; font-size: 14px; font-weight: 700;">{{ $lead['phone'] }}</td>
                                </tr>
                                @if(!empty($lead['city']))
                                <tr>
                                    <td style="padding: 6px 0; color: #94a3b8; font-size: 13px;">Cidade / UF:</td>
                                    <td style="padding: 6px 0; color: #ffffff; font-size: 14px;">{{ $lead['city'] }}</td>
                                </tr>
                                @endif
                                @if(!empty($lead['students_count']))
                                <tr>
                                    <td style="padding: 6px 0; color: #94a3b8; font-size: 13px;">Porte / Alunos:</td>
                                    <td style="padding: 6px 0; color: #ffffff; font-size: 14px;">{{ $lead['students_count'] }}</td>
                                </tr>
                                @endif
                            </table>

                            @if(!empty($lead['message']))
                            <div style="background-color: #020617; border-left: 3px solid #f59e0b; padding: 14px 16px; border-radius: 8px; margin-bottom: 24px;">
                                <p style="margin: 0 0 6px 0; color: #94a3b8; font-size: 12px; font-weight: 700; text-transform: uppercase;">Mensagem do Cliente:</p>
                                <p style="margin: 0; color: #f1f5f9; font-size: 14px; line-height: 1.5; font-style: italic;">"{{ $lead['message'] }}"</p>
                            </div>
                            @endif

                            {{-- BOTÃO DE AÇÃO DIRETA NO WHATSAPP DO LEAD --}}
                            @php
                                $cleanPhone = preg_replace('/[^0-9]/', '', (string)$lead['phone']);
                                if (strlen($cleanPhone) <= 11) {
                                    $cleanPhone = '55' . $cleanPhone;
                                }
                                $greeting = urlencode("Olá {$lead['name']}! Sou o Anderson da Aurosoft. Recebi seu contato sobre a {$lead['school_name']}. Como posso ajudar?");
                            @endphp

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td align="center">
                                        <a href="https://wa.me/{{ $cleanPhone }}?text={{ $greeting }}"
                                           target="_blank"
                                           style="display: inline-block; background-color: #10b981; color: #020617; font-weight: 800; font-size: 14px; padding: 14px 28px; border-radius: 12px; text-decoration: none; box-shadow: 0 4px 14px rgba(16, 185, 129, 0.4);">
                                            💬 Chamar no WhatsApp com 1 Clique
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- FOOTER --}}
                    <tr>
                        <td style="padding: 20px 32px; background-color: #020617; border-top: 1px solid #1e293b; text-align: center; color: #64748b; font-size: 12px;">
                            IP do visitante: {{ $lead['ip'] ?? 'Não registrado' }} • Enviado automaticamente por aurosoft.com.br
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>

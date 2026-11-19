@php
    $leadData = is_array($lead) ? $lead : $lead->toArray();
    $name = $leadData['name'] ?? 'Não informado';
    $barbershopName = $leadData['barbershop_name'] ?? 'Não informada';
    $email = $leadData['email'] ?? 'Não informado';
    $phone = $leadData['phone'] ?? 'Não informado';
    $city = $leadData['city'] ?? null;
    $chairsCount = $leadData['chairs_count'] ?? null;
    $selectedPlan = $leadData['selected_plan'] ?? null;
    $message = $leadData['message'] ?? null;
    $ip = $leadData['ip'] ?? 'Não registrado';

    $cleanPhone = preg_replace('/[^0-9]/', '', (string)$phone);
    if (strlen($cleanPhone) <= 11 && !str_starts_with($cleanPhone, '55')) {
        $cleanPhone = '55' . $cleanPhone;
    }
    $greeting = urlencode("Olá {$name}! Recebi seu cadastro no site do AuroBarber para a barbearia {$barbershopName}. Como posso ajudar?");
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Novo Lead AuroBarber</title>
</head>
<body style="margin: 0; padding: 0; background-color: #020617; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #e2e8f0;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #020617; padding: 30px 15px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" style="max-width: 600px; background-color: #0f172a; border: 1px solid #1e293b; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.6);">

                    {{-- HEADER --}}
                    <tr>
                        <td style="padding: 28px 32px; background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); border-bottom: 1px solid #334155;">
                            <table role="presentation" width="100%">
                                <tr>
                                    <td>
                                        <div style="display: inline-block; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: #020617; font-weight: 900; font-size: 18px; padding: 6px 12px; border-radius: 8px; margin-bottom: 8px;">💈</div>
                                        <h1 style="margin: 0; color: #ffffff; font-size: 20px; font-weight: 800; letter-spacing: -0.5px;">AuroBarber Cloud</h1>
                                        <p style="margin: 4px 0 0 0; color: #fbbf24; font-size: 13px; font-weight: 600;">Novo Lead Comercial da Landing Page</p>
                                    </td>
                                    <td align="right" style="vertical-align: top;">
                                        <span style="background-color: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 20px;">NOVO LEAD</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- CORPO --}}
                    <tr>
                        <td style="padding: 32px;">
                            <p style="margin: 0 0 20px 0; color: #cbd5e1; font-size: 15px; line-height: 1.5;">
                                Um novo responsável de barbearia solicitou demonstração através da landing page oficial do <strong>AuroBarber</strong>:
                            </p>

                            {{-- DADOS DO LEAD --}}
                            <table role="presentation" width="100%" style="background-color: #1e293b; border-radius: 12px; padding: 18px; margin-bottom: 24px; border: 1px solid #334155;">
                                <tr>
                                    <td style="padding: 6px 0; color: #94a3b8; font-size: 13px; width: 40%;">Responsável:</td>
                                    <td style="padding: 6px 0; color: #ffffff; font-size: 14px; font-weight: 700;">{{ $name }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 6px 0; color: #94a3b8; font-size: 13px;">Barbearia:</td>
                                    <td style="padding: 6px 0; color: #fbbf24; font-size: 14px; font-weight: 700;">{{ $barbershopName }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 6px 0; color: #94a3b8; font-size: 13px;">E-mail:</td>
                                    <td style="padding: 6px 0; color: #38bdf8; font-size: 14px; font-weight: 600;">
                                        <a href="mailto:{{ $email }}" style="color: #38bdf8; text-decoration: none;">{{ $email }}</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 6px 0; color: #94a3b8; font-size: 13px;">WhatsApp / Telefone:</td>
                                    <td style="padding: 6px 0; color: #10b981; font-size: 14px; font-weight: 700;">{{ $phone }}</td>
                                </tr>
                                @if(!empty($city))
                                <tr>
                                    <td style="padding: 6px 0; color: #94a3b8; font-size: 13px;">Cidade / UF:</td>
                                    <td style="padding: 6px 0; color: #ffffff; font-size: 14px;">{{ $city }}</td>
                                </tr>
                                @endif
                                @if(!empty($chairsCount))
                                <tr>
                                    <td style="padding: 6px 0; color: #94a3b8; font-size: 13px;">Barbeiros / Cadeiras:</td>
                                    <td style="padding: 6px 0; color: #ffffff; font-size: 14px;">{{ $chairsCount }}</td>
                                </tr>
                                @endif
                                @if(!empty($selectedPlan))
                                <tr>
                                    <td style="padding: 6px 0; color: #94a3b8; font-size: 13px;">Plano Pretendido:</td>
                                    <td style="padding: 6px 0; color: #f59e0b; font-size: 14px; font-weight: 700;">{{ $selectedPlan }}</td>
                                </tr>
                                @endif
                            </table>

                            @if(!empty($message))
                            <div style="background-color: #020617; border-left: 3px solid #f59e0b; padding: 14px 16px; border-radius: 8px; margin-bottom: 24px;">
                                <p style="margin: 0 0 6px 0; color: #94a3b8; font-size: 12px; font-weight: 700; text-transform: uppercase;">Mensagem / Observação:</p>
                                <p style="margin: 0; color: #f1f5f9; font-size: 14px; line-height: 1.5; font-style: italic;">"{{ $message }}"</p>
                            </div>
                            @endif

                            {{-- BOTÃO DE AÇÃO DIRETA NO WHATSAPP DO LEAD --}}
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
                            IP do visitante: {{ $ip }} &bull; Enviado automaticamente pelo AuroBarber Cloud
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>

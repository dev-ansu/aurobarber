<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Código de Segurança • AuroBarber</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #0f172a;
            color: #f8fafc;
            margin: 0;
            padding: 40px 20px;
        }
        .container {
            max-width: 560px;
            margin: 0 auto;
            background-color: #1e293b;
            border: 1px solid #334155;
            border-radius: 16px;
            padding: 36px 32px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.4);
        }
        .header {
            text-align: center;
            border-bottom: 1px solid #334155;
            padding-bottom: 24px;
            margin-bottom: 24px;
        }
        .logo-badge {
            display: inline-block;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: #0f172a;
            font-weight: 900;
            font-size: 20px;
            width: 44px;
            height: 44px;
            line-height: 44px;
            text-align: center;
            border-radius: 12px;
            margin-bottom: 8px;
        }
        .logo-text {
            font-size: 20px;
            font-weight: 800;
            color: #ffffff;
            margin: 0;
            letter-spacing: -0.5px;
        }
        .logo-sub {
            font-size: 11px;
            color: #f59e0b;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 2px;
            display: block;
        }
        h1 {
            font-size: 20px;
            font-weight: 700;
            color: #ffffff;
            margin-top: 0;
            margin-bottom: 12px;
            text-align: center;
        }
        p {
            font-size: 14px;
            line-height: 1.6;
            color: #cbd5e1;
            margin: 0 0 16px 0;
        }
        .code-wrapper {
            text-align: center;
            margin: 28px 0;
        }
        .code-box {
            display: inline-block;
            background-color: #0f172a;
            border: 2px solid #f59e0b;
            border-radius: 12px;
            padding: 16px 32px;
            font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
            font-size: 34px;
            font-weight: 800;
            color: #fbbf24;
            letter-spacing: 10px;
            box-shadow: 0 0 20px rgba(245, 158, 11, 0.2);
        }
        .meta-card {
            background-color: #0f172a;
            border: 1px solid #334155;
            border-radius: 10px;
            padding: 16px;
            margin: 24px 0 16px 0;
            font-size: 12px;
            color: #94a3b8;
        }
        .meta-row {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            border-bottom: 1px dashed #1e293b;
        }
        .meta-row:last-child {
            border-bottom: none;
        }
        .meta-label {
            color: #64748b;
            font-weight: 600;
        }
        .meta-value {
            color: #cbd5e1;
            font-weight: 500;
            text-align: right;
            word-break: break-all;
        }
        .alert-box {
            background-color: rgba(239, 68, 68, 0.1);
            border-left: 4px solid #ef4444;
            padding: 14px 16px;
            border-radius: 6px;
            margin-top: 24px;
            font-size: 13px;
            color: #fca5a5;
            line-height: 1.5;
        }
        .footer {
            margin-top: 32px;
            text-align: center;
            font-size: 12px;
            color: #64748b;
            border-top: 1px solid #334155;
            padding-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo-badge">A</div>
            <h2 class="logo-text">AuroBarber</h2>
            <span class="logo-sub">Autenticação em Duas Etapas</span>
        </div>

        <h1>Código de Verificação de Login</h1>
        
        <p>Olá, <strong>{{ $user->name }}</strong>.</p>
        
        <p>Identificamos uma tentativa de login na sua conta do AuroBarber. Use o código de 6 dígitos abaixo para autorizar o acesso:</p>

        <div class="code-wrapper">
            <div class="code-box">{{ $code }}</div>
            <p style="margin-top: 10px; font-size: 12px; color: #94a3b8;">
                ⏰ Válido por <strong>10 minutos</strong>. Nunca informe este código a terceiros.
            </p>
        </div>

        <div class="meta-card">
            <div class="meta-row">
                <span class="meta-label">Data e Hora:</span>
                <span class="meta-value">{{ $requestedAt }}</span>
            </div>
            <div class="meta-row">
                <span class="meta-label">Endereço IP:</span>
                <span class="meta-value">{{ $ipAddress }}</span>
            </div>
            <div class="meta-row">
                <span class="meta-label">Dispositivo:</span>
                <span class="meta-value">{{ Str::limit($userAgent, 45) }}</span>
            </div>
        </div>

        <div class="alert-box">
            <strong>⚠️ Não reconhece esta tentativa?</strong><br>
            Se você não tentou fazer login agora, outra pessoa pode saber a sua senha. Acesse o AuroBarber imediatamente e altere suas credenciais de segurança.
        </div>

        <div class="footer">
            AuroBarber • Gestão de Barbearias<br>
            Este é um e-mail automático do sistema de segurança.
        </div>
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bem-vindo ao Aurosoft</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #0f172a;
            color: #f8fafc;
            margin: 0;
            padding: 40px 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #1e293b;
            border: 1px solid #334155;
            border-radius: 16px;
            padding: 36px 32px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.4);
        }
        .logo-area {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 28px;
            border-bottom: 1px solid #334155;
            padding-bottom: 20px;
        }
        .logo-badge {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: #0f172a;
            font-weight: 900;
            font-size: 20px;
            width: 42px;
            height: 42px;
            line-height: 42px;
            text-align: center;
            border-radius: 12px;
        }
        .logo-text {
            font-size: 20px;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -0.5px;
        }
        .logo-sub {
            font-size: 11px;
            color: #fbbf24;
            font-weight: 600;
            display: block;
            text-transform: uppercase;
        }
        h1 {
            font-size: 22px;
            font-weight: 800;
            color: #ffffff;
            margin-top: 0;
            margin-bottom: 12px;
        }
        p {
            font-size: 14px;
            line-height: 1.6;
            color: #cbd5e1;
            margin-bottom: 20px;
        }
        .credentials-box {
            background-color: #0f172a;
            border: 1px solid #475569;
            border-radius: 12px;
            padding: 20px;
            margin: 24px 0;
        }
        .cred-item {
            margin-bottom: 10px;
            font-size: 13px;
        }
        .cred-item:last-child {
            margin-bottom: 0;
        }
        .cred-label {
            color: #94a3b8;
            font-weight: 600;
            display: inline-block;
            width: 140px;
        }
        .cred-value {
            color: #f1f5f9;
            font-weight: 700;
            font-family: monospace;
        }
        .button-area {
            text-align: center;
            margin: 32px 0 20px 0;
        }
        .btn-access {
            display: inline-block;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: #0f172a !important;
            font-weight: 800;
            font-size: 15px;
            padding: 14px 32px;
            border-radius: 10px;
            text-decoration: none;
            box-shadow: 0 4px 14px rgba(245, 158, 11, 0.35);
        }
        .footer {
            margin-top: 36px;
            border-top: 1px solid #334155;
            padding-top: 20px;
            font-size: 12px;
            color: #64748b;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo-area">
            <div class="logo-badge">A</div>
            <div>
                <span class="logo-text">Aurosoft</span>
                <span class="logo-sub">Gestão Escolar & LMS</span>
            </div>
        </div>

        <h1>Ambiente Escolar Disponível! 🎉</h1>
        <p>Olá, <strong>{{ $adminName }}</strong>!</p>
        <p>Temos o prazer de informar que o ambiente de gestão digital da instituição <strong>{{ $schoolName }}</strong> foi configurado com sucesso e já está pronto para uso.</p>

        <div class="credentials-box">
            <div class="cred-item">
                <span class="cred-label">Endereço de Acesso:</span>
                <span class="cred-value">{{ $tenantUrl }}</span>
            </div>
            <div class="cred-item">
                <span class="cred-label">E-mail de Login:</span>
                <span class="cred-value">{{ $adminEmail }}</span>
            </div>
            <div class="cred-item">
                <span class="cred-label">Senha Inicial:</span>
                <span class="cred-value">{{ $initialPassword }}</span>
            </div>
        </div>

        <p style="font-size: 13px; color: #fbbf24; background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.2); padding: 12px; border-radius: 8px;">
            🔒 <strong>Primeiro Acesso Seguro:</strong> Por motivos de segurança e privacidade (LGPD), o sistema solicitará automaticamente a definição da sua nova senha pessoal definitiva assim que você fizer login.
        </p>

        <div class="button-area">
            <a href="{{ $tenantUrl }}" class="btn-access" target="_blank">Acessar Sistema da Escola ➔</a>
        </div>

        <div class="footer">
            <p style="margin: 0; color: #64748b; font-size: 11px;">
                Este é um e-mail automático do sistema Aurosoft Gestão Escolar.<br>
                Em caso de dúvidas ou necessidade de suporte técnico, responda a este e-mail.
            </p>
        </div>
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="pt-BR" class="h-full dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificação de Segurança • AuroBarber</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #0b1120;
            color: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .challenge-card {
            background-color: #1e293b;
            border: 1px solid #334155;
            border-radius: 20px;
            padding: 40px 32px;
            max-width: 440px;
            width: 100%;
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.6), 0 0 25px rgba(245, 158, 11, 0.05);
            text-align: center;
        }
        .shield-icon-wrapper {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 64px;
            height: 64px;
            border-radius: 18px;
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.2), rgba(217, 119, 6, 0.1));
            border: 1px solid rgba(245, 158, 11, 0.4);
            color: #f59e0b;
            margin-bottom: 20px;
            box-shadow: 0 0 25px rgba(245, 158, 11, 0.25);
        }
        .shield-icon-wrapper svg {
            width: 32px;
            height: 32px;
        }
        h1 {
            font-size: 22px;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }
        p.subtitle {
            font-size: 14px;
            color: #94a3b8;
            line-height: 1.5;
            margin-bottom: 28px;
        }
        p.subtitle strong {
            color: #cbd5e1;
            font-weight: 600;
        }
        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 20px;
            text-align: left;
        }
        .alert-error {
            background-color: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.4);
            color: #fca5a5;
        }
        .alert-success {
            background-color: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.4);
            color: #6ee7b7;
        }
        .alert-warning {
            background-color: rgba(245, 158, 11, 0.15);
            border: 1px solid rgba(245, 158, 11, 0.4);
            color: #fcd34d;
        }
        .input-group {
            margin-bottom: 20px;
            text-align: left;
        }
        .input-label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #94a3b8;
            margin-bottom: 8px;
            text-align: center;
        }
        .code-input {
            width: 100%;
            background-color: #0f172a;
            border: 2px solid #334155;
            border-radius: 12px;
            padding: 14px 16px;
            font-size: 28px;
            font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
            font-weight: 800;
            color: #fbbf24;
            text-align: center;
            letter-spacing: 12px;
            outline: none;
            transition: all 0.2s;
        }
        .code-input:focus {
            border-color: #f59e0b;
            box-shadow: 0 0 15px rgba(245, 158, 11, 0.3);
            background-color: #090e1a;
        }
        .remember-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 24px;
            cursor: pointer;
            user-select: none;
        }
        .remember-wrapper input[type="checkbox"] {
            accent-color: #f59e0b;
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        .remember-wrapper label {
            font-size: 13px;
            color: #cbd5e1;
            cursor: pointer;
        }
        .btn-submit {
            width: 100%;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: #0f172a;
            border: none;
            border-radius: 12px;
            padding: 14px 20px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 14px rgba(245, 158, 11, 0.35);
        }
        .btn-submit:hover {
            background: linear-gradient(135deg, #fbbf24, #d97706);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(245, 158, 11, 0.45);
        }
        .btn-submit:active {
            transform: translateY(0);
        }
        .actions-footer {
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid #334155;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 13px;
        }
        .btn-link {
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 6px;
            text-decoration: underline;
            transition: color 0.2s;
        }
        .btn-link:hover {
            color: #f59e0b;
        }
        .btn-cancel {
            color: #ef4444;
        }
        .btn-cancel:hover {
            color: #f87171;
        }
    </style>
</head>
<body>
    <div class="challenge-card">
        <div class="shield-icon-wrapper">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
            </svg>
        </div>

        <h1>Código de Segurança</h1>
        <p class="subtitle">
            Para sua segurança, enviamos um código de 6 dígitos para o e-mail <strong>{{ $maskedEmail }}</strong>.
        </p>

        @if ($errors->any())
            <div class="alert alert-error">
                @foreach ($errors->all() as $error)
                    <div>⚠️ {{ $error }}</div>
                @endforeach
            </div>
        @endif

        @if (session('status'))
            <div class="alert alert-success">
                ✅ {{ session('status') }}
            </div>
        @endif

        @if (session('warning'))
            <div class="alert alert-warning">
                ⏳ {{ session('warning') }}
            </div>
        @endif

        <form action="{{ route('auth.2fa.verify') }}" method="POST">
            @csrf
            <div class="input-group">
                <label for="code" class="input-label">Digite o código recebido</label>
                <input 
                    type="text" 
                    id="code" 
                    name="code" 
                    class="code-input" 
                    maxlength="6" 
                    pattern="[0-9]*" 
                    inputmode="numeric" 
                    autocomplete="one-time-code" 
                    placeholder="••••••" 
                    autofocus 
                    required
                >
            </div>

            <div class="remember-wrapper">
                <input type="checkbox" id="remember_device" name="remember_device" value="1" checked>
                <label for="remember_device">Lembrar deste dispositivo por 30 dias</label>
            </div>

            <button type="submit" class="btn-submit">
                Confirmar e Entrar
            </button>
        </form>

        <div class="actions-footer">
            <form action="{{ route('auth.2fa.resend') }}" method="POST" style="display: inline;">
                @csrf
                <button type="submit" class="btn-link">
                    Reenviar código
                </button>
            </form>

            <form action="{{ route('auth.2fa.cancel') }}" method="POST" style="display: inline;">
                @csrf
                <button type="submit" class="btn-link btn-cancel">
                    Voltar ao Login
                </button>
            </form>
        </div>
    </div>
</body>
</html>

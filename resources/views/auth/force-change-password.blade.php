<!DOCTYPE html>
<html lang="pt-BR" class="h-full dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Definição de Nova Senha • Aurosoft</title>
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
        .card {
            background-color: #1e293b;
            border: 1px solid #334155;
            border-radius: 20px;
            padding: 40px 32px;
            max-width: 480px;
            width: 100%;
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.6), 0 0 25px rgba(245, 158, 11, 0.05);
            text-align: center;
        }
        .icon-wrapper {
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
        .icon-wrapper svg {
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
            margin-bottom: 24px;
        }
        .alert-error {
            background-color: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 12px;
            padding: 12px 14px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #fca5a5;
            text-align: left;
        }
        .alert-error ul {
            margin-left: 18px;
            margin-top: 4px;
        }
        .form-group {
            margin-bottom: 16px;
            text-align: left;
        }
        label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #cbd5e1;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        input[type="password"] {
            width: 100%;
            padding: 12px 16px;
            border-radius: 12px;
            background-color: #0f172a;
            border: 1px solid #334155;
            color: #ffffff;
            font-size: 15px;
            outline: none;
            transition: all 0.2s ease;
        }
        input[type="password"]:focus {
            border-color: #f59e0b;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2);
        }
        .helper-text {
            font-size: 11px;
            color: #64748b;
            margin-top: 4px;
        }
        .submit-btn {
            width: 100%;
            padding: 14px 20px;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: #0f172a;
            font-size: 15px;
            font-weight: 700;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 10px 20px -5px rgba(245, 158, 11, 0.4);
        }
        .submit-btn:hover {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            transform: translateY(-1px);
            box-shadow: 0 12px 25px -5px rgba(245, 158, 11, 0.5);
        }
        .footer-action {
            margin-top: 24px;
            font-size: 13px;
            color: #64748b;
        }
        .footer-action a {
            color: #94a3b8;
            text-decoration: none;
            transition: color 0.2s;
        }
        .footer-action a:hover {
            color: #f59e0b;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon-wrapper">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
            </svg>
        </div>

        <h1>Primeiro Acesso • Nova Senha</h1>
        <p class="subtitle">
            Bem-vindo(a) ao <strong>Aurosoft</strong>!<br>
            Por motivos de segurança e conformidade, é obrigatório definir uma nova senha pessoal definitiva antes de prosseguir.
        </p>

        @if ($errors->any())
            <div class="alert-error">
                <strong>Atenção:</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('password.force_change.update') }}">
            @csrf

            <div class="form-group">
                <label for="current_password">Senha Provisória Atual</label>
                <input
                    type="password"
                    id="current_password"
                    name="current_password"
                    required
                    autofocus
                    placeholder="Digite a senha temporária (ex: 12345678)"
                >
            </div>

            <div class="form-group">
                <label for="password">Nova Senha Pessoal</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    placeholder="Mínimo 8 caracteres"
                >
                <div class="helper-text">Crie uma senha forte com letras e números.</div>
            </div>

            <div class="form-group">
                <label for="password_confirmation">Confirmar Nova Senha</label>
                <input
                    type="password"
                    id="password_confirmation"
                    name="password_confirmation"
                    required
                    placeholder="Repita a nova senha"
                >
            </div>

            <button type="submit" class="submit-btn">
                <span>Salvar Nova Senha & Acessar</span>
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                </svg>
            </button>
        </form>

        <div class="footer-action">
            <form method="POST" action="{{ route('filament.admin.auth.logout') }}" style="display: inline;">
                @csrf
                <button type="submit" style="background:none; border:none; color:#64748b; font-size:12px; cursor:pointer; text-decoration:underline;">
                    Sair e entrar com outra conta
                </button>
            </form>
        </div>
    </div>
</body>
</html>

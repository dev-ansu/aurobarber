<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Conectando ao Laboratório Interativo...</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #f8fafc;
        }
        .card {
            text-align: center;
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 28px;
            padding: 44px 36px;
            max-width: 480px;
            width: 90%;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6);
        }
        .spinner {
            width: 56px;
            height: 56px;
            border: 5px solid rgba(245, 158, 11, 0.2);
            border-top-color: #f59e0b;
            border-radius: 50%;
            animation: spin 0.9s cubic-bezier(0.55, 0.15, 0.45, 0.85) infinite;
            margin: 0 auto 24px;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        h1 { font-size: 1.35rem; font-weight: 800; margin: 0 0 8px; color: #ffffff; letter-spacing: -0.02em; }
        p { font-size: 0.875rem; color: #94a3b8; margin: 0 0 20px; line-height: 1.5; }
        .student-badge {
            display: inline-block;
            background: rgba(245, 158, 11, 0.15);
            color: #fbbf24;
            padding: 6px 14px;
            border-radius: 9999px;
            font-size: 0.85rem;
            font-weight: 700;
            margin-bottom: 16px;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }
        #logarButton { display: none; }
    </style>
</head>
<body>
    <div class="card">
        <div class="spinner"></div>
        <div class="student-badge">
            {{ $studentName }}@if (!empty($schoolName)) • {{ $schoolName }}@endif
        </div>
        <h1>Conectando à sua aula interativa...</h1>
        <p>Aguarde um instante enquanto autenticamos seu acesso ao sistema de cursos interativos.</p>

        <form id="logar" action="{{ $actionUrl }}" method="POST">
            <input type="hidden" name="aluno" value="{{ $username }}">
            <input type="hidden" name="senha" value="{{ $password }}">
            <button id="logarButton" type="submit">Entrar</button>
        </form>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const logar = document.querySelector("#logar");
            if (logar) {
                const btn = logar.querySelector("#logarButton");
                setTimeout(function () {
                    if (btn) {
                        btn.click();
                    } else {
                        logar.submit();
                    }
                }, 100);
            }
        });
    </script>
</body>
</html>


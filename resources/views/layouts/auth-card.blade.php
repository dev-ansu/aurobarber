@props([
    'title' => 'Verificação de Segurança • AuroBarber',
    'maxWidth' => '440px',
])

<!DOCTYPE html>
<html lang="pt-BR" class="h-full dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

    @if(filled(config('services.turnstile.site_key')))
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endif

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
        .auth-card {
            background-color: #1e293b;
            border: 1px solid #334155;
            border-radius: 20px;
            padding: 40px 32px;
            max-width: {{ $maxWidth }};
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
            font-size: 13px;
            font-weight: 600;
            color: #cbd5e1;
            margin-bottom: 8px;
        }
        .code-input {
            width: 100%;
            background-color: #0f172a;
            border: 2px solid #334155;
            border-radius: 12px;
            padding: 14px 16px;
            color: #ffffff;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: 6px;
            text-align: center;
            transition: all 0.2s ease;
            outline: none;
        }
        .code-input:focus {
            border-color: #f59e0b;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2);
            background-color: #0b1120;
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
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.25);
        }
        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(245, 158, 11, 0.35);
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
        }
        .btn-submit:active {
            transform: translateY(0);
        }
        .footer-links {
            margin-top: 28px;
            padding-top: 20px;
            border-top: 1px solid #334155;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 13px;
        }
        .footer-link {
            color: #94a3b8;
            text-decoration: none;
            transition: color 0.2s;
            cursor: pointer;
            background: none;
            border: none;
            font-size: 13px;
            font-family: inherit;
        }
        .footer-link:hover {
            color: #f59e0b;
        }
    </style>

    {{ $head ?? '' }}
    @yield('head')
</head>
<body>
    <div class="auth-card">
        @if(isset($icon))
            <div class="icon-wrapper">
                {{ $icon }}
            </div>
        @elseif(isset($logo))
            <div style="margin-bottom: 20px;">
                {{ $logo }}
            </div>
        @endif

        @if(isset($heading))
            <h1>{{ $heading }}</h1>
        @endif

        @if(isset($subtitle))
            <p class="subtitle">{{ $subtitle }}</p>
        @endif

        @if (session('status'))
            <div class="alert alert-success">
                ✓ {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-error">
                @foreach ($errors->all() as $error)
                    <div>• {{ $error }}</div>
                @endforeach
            </div>
        @endif

        @if(isset($slot))
            {{ $slot }}
        @else
            @yield('content')
        @endif

        @if(isset($footer))
            <div class="footer-links">
                {{ $footer }}
            </div>
        @endif
    </div>

    {{ $scripts ?? '' }}
    @yield('scripts')
</body>
</html>

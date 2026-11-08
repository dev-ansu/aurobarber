<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-slate-950">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Licença Suspensa — Aurosoft LMS</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full flex items-center justify-center p-4 antialiased text-slate-100 bg-gradient-to-b from-slate-950 via-slate-900 to-slate-950">

    <div class="w-full max-w-lg rounded-3xl border border-slate-800 bg-slate-900/90 p-8 shadow-2xl backdrop-blur-md space-y-6 text-center">

        <!-- LOGO & ÍCONE -->
        <div class="flex flex-col items-center gap-3">
            <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-400 shadow-inner">
                <svg class="h-8 w-8" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                </svg>
            </div>
            <div>
                <h1 class="text-xl font-black text-white tracking-tight">Aurosoft LMS</h1>
                <p class="text-xs font-bold uppercase tracking-wider text-amber-400 mt-0.5">Acesso Temporariamente Suspenso</p>
            </div>
        </div>

        @if(!empty($status['school_name']))
            <div class="inline-flex items-center gap-1.5 rounded-full bg-slate-800/80 px-4 py-1 text-xs font-medium text-slate-300 border border-slate-700">
                <span>🏫</span>
                <span>{{ $status['school_name'] }}</span>
            </div>
        @endif

        <!-- MENSAGEM DO GOOGLE SHEETS -->
        <div class="rounded-2xl border border-amber-500/30 bg-amber-950/20 p-4 text-xs text-amber-200 leading-relaxed text-left space-y-2">
            <div class="font-bold flex items-center gap-1.5 text-amber-300">
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />
                </svg>
                <span>Comunicado de Licença:</span>
            </div>
            <p class="text-slate-300">
                {{ $status['message'] ?: 'A mensalidade do software está com pendência financeira. Regularize para restabelecer o acesso imediatamente.' }}
            </p>
            @if(!empty($status['due_date']))
                <div class="pt-1 text-[11px] text-amber-300/80 font-mono">
                    Vencimento registrado: <strong>{{ $status['due_date'] }}</strong>
                </div>
            @endif
        </div>

        @if(session('error'))
            <div class="rounded-xl border border-rose-500/30 bg-rose-950/30 p-3 text-xs text-rose-300">
                {{ session('error') }}
            </div>
        @endif

        <!-- AÇÕES -->
        <div class="space-y-3 pt-2">
            <!-- BOTÃO VERIFICAR AGORA -->
            <form method="POST" action="{{ route('license.verify') }}">
                @csrf
                <button type="submit" class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-primary-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-primary-600/25 hover:bg-primary-500 transition cursor-pointer">
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                    <span>Verificar Pagamento Agora</span>
                </button>
            </form>

            <!-- BOTÃO WHATSAPP -->
            @php
                $cleanPhone = preg_replace('/\D/', '', $status['support_phone'] ?: '');
                $waText = urlencode("Olá, estou na tela de licença do Aurosoft da escola " . ($status['school_name'] ?? '') . " e gostaria de regularizar a mensalidade.");
            @endphp
            @if(!empty($cleanPhone))
                <a href="https://wa.me/{{ $cleanPhone }}?text={{ $waText }}" target="_blank" class="w-full inline-flex items-center justify-center gap-2 rounded-xl border border-emerald-500/40 bg-emerald-500/10 px-5 py-3 text-sm font-bold text-emerald-400 hover:bg-emerald-500/20 transition">
                    <span>💬</span>
                    <span>Falar com o Suporte no WhatsApp</span>
                </a>
            @endif
        </div>

        <!-- RODAPÉ DE AUDITORIA -->
        <div class="pt-2 border-t border-slate-800 text-[10px] text-slate-500">
            Aurosoft Enterprise · Servidor Local On-Premise · {{ now()->format('d/m/Y H:i') }}
        </div>

    </div>

</body>
</html>


<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validação de Autenticidade de Certificado • {{ $schoolName }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@500;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex flex-col justify-between antialiased selection:bg-amber-500 selection:text-slate-950">
    <div class="relative overflow-hidden flex-1 flex flex-col">
        {{-- GLOW DE FUNDO --}}
        <div class="absolute -top-40 left-1/2 -translate-x-1/2 w-[800px] h-[400px] bg-amber-500/10 blur-[130px] rounded-full pointer-events-none"></div>

        {{-- BARRA SUPERIOR --}}
        <header class="border-b border-slate-800/80 bg-slate-900/80 backdrop-blur sticky top-0 z-30 px-6 py-4">
            <div class="max-w-4xl mx-auto flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-tr from-amber-500 to-amber-300 text-slate-950 font-black text-xl shadow-lg shadow-amber-500/20 select-none">
                        🎓
                    </span>
                    <div>
                        <span class="text-[10px] uppercase font-extrabold tracking-widest text-amber-400 block leading-tight">Portal Oficial de Autenticidade</span>
                        <span class="text-sm font-bold text-white tracking-wide">{{ $schoolName }}</span>
                    </div>
                </div>
                <a href="{{ url('/') }}" class="text-xs font-semibold text-slate-400 hover:text-white transition flex items-center gap-1.5">
                    <span>Página Inicial</span>
                    <span>➔</span>
                </a>
            </div>
        </header>

        {{-- CONTEÚDO PRINCIPAL --}}
        <main class="max-w-3xl mx-auto px-4 py-10 sm:py-14 w-full flex-1">
            {{-- CAIXA DE BUSCA / DIGITAÇÃO DE CÓDIGO --}}
            <div class="mb-8">
                <form action="{{ route('student.certificate.verify') }}" method="GET" class="relative max-w-xl mx-auto">
                    <div class="flex items-center rounded-2xl border border-slate-800 bg-slate-900/90 p-1.5 shadow-xl backdrop-blur transition focus-within:border-amber-500/50 focus-within:ring-2 focus-within:ring-amber-500/20">
                        <div class="pl-3.5 pr-2 text-slate-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input
                            type="text"
                            name="code"
                            value="{{ $code }}"
                            placeholder="Digite o código (ex: AURO-1-B8F29A4D)..."
                            class="w-full bg-transparent border-none text-sm text-white placeholder:text-slate-500 focus:outline-none focus:ring-0 uppercase font-mono tracking-wider font-semibold"
                            required
                        >
                        <button
                            type="submit"
                            class="shrink-0 rounded-xl bg-amber-500 px-5 py-2.5 text-xs font-bold text-slate-950 transition hover:bg-amber-400 shadow-md shadow-amber-500/20 cursor-pointer"
                        >
                            Verificar
                        </button>
                    </div>
                </form>
            </div>

            @if($isValid && $cert)
                {{-- CARD DE CERTIFICADO VÁLIDO --}}
                <div class="rounded-3xl border border-emerald-500/30 bg-slate-900/95 p-6 sm:p-10 shadow-2xl backdrop-blur relative overflow-hidden">
                    <div class="absolute -top-10 -right-10 w-44 h-44 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

                    {{-- SELO DE AUTENTICIDADE VERDE --}}
                    <div class="text-center mb-8">
                        <div class="inline-flex items-center gap-2 rounded-full border border-emerald-500/40 bg-emerald-500/10 px-5 py-2 text-xs font-bold text-emerald-400 shadow-inner mb-4">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>CERTIFICADO OFICIAL VÁLIDO & AUTÊNTICO</span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-black text-white">
                            Autenticidade Confirmada
                        </h1>
                        <p class="text-xs text-slate-400 mt-2 max-w-lg mx-auto leading-relaxed">
                            O documento consultado é genuíno e encontra-se devidamente registrado na base de certificações da instituição de ensino, em conformidade com as diretrizes do Ministério da Educação (MEC).
                        </p>
                    </div>

                    {{-- TIPO DE CERTIFICAÇÃO --}}
                    <div class="mb-6 flex items-center justify-between border-b border-slate-800/80 pb-4">
                        <div>
                            <span class="text-[10px] uppercase font-bold tracking-wider text-slate-400 block">Modalidade da Emissão</span>
                            <span class="text-sm font-extrabold text-amber-400">{{ $typeLabel }}</span>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] uppercase font-bold tracking-wider text-slate-400 block">Status de Registro</span>
                            <span class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-400">
                                <span class="h-2 w-2 rounded-full bg-emerald-400 animate-pulse"></span> Homologado & Ativo
                            </span>
                        </div>
                    </div>

                    {{-- TABELA DE DADOS DO CERTIFICADO --}}
                    <div class="space-y-3 rounded-2xl border border-slate-800 bg-slate-950/70 p-5 sm:p-6 text-xs sm:text-sm">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between py-2 border-b border-slate-800/80 gap-1">
                            <span class="text-xs text-slate-400 font-medium">Aluno(a) Certificado(a)</span>
                            <span class="font-extrabold text-white text-sm sm:text-base">{{ $cert['student_name'] }}</span>
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-center justify-between py-2 border-b border-slate-800/80 gap-1">
                            <span class="text-xs text-slate-400 font-medium">Formação / Disciplina</span>
                            <span class="font-bold text-slate-200 text-right">{{ $cert['title'] }}</span>
                        </div>

                        <div class="grid grid-cols-2 gap-4 py-2 border-b border-slate-800/80">
                            <div>
                                <span class="text-xs text-slate-400 font-medium block">Carga Horária</span>
                                <span class="font-black text-amber-400 text-sm">{{ $cert['workload'] }} horas</span>
                            </div>
                            <div class="text-right">
                                <span class="text-xs text-slate-400 font-medium block">Aproveitamento / Média</span>
                                <span class="font-black text-white text-sm">{{ number_format($cert['average_grade'], 1, ',', '.') }}%</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 py-2 border-b border-slate-800/80">
                            <div>
                                <span class="text-xs text-slate-400 font-medium block">Período Letivo</span>
                                <span class="text-slate-300 font-semibold">{{ $cert['start_date'] }} a {{ $cert['completion_date'] }}</span>
                            </div>
                            <div class="text-right">
                                <span class="text-xs text-slate-400 font-medium block">Registro do Livro</span>
                                <span class="text-slate-300 font-mono font-semibold">Livro {{ $cert['book_number'] }} · Fl. {{ $cert['sheet_number'] }} · Reg. #{{ $cert['registration_number'] }}</span>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-center justify-between py-2 border-b border-slate-800/80 gap-1">
                            <span class="text-xs text-slate-400 font-medium">Instituição Emissora</span>
                            <div class="sm:text-right">
                                <span class="font-bold text-white block">{{ $cert['school_name'] }}</span>
                                @if(!empty($cert['school_cnpj']))
                                    <span class="text-[11px] text-slate-400 block font-mono">CNPJ: {{ $cert['school_cnpj'] }} · {{ $cert['school_city'] }}/{{ $cert['school_state'] }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-center justify-between py-2 border-b border-slate-800/80 gap-1">
                            <span class="text-xs text-slate-400 font-medium">Código de Autenticação</span>
                            <span class="font-mono font-bold text-amber-400 text-sm tracking-wider select-all">{{ $code }}</span>
                        </div>

                        <div class="pt-2 text-[11px] text-slate-400 leading-relaxed">
                            <span class="font-bold text-slate-300">Base Legal:</span> {{ $cert['legal_base'] }}.
                        </div>
                    </div>

                    <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3">
                        <button
                            type="button"
                            onclick="window.print()"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl bg-slate-800 hover:bg-slate-700 px-5 py-3 text-xs font-bold text-white transition cursor-pointer"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            <span>Imprimir Comprovante de Validação</span>
                        </button>
                        <a
                            href="{{ route('student.certificate.verify') }}"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl border border-slate-700 hover:border-slate-600 px-5 py-3 text-xs font-bold text-slate-300 transition"
                        >
                            <span>Nova Consulta</span>
                        </a>
                    </div>
                </div>
            @elseif(!empty($code))
                {{-- CARD DE CERTIFICADO NÃO ENCONTRADO --}}
                <div class="rounded-3xl border border-rose-500/30 bg-slate-900/90 p-8 sm:p-12 shadow-2xl backdrop-blur text-center">
                    <div class="inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-rose-500/10 text-rose-400 text-3xl mb-4 border border-rose-500/20">
                        ✕
                    </div>
                    <h1 class="text-xl sm:text-2xl font-black text-white mb-2">
                        Certificado Não Localizado
                    </h1>
                    <p class="text-xs text-slate-400 mb-6 max-w-md mx-auto leading-relaxed">
                        O código <span class="font-mono font-bold text-rose-400">{{ $code }}</span> não corresponde a nenhum registro oficial ativo na base de dados desta instituição.
                    </p>

                    <div class="rounded-2xl border border-slate-800 bg-slate-950/60 p-4 text-left text-xs text-slate-400 max-w-md mx-auto space-y-2 mb-6">
                        <div class="font-bold text-slate-200">Recomendações:</div>
                        <ul class="list-disc pl-4 space-y-1">
                            <li>Verifique se o código foi digitado corretamente (incluindo o prefixo <strong class="text-white">AURO-</strong>).</li>
                            <li>Confirme se o documento pertence a esta instituição de ensino.</li>
                            <li>Em caso de dúvidas, solicite a reapresentação do documento original com o QR Code nítido.</li>
                        </ul>
                    </div>

                    <a href="{{ route('student.certificate.verify') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-amber-500 px-6 py-3 text-xs font-bold text-slate-950 hover:bg-amber-400 transition">
                        <span>Tentar Outro Código</span>
                    </a>
                </div>
            @else
                {{-- TELA INICIAL DE CONSULTA (SEM CÓDIGO AINDA) --}}
                <div class="rounded-3xl border border-slate-800 bg-slate-900/80 p-8 sm:p-12 shadow-xl backdrop-blur text-center">
                    <div class="inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-500/10 text-amber-400 text-3xl mb-4 border border-amber-500/20">
                        🔍
                    </div>
                    <h2 class="text-xl sm:text-2xl font-black text-white mb-2">
                        Consulta Pública de Certificados
                    </h2>
                    <p class="text-xs text-slate-400 max-w-md mx-auto leading-relaxed mb-6">
                        Para verificar a autenticidade de um certificado emitido por nossa instituição, digite o código de autenticação localizado no verso do documento ou aponte a câmera do seu celular para o QR Code impresso.
                    </p>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 max-w-xl mx-auto text-left text-xs">
                        <div class="rounded-2xl border border-slate-800/80 bg-slate-950/50 p-4">
                            <span class="text-lg block mb-1">📱</span>
                            <span class="font-bold text-white block">Leitura por QR Code</span>
                            <span class="text-slate-400 text-[11px]">Aponte a câmera do celular para o QR Code no verso do certificado.</span>
                        </div>
                        <div class="rounded-2xl border border-slate-800/80 bg-slate-950/50 p-4">
                            <span class="text-lg block mb-1">🔢</span>
                            <span class="font-bold text-white block">Código Alfanumérico</span>
                            <span class="text-slate-400 text-[11px]">Digite o código no campo de busca acima e clique em Verificar.</span>
                        </div>
                        <div class="rounded-2xl border border-slate-800/80 bg-slate-950/50 p-4">
                            <span class="text-lg block mb-1">⚖️</span>
                            <span class="font-bold text-white block">Validade Nacional</span>
                            <span class="text-slate-400 text-[11px]">Em conformidade com a LDB 9.394/96 e Decreto Federal 5.154/04.</span>
                        </div>
                    </div>
                </div>
            @endif
        </main>

        {{-- RODAPÉ --}}
        <footer class="border-t border-slate-800/60 bg-slate-950/90 py-6 px-4 text-center text-xs text-slate-500">
            <p>
                Aurosoft LMS • Sistema de Gestão Escolar Inteligente · Validação Digital de Diplomas e Certificações
            </p>
        </footer>
    </div>
</body>
</html>

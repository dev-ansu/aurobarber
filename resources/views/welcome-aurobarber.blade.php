<!DOCTYPE html>
<html lang="pt-BR" class="dark scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AuroBarber • Gestão & Agendamento Inteligente para Barbearias</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600,700,900&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        amber: {
                            500: '#f59e0b',
                            600: '#d97706',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-slate-950 text-slate-100 font-sans antialiased selection:bg-amber-500 selection:text-slate-950">

    {{-- HEADER --}}
    <header class="sticky top-0 z-50 bg-slate-950/80 backdrop-blur border-b border-slate-800/80">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 h-20 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="h-11 w-11 rounded-2xl bg-gradient-to-tr from-amber-600 to-amber-400 flex items-center justify-center font-black text-slate-950 text-xl shadow-lg shadow-amber-500/20">
                    💈
                </div>
                <div>
                    <span class="text-xl font-black text-white tracking-tight">Auro<span class="text-amber-500">Barber</span></span>
                    <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest">by Aurosoft Tecnologia</span>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <a href="/admin" class="inline-flex items-center gap-2 rounded-xl bg-slate-900 hover:bg-slate-800 border border-slate-800 px-5 py-2.5 text-xs font-bold text-white transition hover:border-amber-500/50">
                    <span>Acessar Painel</span>
                    <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </a>
            </div>
        </div>
    </header>

    {{-- HERO SECTION --}}
    <section class="relative pt-16 pb-24 overflow-hidden">
        <div class="pointer-events-none absolute -top-40 left-1/2 -translate-x-1/2 h-[600px] w-[800px] rounded-full bg-amber-500/10 blur-3xl opacity-60"></div>
        
        <div class="max-w-5xl mx-auto px-4 text-center relative z-10">
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-amber-500/10 border border-amber-500/20 text-amber-400 text-xs font-semibold mb-6">
                <span>✂️</span>
                <span>Software Completo de Agendamento & Gestão</span>
            </div>

            <h1 class="text-4xl sm:text-6xl font-black text-white tracking-tight leading-tight mb-6">
                Sua Barbearia no Piloto Automático: <br>
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-400 to-amber-600">
                    Agendamentos, Comissões e Caixa
                </span>
            </h1>

            <p class="text-base sm:text-xl text-slate-400 max-w-3xl mx-auto mb-10 leading-relaxed">
                Elimine o caos das mensagens no WhatsApp. Dê aos seus clientes um link profissional de agendamento em 30 segundos e tenha o controle total das comissões dos seus barbeiros em tempo real.
            </p>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="/admin" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-2xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-black px-8 py-4 text-sm shadow-xl shadow-amber-500/25 transition transform hover:-translate-y-0.5">
                    <span>Entrar no Sistema</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </a>
                <a href="#recursos" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-2xl bg-slate-900 hover:bg-slate-800 border border-slate-800 text-slate-300 font-bold px-8 py-4 text-sm transition">
                    <span>Ver Como Funciona</span>
                </a>
            </div>
        </div>
    </section>

    {{-- 3 CARDS DE BENEFÍCIOS --}}
    <section id="recursos" class="py-20 border-t border-slate-900 bg-slate-950/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="rounded-3xl border border-slate-800/80 bg-slate-900/60 p-8 hover:border-amber-500/40 transition">
                    <div class="h-12 w-12 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-2xl mb-6">
                        📱
                    </div>
                    <h3 class="text-xl font-bold text-white mb-3">Agendamento Online no Celular</h3>
                    <p class="text-sm text-slate-400 leading-relaxed">
                        Seus clientes escolhem o barbeiro, o serviço e o horário livre direto pelo link da Bio do Instagram ou WhatsApp. Rápido, sem cadastro demorado.
                    </p>
                </div>

                <div class="rounded-3xl border border-slate-800/80 bg-slate-900/60 p-8 hover:border-amber-500/40 transition">
                    <div class="h-12 w-12 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-2xl mb-6">
                        💰
                    </div>
                    <h3 class="text-xl font-bold text-white mb-3">Cálculo Automático de Comissões</h3>
                    <p class="text-sm text-slate-400 leading-relaxed">
                        Cada colaborador tem sua porcentagem de comissão configurada. Ao concluir um atendimento, o sistema já calcula exatamente quanto cada barbeiro tem a receber.
                    </p>
                </div>

                <div class="rounded-3xl border border-slate-800/80 bg-slate-900/60 p-8 hover:border-amber-500/40 transition">
                    <div class="h-12 w-12 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-2xl mb-6">
                        📊
                    </div>
                    <h3 class="text-xl font-bold text-white mb-3">Comanda Rápida & Fluxo de Caixa</h3>
                    <p class="text-sm text-slate-400 leading-relaxed">
                        Adicione produtos (pomadas, bebidas) na mesma comanda do corte. Tenha relatórios diários de faturamento e pagamentos por Pix, Dinheiro e Cartão.
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- FOOTER --}}
    <footer class="py-12 border-t border-slate-900 bg-slate-950 text-center text-xs text-slate-500">
        <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <span class="font-bold text-slate-400">AuroBarber</span>
                <span>&middot;</span>
                <span>Uma solução da Aurosoft Tecnologia</span>
            </div>
            <p>&copy; {{ date('Y') }} AuroBarber. Todos os direitos reservados.</p>
        </div>
    </footer>

</body>
</html>

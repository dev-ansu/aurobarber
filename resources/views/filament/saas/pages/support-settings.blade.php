<x-filament-panels::page>
    <div class="space-y-6 max-w-4xl mx-auto">

        {{-- 1. HEADER DO SUPORTE --}}
        <div class="relative overflow-hidden rounded-3xl border border-slate-800 bg-gradient-to-r from-slate-950 via-slate-900 to-slate-950 p-6 sm:p-8 shadow-2xl">
            <div class="absolute -top-24 -right-24 h-64 w-64 rounded-full bg-emerald-500/10 blur-3xl pointer-events-none"></div>
            
            <div class="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="space-y-1.5">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-bold tracking-wide">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span>Canal Oficial de Atendimento</span>
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-black text-white tracking-tight">Contato de Suporte ao Cliente</h2>
                    <p class="text-xs sm:text-sm text-slate-400 max-w-2xl">
                        Este WhatsApp será exibido para as barbearias quando estiverem com a licença em carência ou suspensas por faturas em aberto.
                    </p>
                </div>

                <div class="shrink-0 flex items-center justify-center h-16 w-16 rounded-2xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 shadow-inner">
                    <x-filament::icon icon="heroicon-o-chat-bubble-left-right" class="w-8 h-8" />
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            {{-- 2. FORMULÁRIO DE EDIÇÃO --}}
            <div class="md:col-span-2 rounded-3xl border border-slate-800 bg-slate-950/90 p-6 sm:p-8 shadow-xl space-y-6">
                <div>
                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                        <span>Configurar Número do WhatsApp</span>
                    </h3>
                    <p class="text-xs text-slate-400 mt-1">
                        Informe o número completo com DDI (55 para Brasil) e DDD sem espaços ou caracteres especiais.
                    </p>
                </div>

                <form wire:submit="save" class="space-y-5">
                    <div class="space-y-2">
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-300 block">Número de Telefone / WhatsApp *</label>
                        <div class="relative rounded-2xl border border-slate-800 bg-slate-900 focus-within:border-emerald-500 focus-within:ring-1 focus-within:ring-emerald-500 transition">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                                💬
                            </div>
                            <input
                                type="tel"
                                wire:model="supportPhone"
                                placeholder="Ex: 5585999999999"
                                class="w-full pl-11 pr-4 py-3.5 bg-transparent text-sm text-white font-mono placeholder-slate-500 focus:outline-none"
                            />
                        </div>
                        @error('supportPhone')
                            <p class="text-xs text-rose-400 font-semibold">{{ $message }}</p>
                        @enderror
                        <p class="text-[11px] text-slate-400">
                            💡 Exemplos válidos: <code class="text-emerald-400">5585988887777</code> ou <code class="text-emerald-400">(85) 98888-7777</code>. O sistema salva automaticamente o padrão internacional limpo.
                        </p>
                    </div>

                    <div class="pt-3 border-t border-slate-800/80 flex items-center gap-3">
                        <button
                            type="submit"
                            class="px-6 py-3 rounded-xl bg-gradient-to-r from-emerald-500 to-emerald-400 hover:brightness-110 text-slate-950 font-black text-xs transition shadow-lg shadow-emerald-500/20 flex items-center gap-2 cursor-pointer active:scale-95"
                        >
                            <x-filament::icon icon="heroicon-m-check" class="w-4 h-4" />
                            <span>Salvar Contato Oficial</span>
                        </button>

                        @if(!empty($supportPhone))
                            @php
                                $cleanDigits = preg_replace('/\D/', '', $supportPhone);
                            @endphp
                            <a
                                href="https://wa.me/{{ $cleanDigits }}?text=Ol%C3%A1!%20Sou%20da%20equipe%20AuroBarber%20testando%20o%20canal%20de%20suporte."
                                target="_blank"
                                rel="noopener noreferrer"
                                class="px-4 py-3 rounded-xl border border-slate-700 bg-slate-900 hover:bg-slate-800 text-slate-200 text-xs font-bold transition flex items-center gap-1.5"
                            >
                                <span>Abrir Teste</span>
                                <x-filament::icon icon="heroicon-m-arrow-top-right-on-square" class="w-3.5 h-3.5 text-slate-400" />
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            {{-- 3. CARD DE PRÉ-VISUALIZAÇÃO (COMO A BARBEARIA ENXERGA) --}}
            <div class="rounded-3xl border border-slate-800 bg-slate-950/90 p-6 shadow-xl flex flex-col justify-between space-y-4">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-amber-400 block mb-1">Simulador de Tela</span>
                    <h4 class="text-sm font-bold text-white mb-2">Visão do Cliente Suspenso</h4>
                    <p class="text-xs text-slate-400 leading-relaxed mb-4">
                        Veja abaixo como o botão de socorro aparece na tela de bloqueio da barbearia:
                    </p>

                    <div class="rounded-2xl border border-rose-500/20 bg-rose-950/20 p-4 space-y-3">
                        <div class="flex items-center gap-2 text-rose-400 text-xs font-bold">
                            <span>🔒 Acesso Temporariamente Bloqueado</span>
                        </div>
                        <p class="text-[11px] text-slate-300">
                            Sua assinatura está suspensa. Fale com a equipe pelo canal de atendimento:
                        </p>
                        
                        @if(!empty($supportPhone))
                            <div class="w-full py-2.5 px-3 rounded-xl bg-emerald-500 text-slate-950 font-black text-xs text-center flex items-center justify-center gap-2 shadow-sm">
                                <span>💬 Falar no WhatsApp</span>
                            </div>
                        @else
                            <div class="w-full py-2.5 px-3 rounded-xl bg-slate-800 text-slate-400 font-bold text-xs text-center border border-dashed border-slate-700">
                                <span>(Nenhum contato cadastrado)</span>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-800 text-[11px] text-slate-400 space-y-1">
                    <p>• Suporte 100% responsivo.</p>
                    <p>• Zero interferência no banco das barbearias.</p>
                </div>
            </div>

        </div>

    </div>
</x-filament-panels::page>

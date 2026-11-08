<!DOCTYPE html>
<html lang="pt-BR" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $barbershop->name }} • Agendamento Online</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600,700,900&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        :root {
            --brand-color: {{ $barbershop->brand_color ?: '#d97706' }};
        }
        .bg-brand { background-color: var(--brand-color) !important; }
        .text-brand { color: var(--brand-color) !important; }
        .border-brand { border-color: var(--brand-color) !important; }
        .ring-brand { --tw-ring-color: var(--brand-color) !important; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 font-sans antialiased selection:bg-amber-500 selection:text-slate-950 min-h-screen flex flex-col justify-between pb-24"
      x-data="bookingApp('{{ $barbershop->slug }}')">

    {{-- CABEÇALHO DA BARBEARIA --}}
    <header class="bg-slate-900/90 border-b border-slate-800/80 backdrop-blur sticky top-0 z-40">
        <div class="max-w-4xl mx-auto px-4 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                @if ($barbershop->logo_url)
                    <img src="{{ $barbershop->logo_url }}" alt="{{ $barbershop->name }}" class="h-11 w-11 rounded-2xl object-cover border border-slate-700" />
                @else
                    <div class="h-11 w-11 rounded-2xl bg-brand text-slate-950 flex items-center justify-center font-black text-xl shadow-lg">
                        💈
                    </div>
                @endif
                <div>
                    <h1 class="text-base sm:text-lg font-black text-white leading-tight">{{ $barbershop->name }}</h1>
                    @if ($barbershop->city)
                        <span class="text-xs text-slate-400">📍 {{ $barbershop->city }}</span>
                    @endif
                </div>
            </div>

            @if ($barbershop->formatted_whatsapp)
                <a href="https://api.whatsapp.com/send?phone={{ $barbershop->formatted_whatsapp }}" target="_blank" class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs px-3.5 py-2 transition shadow-lg shadow-emerald-900/20">
                    <span>WhatsApp</span>
                </a>
            @endif
        </div>
    </header>

    {{-- CONTEÚDO PRINCIPAL --}}
    <main class="max-w-4xl mx-auto px-4 py-8 w-full">
        {{-- BANNER HERO --}}
        <div class="rounded-3xl border border-slate-800 bg-slate-900/60 p-6 sm:p-8 mb-8 text-center relative overflow-hidden shadow-xl">
            <div class="pointer-events-none absolute -top-24 left-1/2 -translate-x-1/2 h-48 w-96 rounded-full bg-brand/20 blur-3xl opacity-50"></div>
            
            <h2 class="text-2xl sm:text-3xl font-black text-white mb-2 relative z-10">{{ $barbershop->headline ?: 'Agende seu horário online em segundos' }}</h2>
            <p class="text-sm text-slate-400 max-w-xl mx-auto relative z-10">{{ $barbershop->description ?: 'Escolha um ou mais serviços, selecione seu barbeiro de preferência e garanta seu horário sem filas.' }}</p>
            
            @if ($barbershop->address)
                <div class="mt-4 inline-flex items-center gap-2 px-3 py-1 rounded-full bg-slate-800/80 text-xs text-slate-300">
                    <span>🏢</span>
                    <span>{{ $barbershop->address }}</span>
                </div>
            @endif
        </div>

        {{-- SEÇÃO DE PROFISSIONAIS --}}
        @if ($barbershop->professionals->isNotEmpty())
            <div class="mb-10">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-4 flex items-center gap-2">
                    <span>💈</span>
                    <span>Nossa Equipe Especializada</span>
                </h3>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    @foreach ($barbershop->professionals as $pro)
                        <div class="rounded-2xl border border-slate-800/80 bg-slate-900/40 p-4 text-center hover:border-slate-700 transition">
                            <div class="h-16 w-16 mx-auto rounded-full bg-slate-800 border-2 border-brand/50 mb-3 flex items-center justify-center font-bold text-lg text-white overflow-hidden shadow-md">
                                @if ($pro->photo_url)
                                    <img src="{{ $pro->photo_url }}" alt="{{ $pro->name }}" class="h-full w-full object-cover" />
                                @else
                                    {{ strtoupper(mb_substr($pro->name, 0, 1)) }}
                                @endif
                            </div>
                            <h4 class="text-sm font-bold text-white">{{ $pro->display_name }}</h4>
                            <span class="text-[11px] text-slate-400 block">Barbeiro Especialista</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- LISTA DE SERVIÇOS (COM SELEÇÃO MÚLTIPLA) --}}
        <div>
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 flex items-center gap-2">
                    <span>✂️</span>
                    <span>Serviços Disponíveis (Pode escolher mais de um)</span>
                </h3>
                <span class="text-xs text-brand font-semibold" x-show="selectedServices.length > 0" x-text="selectedServices.length + ' selecionado(s)'"></span>
            </div>

            <div class="space-y-3">
                @forelse ($barbershop->services as $service)
                    @php
                        $srvData = [
                            'id' => $service->id,
                            'name' => $service->name,
                            'price' => (float) $service->price,
                            'duration' => (int) $service->duration_minutes,
                        ];
                    @endphp
                    <div 
                        @click="toggleService({{ json_encode($srvData) }})"
                        :class="isServiceSelected({{ $service->id }}) ? 'border-brand bg-slate-900/90 ring-1 ring-brand' : 'border-slate-800/90 bg-slate-900/60 hover:border-slate-700'"
                        class="rounded-2xl border p-4 sm:p-5 flex items-center justify-between gap-4 transition cursor-pointer select-none"
                    >
                        <div>
                            <div class="flex items-center gap-2">
                                <span x-show="isServiceSelected({{ $service->id }})" class="text-brand font-black text-sm">✓</span>
                                <h4 class="text-base font-bold text-white">{{ $service->name }}</h4>
                            </div>
                            @if ($service->description)
                                <p class="text-xs text-slate-400 mt-0.5">{{ $service->description }}</p>
                            @endif
                            <div class="flex items-center gap-3 mt-2 text-xs text-slate-400">
                                <span>⏱️ {{ $service->duration_minutes }} minutos</span>
                                <span>&middot;</span>
                                <span class="text-base font-black text-brand">{{ $service->formatted_price }}</span>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="rounded-xl font-bold text-xs px-3.5 py-2 transition shrink-0"
                            :class="isServiceSelected({{ $service->id }}) ? 'bg-brand text-slate-950 font-black' : 'bg-slate-800 text-slate-300 hover:bg-slate-700 hover:text-white'"
                            x-text="isServiceSelected({{ $service->id }}) ? 'Adicionado ✓' : '+ Adicionar'"
                        >
                        </button>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-800 p-8 text-center text-slate-400 text-sm">
                        Nenhum serviço cadastrado no momento.
                    </div>
                @endforelse
            </div>
        </div>
    </main>

    {{-- BARRA FLUTUANTE INFERIOR (QUANDO HOUVER SERVIÇOS SELECIONADOS) --}}
    <div 
        x-show="selectedServices.length > 0 && !bookingOpen"
        x-transition:enter="transition ease-out duration-300 transform"
        x-transition:enter-start="translate-y-full opacity-0"
        x-transition:enter-end="translate-y-0 opacity-100"
        x-transition:leave="transition ease-in duration-200 transform"
        x-transition:leave-start="translate-y-0 opacity-100"
        x-transition:leave-end="translate-y-full opacity-0"
        class="fixed bottom-0 inset-x-0 z-40 bg-slate-900/95 border-t border-slate-800 backdrop-blur-lg p-4 shadow-2xl"
    >
        <div class="max-w-4xl mx-auto flex items-center justify-between gap-4">
            <div>
                <div class="text-xs text-slate-400 flex items-center gap-2">
                    <span x-text="selectedServices.length + (selectedServices.length === 1 ? ' serviço' : ' serviços')"></span>
                    <span>&middot;</span>
                    <span x-text="'⏱️ ' + totalDuration() + ' min'"></span>
                </div>
                <div class="text-lg font-black text-white">
                    <span class="text-brand" x-text="totalPriceFormatted()"></span>
                </div>
            </div>

            <button
                type="button"
                @click="openModal()"
                class="rounded-2xl bg-brand hover:brightness-110 text-slate-950 font-black text-xs uppercase tracking-wider px-6 py-3.5 transition shadow-xl shadow-amber-500/20 cursor-pointer flex items-center gap-2"
            >
                <span>Avançar para Horários</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
            </button>
        </div>
    </div>

    {{-- MODAL INTERATIVO DE AGENDAMENTO (SLOTS E CONFIRMAÇÃO) --}}
    <div x-show="bookingOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-black/85 backdrop-blur-md"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        
        <div class="bg-slate-900 border border-slate-800 rounded-3xl max-w-lg w-full p-5 sm:p-6 shadow-2xl relative max-h-[92vh] overflow-y-auto"
             @click.outside="if (!confirmedData) bookingOpen = false">
            
            {{-- TELA 1: ESCOLHA DE BARBEIRO, DATA E HORÁRIO --}}
            <template x-if="!confirmedData">
                <div>
                    <div class="flex items-center justify-between mb-4 border-b border-slate-800 pb-3">
                        <div>
                            <h3 class="text-lg font-black text-white">Confirmar Agendamento</h3>
                            <p class="text-xs text-slate-400" x-text="selectedServices.length + ' serviço(s) • ' + totalDuration() + ' min • ' + totalPriceFormatted()"></p>
                        </div>
                        <button type="button" @click="bookingOpen = false" class="text-slate-400 hover:text-white text-xl p-1 leading-none">&times;</button>
                    </div>

                    {{-- LISTA RESUMIDA DOS SERVIÇOS SELECIONADOS --}}
                    <div class="mb-4 p-3 rounded-2xl bg-slate-950 border border-slate-800 space-y-1.5">
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Serviços Inclusos:</span>
                        <template x-for="s in selectedServices" :key="s.id">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-slate-200 font-semibold" x-text="'• ' + s.name"></span>
                                <div class="flex items-center gap-2">
                                    <span class="text-slate-400" x-text="s.duration + ' min'"></span>
                                    <span class="text-brand font-bold" x-text="'R$ ' + s.price.toFixed(2).replace('.', ',')"></span>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- ALERTA DE ERRO --}}
                    <div x-show="errorMessage" x-cloak class="mb-4 p-3 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs font-semibold" x-text="errorMessage"></div>

                    {{-- PASSO 1: ESCOLHA DO BARBEIRO --}}
                    <div class="mb-5">
                        <label class="block text-xs font-bold text-slate-300 mb-2">1. Escolha o Profissional</label>
                        <div class="grid grid-cols-2 gap-2">
                            <button
                                type="button"
                                @click="setBarber('any')"
                                :class="selectedBarber === 'any' ? 'border-brand bg-brand/10 text-white font-bold ring-1 ring-brand' : 'border-slate-800 bg-slate-950 text-slate-300 hover:border-slate-700'"
                                class="p-2.5 rounded-xl border text-left text-xs transition flex items-center gap-2 cursor-pointer"
                            >
                                <span class="text-base">⚡</span>
                                <div>
                                    <span class="block font-bold">Primeiro Disponível</span>
                                    <span class="text-[10px] text-slate-400">Mais opções de horário</span>
                                </div>
                            </button>

                            @foreach ($barbershop->professionals as $pro)
                                <button
                                    type="button"
                                    @click="setBarber({ id: {{ $pro->id }}, name: '{{ addslashes($pro->display_name) }}' })"
                                    :class="selectedBarber && selectedBarber.id === {{ $pro->id }} ? 'border-brand bg-brand/10 text-white font-bold ring-1 ring-brand' : 'border-slate-800 bg-slate-950 text-slate-300 hover:border-slate-700'"
                                    class="p-2.5 rounded-xl border text-left text-xs transition flex items-center gap-2 cursor-pointer"
                                >
                                    <span class="text-base">💈</span>
                                    <div class="truncate">
                                        <span class="block font-bold truncate">{{ $pro->display_name }}</span>
                                        <span class="text-[10px] text-slate-400">Especialista</span>
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- PASSO 2: ESCOLHA DA DATA --}}
                    <div class="mb-5">
                        <label class="block text-xs font-bold text-slate-300 mb-2">2. Escolha o Dia</label>
                        <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none">
                            <template x-for="d in availableDates" :key="d.date">
                                <button
                                    type="button"
                                    @click="setDate(d.date)"
                                    :class="selectedDate === d.date ? 'border-brand bg-brand text-slate-950 font-black' : 'border-slate-800 bg-slate-950 text-slate-300 hover:border-slate-700'"
                                    class="px-3.5 py-2.5 rounded-xl border text-center transition shrink-0 cursor-pointer"
                                >
                                    <span class="block text-[10px] uppercase font-bold" x-text="d.dayName"></span>
                                    <span class="block text-base font-black leading-none my-0.5" x-text="d.dayNum"></span>
                                    <span class="block text-[9px] uppercase" x-text="d.month"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    {{-- PASSO 3: HORÁRIOS LIVRES --}}
                    <div class="mb-5">
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-bold text-slate-300">3. Horários Disponíveis</label>
                            <span class="text-[11px] text-slate-400" x-show="loadingSlots">Buscando horários...</span>
                        </div>

                        <div x-show="loadingSlots" class="p-6 text-center text-slate-400 text-xs">
                            <svg class="animate-spin h-5 w-5 mx-auto mb-2 text-brand" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span>Calculando agenda para <span x-text="totalDuration() + ' minutos'"></span>...</span>
                        </div>

                        <div x-show="!loadingSlots && availableSlots.length > 0" class="grid grid-cols-4 sm:grid-cols-5 gap-2 max-h-40 overflow-y-auto pr-1">
                            <template x-for="slot in availableSlots" :key="slot.time">
                                <button
                                    type="button"
                                    @click="selectedTime = slot.time"
                                    :class="selectedTime === slot.time ? 'border-brand bg-brand text-slate-950 font-black shadow-md' : 'border-slate-800 bg-slate-950 text-slate-200 hover:border-slate-700'"
                                    class="py-2 px-1 rounded-xl border text-xs font-semibold text-center transition cursor-pointer"
                                    x-text="slot.time"
                                >
                                </button>
                            </template>
                        </div>

                        <div x-show="!loadingSlots && availableSlots.length === 0" class="p-4 rounded-xl bg-slate-950 border border-dashed border-slate-800 text-center text-xs text-slate-400">
                            😔 Nenhum horário livre para atender <span x-text="totalDuration() + ' minutos'"></span> nesta data. Escolha outro dia.
                        </div>
                    </div>

                    {{-- PASSO 4: DADOS DO CLIENTE --}}
                    <div class="space-y-3 mb-5 border-t border-slate-800 pt-4">
                        <label class="block text-xs font-bold text-slate-300">4. Seus Dados de Atendimento</label>
                        <div>
                            <input
                                type="text"
                                x-model="clientName"
                                placeholder="Seu Nome Completo"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-brand"
                            />
                        </div>
                        <div>
                            <input
                                type="tel"
                                x-model="clientPhone"
                                placeholder="Seu WhatsApp (Ex: 85999999999)"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-brand"
                            />
                        </div>
                    </div>

                    {{-- BOTÃO FINAL --}}
                    <button
                        type="button"
                        @click="submitBooking()"
                        :disabled="isSubmitting || !selectedTime || !clientName || !clientPhone"
                        :class="isSubmitting || !selectedTime || !clientName || !clientPhone ? 'opacity-50 cursor-not-allowed bg-slate-800 text-slate-500' : 'bg-brand hover:brightness-110 text-slate-950 shadow-lg cursor-pointer'"
                        class="w-full rounded-2xl font-black py-3.5 text-xs uppercase tracking-wider transition flex items-center justify-center gap-2"
                    >
                        <span x-show="!isSubmitting">Confirmar e Garantir Horário</span>
                        <span x-show="isSubmitting" x-cloak>Gravando Agendamento...</span>
                    </button>
                </div>
            </template>

            {{-- TELA 2: COMPROVANTE DE SUCESSO --}}
            <template x-if="confirmedData">
                <div class="text-center py-4">
                    <div class="h-16 w-16 mx-auto rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center text-3xl mb-4">
                        ✓
                    </div>

                    <h3 class="text-xl font-black text-white mb-1">Agendamento Garantido!</h3>
                    <p class="text-xs text-slate-400 mb-6">Seus serviços foram reservados com sucesso na agenda.</p>

                    <div class="bg-slate-950 border border-slate-800 rounded-2xl p-4 text-left text-xs space-y-2 mb-6">
                        <div class="flex justify-between border-b border-slate-800/80 pb-2">
                            <span class="text-slate-400">Protocolo:</span>
                            <span class="font-bold text-brand" x-text="'#' + confirmedData.appointment.id"></span>
                        </div>
                        <div class="flex justify-between border-b border-slate-800/80 pb-2">
                            <span class="text-slate-400">Serviço(s):</span>
                            <span class="font-bold text-white text-right max-w-[240px]" x-text="confirmedData.appointment.services_summary"></span>
                        </div>
                        <div class="flex justify-between border-b border-slate-800/80 pb-2">
                            <span class="text-slate-400">Barbeiro:</span>
                            <span class="font-bold text-white" x-text="confirmedData.appointment.professional_name"></span>
                        </div>
                        <div class="flex justify-between border-b border-slate-800/80 pb-2">
                            <span class="text-slate-400">Data e Horário:</span>
                            <span class="font-black text-emerald-400" x-text="confirmedData.appointment.date + ' às ' + confirmedData.appointment.time"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Total a Pagar:</span>
                            <span class="font-bold text-brand" x-text="confirmedData.appointment.price"></span>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <template x-if="confirmedData.whatsapp_url">
                            <a
                                :href="confirmedData.whatsapp_url"
                                target="_blank"
                                class="w-full rounded-2xl bg-emerald-600 hover:bg-emerald-500 text-white font-black py-3.5 text-xs uppercase tracking-wider transition shadow-lg flex items-center justify-center gap-2"
                            >
                                <span>Enviar no WhatsApp da Barbearia</span>
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/>
                                </svg>
                            </a>
                        </template>

                        <button
                            type="button"
                            @click="closeAndReset()"
                            class="w-full rounded-2xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold py-3 text-xs transition cursor-pointer"
                        >
                            Novo Agendamento
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- FOOTER --}}
    <footer class="py-6 border-t border-slate-900 text-center text-xs text-slate-500">
        <p>Desenvolvido com tecnologia <a href="https://barber.aurosoft.com.br" class="text-slate-400 hover:text-white font-bold">AuroBarber</a> &middot; Gestão de Barbearias</p>
    </footer>

    {{-- SCRIPT ALPINE JS COM SUPORTE A MÚLTIPLOS SERVIÇOS --}}
    <script>
        function bookingApp(barbershopSlug) {
            return {
                slug: barbershopSlug,
                bookingOpen: false,
                selectedServices: [],
                selectedBarber: 'any',
                selectedDate: '',
                selectedTime: '',
                availableDates: [],
                availableSlots: [],
                loadingSlots: false,
                clientName: '',
                clientPhone: '',
                isSubmitting: false,
                errorMessage: '',
                confirmedData: null,

                init() {
                    this.generateNextDates();
                    this.selectedDate = this.availableDates[0].date;
                },

                generateNextDates() {
                    const days = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
                    const months = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
                    const list = [];
                    const today = new Date();

                    for (let i = 0; i < 14; i++) {
                        const d = new Date(today);
                        d.setDate(today.getDate() + i);

                        const yyyy = d.getFullYear();
                        const mm = String(d.getMonth() + 1).padStart(2, '0');
                        const dd = String(d.getDate()).padStart(2, '0');
                        const dateStr = `${yyyy}-${mm}-${dd}`;

                        list.push({
                            date: dateStr,
                            dayName: i === 0 ? 'Hoje' : (i === 1 ? 'Amanhã' : days[d.getDay()]),
                            dayNum: dd,
                            month: months[d.getMonth()]
                        });
                    }
                    this.availableDates = list;
                },

                isServiceSelected(id) {
                    return this.selectedServices.some(s => s.id === id);
                },

                toggleService(service) {
                    const index = this.selectedServices.findIndex(s => s.id === service.id);
                    if (index > -1) {
                        this.selectedServices.splice(index, 1);
                    } else {
                        this.selectedServices.push(service);
                    }
                    if (this.bookingOpen) {
                        this.fetchAvailableSlots();
                    }
                },

                totalDuration() {
                    return this.selectedServices.reduce((acc, s) => acc + s.duration, 0);
                },

                totalPriceFormatted() {
                    const total = this.selectedServices.reduce((acc, s) => acc + s.price, 0);
                    return 'R$ ' + total.toFixed(2).replace('.', ',');
                },

                openModal() {
                    if (this.selectedServices.length === 0) return;
                    this.confirmedData = null;
                    this.errorMessage = '';
                    this.selectedTime = '';
                    this.bookingOpen = true;
                    this.fetchAvailableSlots();
                },

                setBarber(barber) {
                    this.selectedBarber = barber;
                    this.selectedTime = '';
                    this.fetchAvailableSlots();
                },

                setDate(dateStr) {
                    this.selectedDate = dateStr;
                    this.selectedTime = '';
                    this.fetchAvailableSlots();
                },

                async fetchAvailableSlots() {
                    if (this.selectedServices.length === 0 || !this.selectedDate) return;
                    this.loadingSlots = true;
                    this.availableSlots = [];
                    this.errorMessage = '';

                    const barberParam = this.selectedBarber === 'any' ? 'any' : this.selectedBarber.id;
                    const serviceIdsQuery = this.selectedServices.map(s => `service_ids[]=${s.id}`).join('&');
                    const url = `/${this.slug}/slots?${serviceIdsQuery}&date=${this.selectedDate}&professional_id=${barberParam}`;

                    try {
                        const res = await fetch(url);
                        const data = await res.json();
                        this.availableSlots = data.slots || [];
                        if (this.availableSlots.length > 0) {
                            this.selectedTime = this.availableSlots[0].time;
                        }
                    } catch (e) {
                        this.errorMessage = 'Erro ao carregar horários livres. Tente novamente.';
                    } finally {
                        this.loadingSlots = false;
                    }
                },

                async submitBooking() {
                    this.errorMessage = '';
                    if (!this.clientName || !this.clientPhone || !this.selectedTime) {
                        this.errorMessage = 'Por favor, informe seu nome e WhatsApp para confirmar.';
                        return;
                    }

                    this.isSubmitting = true;
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                    const payload = {
                        service_ids: this.selectedServices.map(s => s.id),
                        professional_id: this.selectedBarber === 'any' ? null : this.selectedBarber.id,
                        date: this.selectedDate,
                        time: this.selectedTime,
                        client_name: this.clientName,
                        client_phone: this.clientPhone,
                    };

                    try {
                        const res = await fetch(`/${this.slug}/book`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken || '',
                            },
                            body: JSON.stringify(payload),
                        });

                        const data = await res.json();

                        if (!res.ok) {
                            this.errorMessage = data.message || 'Erro ao realizar o agendamento. Escolha outro horário.';
                            this.fetchAvailableSlots();
                            return;
                        }

                        this.confirmedData = data;
                    } catch (e) {
                        this.errorMessage = 'Falha na conexão com o servidor. Tente novamente.';
                    } finally {
                        this.isSubmitting = false;
                    }
                },

                closeAndReset() {
                    this.bookingOpen = false;
                    this.confirmedData = null;
                    this.selectedServices = [];
                    this.selectedTime = '';
                }
            };
        }
    </script>
</body>
</html>

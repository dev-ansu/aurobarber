<x-filament-panels::page>
    @php
        $carbonDate = \Carbon\Carbon::parse($selectedDate)->locale('pt_BR');
        $dateFormatted = $carbonDate->translatedFormat('l, d \d\e F \d\e Y');
        $isToday = $selectedDate === now()->format('Y-m-d');
        $stats = $this->dayStats;
    @endphp

    {{-- BARRA SUPERIOR DE FILTRO DE DATA & MÉTRICAS DO DIA --}}
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4 sm:p-5 backdrop-blur-sm space-y-4">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            {{-- SELETOR DE DATAS --}}
            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    wire:click="previousDay"
                    class="p-2 rounded-xl border border-slate-700 bg-slate-800 text-slate-300 hover:text-white hover:bg-slate-700 transition cursor-pointer"
                    title="Dia Anterior"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>

                <button
                    type="button"
                    wire:click="setToday"
                    class="px-3.5 py-1.5 rounded-xl border text-xs font-bold transition cursor-pointer {{ $isToday ? 'border-amber-500 bg-amber-500/10 text-amber-400' : 'border-slate-700 bg-slate-800 text-slate-300 hover:text-white' }}"
                >
                    Hoje
                </button>

                <button
                    type="button"
                    wire:click="nextDay"
                    class="p-2 rounded-xl border border-slate-700 bg-slate-800 text-slate-300 hover:text-white hover:bg-slate-700 transition cursor-pointer"
                    title="Próximo Dia"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>

                <input
                    type="date"
                    wire:model.live="selectedDate"
                    class="bg-slate-950 border border-slate-700 rounded-xl px-3 py-1.5 text-xs text-white focus:outline-none focus:border-amber-500 cursor-pointer"
                />

                <span class="text-sm font-bold text-white capitalize hidden sm:inline-block ml-2">
                    📅 {{ $dateFormatted }}
                </span>
            </div>

            {{-- BOTÃO DE NOVO ENCAIXE RÁPIDO --}}
            <div>
                <button
                    type="button"
                    wire:click="openQuickModal()"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-black px-4 py-2 text-xs uppercase tracking-wider transition shadow-lg shadow-amber-500/20 cursor-pointer"
                >
                    <span>+ Novo Encaixe Rápido</span>
                </button>
            </div>
        </div>

        {{-- MÉTRICAS RÁPIDAS DO DIA --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-2 border-t border-slate-800">
            <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800/80">
                <span class="text-[11px] text-slate-400 block font-semibold">Total Agendado</span>
                <span class="text-lg font-black text-white">{{ $stats['total_count'] }} <span class="text-xs font-normal text-slate-400">clientes</span></span>
            </div>

            <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800/80">
                <span class="text-[11px] text-amber-400 block font-semibold">Em Atendimento</span>
                <span class="text-lg font-black text-amber-400">{{ $stats['in_progress_count'] }} <span class="text-xs font-normal text-slate-400">na cadeira</span></span>
            </div>

            <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800/80">
                <span class="text-[11px] text-emerald-400 block font-semibold">Concluídos</span>
                <span class="text-lg font-black text-emerald-400">{{ $stats['completed_count'] }} <span class="text-xs font-normal text-slate-400">finalizados</span></span>
            </div>

            <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800/80">
                <span class="text-[11px] text-slate-400 block font-semibold">Faturamento Previsto</span>
                <span class="text-lg font-black text-white">R$ {{ number_format($stats['total_revenue'], 2, ',', '.') }}</span>
            </div>
        </div>
    </div>

    {{-- GRADE EM COLUNAS: UM BARBEIRO POR COLUNA --}}
    @php
        $professionals = $this->professionals;
        $byBarber = $this->appointmentsByBarber;
    @endphp

    @if ($professionals->isEmpty())
        <div class="p-12 text-center rounded-2xl border border-dashed border-slate-800 text-slate-400 text-sm">
            Nenhum barbeiro ativo cadastrado nesta unidade.
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 items-start">
            @foreach ($professionals as $pro)
                @php
                    $appts = $byBarber[$pro->id] ?? collect();
                    $barberRevenue = $appts->where('status', '!=', 'cancelled')->sum('service_price');
                @endphp

                <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4 space-y-3">
                    {{-- CABEÇALHO DA COLUNA DO BARBEIRO --}}
                    <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                        <div class="flex items-center gap-2.5">
                            <div class="h-10 w-10 rounded-full bg-slate-800 border-2 border-amber-500/50 flex items-center justify-center font-bold text-sm text-white overflow-hidden">
                                @if ($pro->photo_url)
                                    <img src="{{ $pro->photo_url }}" alt="{{ $pro->name }}" class="h-full w-full object-cover" />
                                @else
                                    {{ strtoupper(mb_substr($pro->name, 0, 1)) }}
                                @endif
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-white leading-tight">{{ $pro->display_name }}</h3>
                                <span class="text-[10px] text-slate-400 block">{{ $appts->count() }} corte(s) &middot; R$ {{ number_format($barberRevenue, 2, ',', '.') }}</span>
                            </div>
                        </div>

                        <button
                            type="button"
                            wire:click="openQuickModal({{ $pro->id }})"
                            class="p-1.5 rounded-lg border border-slate-700 bg-slate-800 text-slate-300 hover:text-white hover:border-amber-500 transition cursor-pointer"
                            title="Adicionar encaixe para este barbeiro"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                        </button>
                    </div>

                    {{-- CARDS DE AGENDAMENTOS DO BARBEIRO --}}
                    <div class="space-y-3 min-h-[300px]">
                        @forelse ($appts as $appt)
                            @php
                                $statusColors = [
                                    'scheduled' => 'border-amber-500/40 bg-amber-500/5 text-amber-400',
                                    'confirmed' => 'border-sky-500/40 bg-sky-500/5 text-sky-400',
                                    'in_progress' => 'border-purple-500/50 bg-purple-500/10 text-purple-300',
                                    'completed' => 'border-emerald-500/40 bg-emerald-500/5 text-emerald-400',
                                    'cancelled' => 'border-slate-800 bg-slate-950/40 text-slate-500 line-through',
                                    'no_show' => 'border-rose-500/40 bg-rose-500/5 text-rose-400',
                                ];
                                $statusClass = $statusColors[$appt->status] ?? 'border-slate-800 bg-slate-950 text-slate-400';
                            @endphp

                            <div class="rounded-xl border border-slate-800/90 bg-slate-950/80 p-3.5 space-y-2 hover:border-slate-700 transition">
                                {{-- LINHA 1: HORÁRIO & STATUS --}}
                                <div class="flex items-center justify-between text-xs">
                                    <div class="flex items-center gap-1.5 font-black text-white">
                                        <span>⏰ {{ $appt->scheduled_at->format('H:i') }}</span>
                                        <span class="text-slate-500 text-[10px]">até {{ $appt->ends_at->format('H:i') }}</span>
                                    </div>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase border {{ $statusClass }}">
                                        {{ $appt->status_label }}
                                    </span>
                                </div>

                                {{-- LINHA 2: CLIENTE E WHATSAPP --}}
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-sm font-bold text-white truncate">{{ $appt->client_name }}</span>
                                    @if ($appt->client_phone)
                                        @php
                                            $cleanPhone = preg_replace('/\D/', '', $appt->client_phone);
                                            $phone55 = str_starts_with($cleanPhone, '55') ? $cleanPhone : '55' . $cleanPhone;
                                        @endphp
                                        <a
                                            href="https://api.whatsapp.com/send?phone={{ $phone55 }}"
                                            target="_blank"
                                            class="p-1 rounded-lg bg-emerald-600/20 text-emerald-400 hover:bg-emerald-600 hover:text-white transition"
                                            title="Chamar no WhatsApp"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/>
                                            </svg>
                                        </a>
                                    @endif
                                </div>

                                {{-- LINHA 3: SERVIÇOS & VALOR --}}
                                <div class="flex items-center justify-between text-xs text-slate-400 pt-1 border-t border-slate-900">
                                    <span class="truncate max-w-[170px]" title="{{ $appt->services_summary }}">✂️ {{ $appt->services_summary }}</span>
                                    <span class="font-bold text-amber-500">R$ {{ number_format($appt->service_price, 2, ',', '.') }}</span>
                                </div>

                                {{-- LINHA 4: BOTÕES DE AÇÃO RÁPIDA --}}
                                @if (!in_array($appt->status, ['completed', 'cancelled']))
                                    <div class="grid grid-cols-3 gap-1.5 pt-2 border-t border-slate-900 text-[10px]">
                                        @if ($appt->status !== 'in_progress')
                                            <button
                                                type="button"
                                                wire:click="updateStatus({{ $appt->id }}, 'in_progress')"
                                                class="py-1 px-1 rounded-lg bg-purple-500/10 hover:bg-purple-500/20 text-purple-400 font-bold border border-purple-500/30 transition text-center cursor-pointer"
                                            >
                                                ▶️ Atender
                                            </button>
                                        @else
                                            <span class="py-1 px-1 rounded-lg bg-purple-500/20 text-purple-300 font-bold text-center">
                                                Em Corte
                                            </span>
                                        @endif

                                        <button
                                            type="button"
                                            wire:click="updateStatus({{ $appt->id }}, 'completed')"
                                            class="py-1 px-1 rounded-lg bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 font-bold border border-emerald-500/30 transition text-center cursor-pointer"
                                        >
                                            ✅ Concluir
                                        </button>

                                        <button
                                            type="button"
                                            wire:click="updateStatus({{ $appt->id }}, 'no_show')"
                                            class="py-1 px-1 rounded-lg bg-slate-800 hover:bg-rose-500/20 text-slate-400 hover:text-rose-400 transition text-center cursor-pointer"
                                        >
                                            🚫 Faltou
                                        </button>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="py-12 text-center rounded-xl border border-dashed border-slate-800/80 p-4 text-xs text-slate-500 space-y-2">
                                <p>Sem agendamentos para este barbeiro.</p>
                                <button
                                    type="button"
                                    wire:click="openQuickModal({{ $pro->id }})"
                                    class="inline-block text-[11px] text-amber-500 hover:text-amber-400 font-bold underline cursor-pointer"
                                >
                                    + Encaixar cliente
                                </button>
                            </div>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- MODAL DE NOVO ENCAIXE RÁPIDO (COM ABAS E CAMPOS EM LARGURA TOTAL) --}}
    @if ($quickModalOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
            <div class="bg-slate-900 border border-slate-800 rounded-3xl max-w-md w-full p-6 shadow-2xl relative">
                <div class="flex items-center justify-between mb-4 border-b border-slate-800 pb-3">
                    <h3 class="text-base font-black text-white">Novo Encaixe no Balcão</h3>
                    <button type="button" wire:click="$set('quickModalOpen', false)" class="text-slate-400 hover:text-white text-xl p-1 leading-none cursor-pointer">&times;</button>
                </div>

                {{-- ABAS DO FORMULÁRIO --}}
                <div class="flex border-b border-slate-800 mb-4">
                    <button
                        type="button"
                        wire:click="$set('activeModalTab', 'atendimento')"
                        class="flex-1 pb-2 text-xs font-bold transition border-b-2 cursor-pointer {{ $activeModalTab === 'atendimento' ? 'border-amber-500 text-amber-400' : 'border-transparent text-slate-400 hover:text-white' }}"
                    >
                        ✂️ Atendimento
                    </button>
                    <button
                        type="button"
                        wire:click="$set('activeModalTab', 'cliente')"
                        class="flex-1 pb-2 text-xs font-bold transition border-b-2 cursor-pointer {{ $activeModalTab === 'cliente' ? 'border-amber-500 text-amber-400' : 'border-transparent text-slate-400 hover:text-white' }}"
                    >
                        👤 Cliente
                    </button>
                </div>

                <form wire:submit.prevent="saveQuickAppointment" class="space-y-4">
                    {{-- ABA 1: ATENDIMENTO (TODOS OS CAMPOS EM LARGURA TOTAL) --}}
                    <div class="{{ $activeModalTab === 'atendimento' ? 'space-y-4' : 'hidden' }}">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Barbeiro Responsável</label>
                            <select wire:model="quickProfessionalId" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500">
                                @foreach ($professionals as $p)
                                    <option value="{{ $p->id }}">{{ $p->display_name }}</option>
                                @endforeach
                            </select>
                            @error('quickProfessionalId') <span class="text-rose-400 text-[10px]">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Serviço</label>
                            <select wire:model="quickServiceId" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500">
                                @foreach ($this->services as $s)
                                    <option value="{{ $s->id }}">{{ $s->name }} — R$ {{ number_format($s->price, 2, ',', '.') }} ({{ $s->duration_minutes }} min)</option>
                                @endforeach
                            </select>
                            @error('quickServiceId') <span class="text-rose-400 text-[10px]">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Horário de Início</label>
                            <input type="time" wire:model="quickTime" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500" />
                            @error('quickTime') <span class="text-rose-400 text-[10px]">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    {{-- ABA 2: DADOS DO CLIENTE (TODOS OS CAMPOS EM LARGURA TOTAL) --}}
                    <div class="{{ $activeModalTab === 'cliente' ? 'space-y-4' : 'hidden' }}">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Nome do Cliente</label>
                            <input type="text" wire:model="quickClientName" placeholder="Ex: Lucas Gabriel" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-amber-500" />
                            @error('quickClientName') <span class="text-rose-400 text-[10px]">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">WhatsApp do Cliente</label>
                            <input type="tel" wire:model="quickClientPhone" placeholder="Ex: 85999999999" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-amber-500" />
                            @error('quickClientPhone') <span class="text-rose-400 text-[10px]">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    {{-- AÇÕES DO MODAL --}}
                    <div class="pt-2 flex items-center justify-between gap-3 border-t border-slate-800">
                        @if ($activeModalTab === 'atendimento')
                            <button type="button" wire:click="$set('activeModalTab', 'cliente')" class="w-full py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs uppercase tracking-wider transition cursor-pointer">
                                Próximo: Dados do Cliente ➔
                            </button>
                        @else
                            <button type="button" wire:click="$set('activeModalTab', 'atendimento')" class="py-2.5 px-4 rounded-xl bg-slate-800 text-slate-300 text-xs font-bold transition cursor-pointer">
                                ⬅ Voltar
                            </button>
                            <button type="submit" class="flex-1 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs uppercase tracking-wider transition cursor-pointer shadow-lg shadow-emerald-900/20">
                                Salvar Encaixe ✓
                            </button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    @endif
</x-filament-panels::page>

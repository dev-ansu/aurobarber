@php
    $shouldShow = auth()->check()
        && ! request()->routeIs('filament.*.auth.*')
        && ! request()->is('*login*')
        && ! request()->is('*password-reset*')
        && ! request()->is('*kiosk*');

    $school = null;
    if ($shouldShow) {
        try {
            $school = \Filament\Facades\Filament::getTenant()
                ?? (auth()->check() ? auth()->user()->schools()->first() : null)
                ?? (session()->has('current_school_id') ? \App\Models\School::find(session('current_school_id')) : null)
                ?? \App\Models\School::first();
        } catch (\Throwable $e) {
            $school = null;
        }
    }

    $systemVersion = \App\Services\SystemUpdateService::CURRENT_VERSION ?? '1.6.5';
@endphp

@if ($shouldShow)
    {{-- Espaçador para o conteúdo da página não ficar sob a barra fixa --}}
    <style>
        body, .fi-main, main, .fi-body {
            padding-bottom: 3.5rem !important;
        }

        .windows-calendar-grid {
            display: grid !important;
            grid-template-columns: repeat(7, 1fr) !important;
            gap: 3px !important;
            text-align: center !important;
        }

        .windows-day-cell {
            width: 34px !important;
            height: 34px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            margin: 0 auto !important;
            border-radius: 8px !important;
            font-size: 11px !important;
        }
    </style>

    <div x-data="windowsDesktopTray()" x-init="initClock()">
        {{-- Flyout do Calendário Estilo Quadrado do Windows Desktop (Fora do backdrop-blur para viewport fixo perfeito) --}}
        <div
            x-show="openCalendar"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-4 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 scale-95"
            @click.outside="openCalendar = false"
            @keydown.escape.window="openCalendar = false"
            class="rounded-2xl bg-slate-900/98 dark:bg-slate-950/98 border border-slate-700/80 shadow-2xl shadow-black/90 p-3.5 text-white"
            style="position: fixed !important; bottom: 50px !important; right: 16px !important; width: 320px !important; max-height: calc(100vh - 60px) !important; overflow-y: auto !important; z-index: 99999 !important; box-sizing: border-box; display: none;"
        >
            {{-- Cabeçalho: Hora Digital Grande e Data Completa --}}
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <div>
                    <div class="text-2xl font-mono font-black tracking-tight text-white" x-text="timeString">20:00:00</div>
                    <div class="text-xs font-medium text-amber-400 capitalize" x-text="fullDateString">Sábado, 7 de Novembro</div>
                </div>
                <button
                    type="button"
                    @click="openCalendar = false"
                    class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition cursor-pointer"
                    title="Fechar calendário"
                >
                    <x-heroicon-m-x-mark class="h-4 w-4" />
                </button>
            </div>

            {{-- Navegação do Mês --}}
            <div class="flex items-center justify-between py-2 text-xs font-bold text-slate-200">
                <button
                    type="button"
                    @click="prevMonth()"
                    class="p-1.5 rounded-lg hover:bg-slate-800 text-slate-400 hover:text-white transition cursor-pointer"
                    title="Mês anterior"
                >
                    <x-heroicon-m-chevron-left class="h-4 w-4" />
                </button>
                <span class="capitalize text-sm font-semibold text-white tracking-wide" x-text="monthYearTitle"></span>
                <button
                    type="button"
                    @click="nextMonth()"
                    class="p-1.5 rounded-lg hover:bg-slate-800 text-slate-400 hover:text-white transition cursor-pointer"
                    title="Próximo mês"
                >
                    <x-heroicon-m-chevron-right class="h-4 w-4" />
                </button>
            </div>

            {{-- Dias da Semana (7 colunas exatas) --}}
            <div class="windows-calendar-grid text-[11px] font-bold text-slate-400 py-1 mb-1">
                <span>Dom</span>
                <span>Seg</span>
                <span>Ter</span>
                <span>Qua</span>
                <span>Qui</span>
                <span>Sex</span>
                <span>Sáb</span>
            </div>

            {{-- Grade Quadrada de Dias do Mês (7 colunas exatas) --}}
            <div class="windows-calendar-grid">
                <template x-for="item in calendarGrid" :key="item.key">
                    <button
                        type="button"
                        @click="selectDay(item)"
                        class="windows-day-cell transition duration-150 cursor-pointer"
                        :class="{
                            'text-slate-600': !item.currentMonth,
                            'text-slate-300 hover:bg-slate-800 hover:text-white': item.currentMonth && !item.isToday && !item.isSelected,
                            'bg-amber-500 text-slate-950 font-black shadow-lg shadow-amber-500/40 scale-105': item.isToday,
                            'ring-2 ring-amber-400 text-white font-bold bg-slate-800': item.isSelected && !item.isToday
                        }"
                        x-text="item.day"
                    ></button>
                </template>
            </div>

            {{-- Rodapé do Calendário: Botão "Hoje" e Detalhe --}}
            <div class="mt-3 pt-2.5 border-t border-slate-800 flex items-center justify-between text-[11px]">
                <button
                    type="button"
                    @click="goToday()"
                    class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-amber-400 font-semibold transition border border-slate-700/60 cursor-pointer"
                >
                    Voltar para Hoje
                </button>
                <span class="text-slate-500 font-mono text-[10px]">Aurosoft Desktop OS</span>
            </div>
        </div>

        {{-- Barra de Tarefas Fixa na Base da Tela --}}
        <div
            class="fixed bottom-0 inset-x-0 w-full z-40 h-10 bg-slate-900/95 dark:bg-slate-950/98 backdrop-blur-md border-t border-slate-800 text-slate-300 text-xs px-4 select-none flex items-center justify-between shadow-[0_-4px_25px_rgba(0,0,0,0.35)]"
            style="height: 40px;"
        >
            {{-- Lado Esquerdo: Marca Padrão Aurosoft com o "A" Dourado e Versão --}}
            <div class="flex items-center gap-2.5">
                <div class="flex items-center gap-2 select-none">
                    <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-gradient-to-tr from-amber-500 via-amber-400 to-amber-300 text-slate-950 font-black shadow-md shadow-amber-500/20 text-xs select-none">
                        A
                    </div>
                    <div class="flex items-center gap-1.5 leading-none">
                        <span class="text-xs font-black tracking-wider uppercase text-white">Aurosoft</span>
                        <span class="text-[9px] font-semibold text-amber-400 hidden sm:inline">Gestão & LMS</span>
                    </div>
                </div>

                {{-- Badge da Versão --}}
                <span class="px-1.5 py-0.5 rounded text-[10px] font-mono font-semibold bg-slate-800 text-slate-400 border border-slate-700/80">
                    v{{ $systemVersion }}
                </span>

                {{-- Indicador de Nuvem Conectada --}}
                <div class="hidden lg:flex items-center gap-1.5 ml-1 pl-2 border-l border-slate-800 text-[10px] text-slate-400" title="Ambiente Nuvem Aurosoft Ativo e Sincronizado">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                    <span>Nuvem Ativa</span>
                </div>
            </div>

            {{-- Lado Direito: Unidade Escolar + Bandeja do Windows (Relógio Digital + Botão Calendário) --}}
            <div class="flex items-center gap-2.5">
                @if ($school)
                    <div class="flex items-center gap-2 px-2.5 py-1 rounded-full bg-slate-800/80 border border-slate-700/70 text-xs text-slate-200 shadow-inner">
                        @if ($school->logo_url)
                            <img src="{{ $school->logo_url }}" alt="Logo" class="h-4 w-auto object-contain rounded" />
                        @else
                            <span class="text-amber-400">🏫</span>
                        @endif
                        <span class="font-semibold truncate max-w-[240px]">{{ $school->trade_name ?: $school->legal_name }}</span>
                    </div>
                @endif

                <button
                    type="button"
                    @click="openCalendar = !openCalendar"
                    class="flex items-center gap-2 px-2.5 py-1 rounded-lg transition-all duration-150 hover:bg-white/10 focus:outline-none focus:ring-1 focus:ring-amber-500/50 cursor-pointer"
                    :class="{ 'bg-white/10 ring-1 ring-amber-500/40 shadow-inner': openCalendar }"
                    title="Clique para abrir o calendário"
                >
                    <div class="flex flex-col text-right leading-tight">
                        <span class="font-mono text-xs font-bold text-white tracking-wide" x-text="timeString">--:--:--</span>
                        <span class="text-[9px] text-slate-400 tracking-tight" x-text="dateShortString">--/--/----</span>
                    </div>
                    <div class="p-1 rounded bg-slate-800/80 text-amber-400 border border-slate-700/60 shadow-sm">
                        <x-heroicon-m-calendar-days class="h-3.5 w-3.5" />
                    </div>
                </button>
            </div>
        </div>
    </div>

    {{-- Script de lógica do Relógio e Calendário em Alpine.js --}}
    <script>
        function windowsDesktopTray() {
            return {
                openCalendar: false,
                timeString: '--:--:--',
                dateShortString: '--/--/----',
                fullDateString: '',
                currentYear: new Date().getFullYear(),
                currentMonth: new Date().getMonth(),
                selectedDay: null,
                monthNames: [
                    'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
                    'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'
                ],

                initClock() {
                    this.updateTime();
                    setInterval(() => this.updateTime(), 1000);
                },

                updateTime() {
                    const now = new Date();
                    const hours = String(now.getHours()).padStart(2, '0');
                    const minutes = String(now.getMinutes()).padStart(2, '0');
                    const seconds = String(now.getSeconds()).padStart(2, '0');
                    this.timeString = `${hours}:${minutes}:${seconds}`;

                    const day = String(now.getDate()).padStart(2, '0');
                    const month = String(now.getMonth() + 1).padStart(2, '0');
                    const year = now.getFullYear();
                    this.dateShortString = `${day}/${month}/${year}`;

                    const weekdayOptions = { weekday: 'long', day: 'numeric', month: 'long' };
                    this.fullDateString = now.toLocaleDateString('pt-BR', weekdayOptions);
                },

                get monthYearTitle() {
                    return `${this.monthNames[this.currentMonth]} de ${this.currentYear}`;
                },

                get calendarGrid() {
                    const days = [];
                    const today = new Date();
                    const firstDayIndex = new Date(this.currentYear, this.currentMonth, 1).getDay();
                    const daysInCurrentMonth = new Date(this.currentYear, this.currentMonth + 1, 0).getDate();
                    const daysInPrevMonth = new Date(this.currentYear, this.currentMonth, 0).getDate();

                    // Dias do mês anterior
                    for (let i = firstDayIndex - 1; i >= 0; i--) {
                        const dayNum = daysInPrevMonth - i;
                        days.push({
                            key: `prev-${dayNum}`,
                            day: dayNum,
                            currentMonth: false,
                            isToday: false,
                            isSelected: false
                        });
                    }

                    // Dias do mês atual
                    for (let i = 1; i <= daysInCurrentMonth; i++) {
                        const isToday = today.getDate() === i
                            && today.getMonth() === this.currentMonth
                            && today.getFullYear() === this.currentYear;

                        const isSelected = this.selectedDay === i;

                        days.push({
                            key: `curr-${i}`,
                            day: i,
                            currentMonth: true,
                            isToday: isToday,
                            isSelected: isSelected
                        });
                    }

                    // Dias do próximo mês para completar 35 ou 42 células
                    const totalCells = days.length > 35 ? 42 : 35;
                    const nextDays = totalCells - days.length;
                    for (let i = 1; i <= nextDays; i++) {
                        days.push({
                            key: `next-${i}`,
                            day: i,
                            currentMonth: false,
                            isToday: false,
                            isSelected: false
                        });
                    }

                    return days;
                },

                prevMonth() {
                    if (this.currentMonth === 0) {
                        this.currentMonth = 11;
                        this.currentYear--;
                    } else {
                        this.currentMonth--;
                    }
                    this.selectedDay = null;
                },

                nextMonth() {
                    if (this.currentMonth === 11) {
                        this.currentMonth = 0;
                        this.currentYear++;
                    } else {
                        this.currentMonth++;
                    }
                    this.selectedDay = null;
                },

                goToday() {
                    const today = new Date();
                    this.currentYear = today.getFullYear();
                    this.currentMonth = today.getMonth();
                    this.selectedDay = today.getDate();
                },

                selectDay(item) {
                    if (item.currentMonth) {
                        this.selectedDay = item.day;
                    }
                }
            };
        }
    </script>
@endif

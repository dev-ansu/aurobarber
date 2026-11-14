<x-filament-panels::page>
    @php
        $carbonDate = \Carbon\Carbon::parse($selectedDate)->locale('pt_BR');
        $dateFormatted = $carbonDate->translatedFormat('l, d \d\e F \d\e Y');
        $isToday = $selectedDate === now()->format('Y-m-d');
        $stats = $this->dayStats;
        $professionals = $this->professionals;
        $byBarber = $this->appointmentsByBarber;
    @endphp

    <style>
        .ab-wrap {
            display: flex;
            flex-direction: column;
            gap: 24px;
            color: #f1f5f9;
            font-family: inherit;
        }

        /* 1. TOP TOOLBAR */
        .ab-toolbar {
            background: linear-gradient(180deg, #0f172a 0%, #0b1120 100%);
            border: 1px solid #1e293b;
            border-radius: 20px;
            padding: 20px 24px;
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.5);
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .ab-toolbar-header {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .ab-date-nav {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .ab-nav-btn {
            height: 38px;
            width: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: #1e293b;
            border: 1px solid #334155;
            color: #cbd5e1;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .ab-nav-btn:hover {
            background: #334155;
            color: #ffffff;
            border-color: #475569;
        }

        .ab-nav-today {
            height: 38px;
            padding: 0 18px;
            font-size: 13px;
            font-weight: 800;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.15s ease;
            display: inline-flex;
            align-items: center;
        }

        .ab-nav-today.is-active {
            background: rgba(217, 119, 6, 0.15);
            border: 1px solid #d97706;
            color: #fbbf24;
        }

        .ab-nav-today.is-inactive {
            background: #1e293b;
            border: 1px solid #334155;
            color: #94a3b8;
        }

        .ab-nav-today.is-inactive:hover {
            background: #334155;
            color: #ffffff;
        }

        .ab-date-picker {
            height: 38px;
            background: #020617;
            border: 1px solid #334155;
            border-radius: 12px;
            color: #ffffff;
            padding: 0 14px;
            font-size: 13px;
            font-weight: 700;
            outline: none;
            cursor: pointer;
        }

        .ab-date-picker:focus {
            border-color: #d97706;
        }

        .ab-date-title {
            font-size: 14px;
            font-weight: 800;
            color: #ffffff;
            text-transform: capitalize;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-left: 6px;
        }

        .ab-btn-cta {
            background: #d97706;
            color: #020617;
            font-weight: 900;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 11px 22px;
            border-radius: 14px;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 16px rgba(217, 119, 6, 0.35);
            transition: all 0.15s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .ab-btn-cta:hover {
            background: #f59e0b;
            transform: translateY(-1px);
        }

        /* 2. STATS METRICS GRID */
        .ab-stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
            padding-top: 18px;
            border-top: 1px solid #1e293b;
        }

        @media (min-width: 768px) {
            .ab-stats-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        .ab-stat-box {
            background: #020617;
            border: 1px solid #1e293b;
            border-radius: 14px;
            padding: 14px 18px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .ab-stat-label {
            font-size: 11px;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .ab-stat-val {
            font-size: 20px;
            font-weight: 900;
            color: #ffffff;
        }

        .ab-stat-val-amber { color: #fbbf24; }
        .ab-stat-val-emerald { color: #34d399; }

        .ab-stat-sub {
            font-size: 11px;
            color: #64748b;
            font-weight: 500;
        }

        /* 3. KANBAN BOARD */
        .ab-board-columns {
            display: grid;
            grid-template-columns: repeat(1, 1fr);
            gap: 20px;
            align-items: start;
        }

        @media (min-width: 640px) {
            .ab-board-columns {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (min-width: 1024px) {
            .ab-board-columns {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (min-width: 1400px) {
            .ab-board-columns {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        .ab-col {
            background: #0b1120;
            border: 1px solid #1e293b;
            border-radius: 20px;
            padding: 18px;
            display: flex;
            flex-direction: column;
            gap: 16px;
            box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.4);
        }

        .ab-col-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 14px;
            border-bottom: 1px solid #1e293b;
        }

        .ab-barber-details {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .ab-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: #1e293b;
            border: 2px solid #d97706;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 15px;
            color: #ffffff;
            overflow: hidden;
            flex-shrink: 0;
        }

        .ab-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .ab-barber-name {
            font-size: 15px;
            font-weight: 800;
            color: #ffffff;
            line-height: 1.2;
        }

        .ab-barber-summary {
            font-size: 11px;
            color: #94a3b8;
            margin-top: 3px;
        }

        .ab-col-add {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: #1e293b;
            border: 1px solid #334155;
            color: #cbd5e1;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .ab-col-add:hover {
            background: #d97706;
            border-color: #d97706;
            color: #020617;
        }

        /* 4. APPOINTMENT CARD */
        .ab-card-stack {
            display: flex;
            flex-direction: column;
            gap: 14px;
            min-height: 220px;
        }

        .ab-card {
            background: #020617;
            border: 1px solid #1e293b;
            border-radius: 16px;
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            transition: all 0.15s ease;
            position: relative;
        }

        .ab-card:hover {
            border-color: #334155;
            box-shadow: 0 8px 20px -4px rgba(0, 0, 0, 0.5);
            transform: translateY(-1px);
        }

        .ab-card-row1 {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .ab-time-badge {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
            font-weight: 900;
            color: #ffffff;
        }

        .ab-time-badge small {
            font-size: 11px;
            font-weight: 600;
            color: #64748b;
        }

        .ab-pill {
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 3px 9px;
            border-radius: 9999px;
            border: 1px solid transparent;
        }

        .ab-pill-scheduled { background: rgba(217, 119, 6, 0.12); border-color: rgba(217, 119, 6, 0.4); color: #fbbf24; }
        .ab-pill-confirmed { background: rgba(14, 165, 233, 0.12); border-color: rgba(14, 165, 233, 0.4); color: #38bdf8; }
        .ab-pill-in_progress { background: rgba(168, 85, 247, 0.18); border-color: rgba(168, 85, 247, 0.5); color: #d8b4fe; }
        .ab-pill-completed { background: rgba(16, 185, 129, 0.12); border-color: rgba(16, 185, 129, 0.4); color: #34d399; }
        .ab-pill-no_show { background: rgba(244, 63, 94, 0.12); border-color: rgba(244, 63, 94, 0.4); color: #fb7185; }
        .ab-pill-cancelled { background: rgba(51, 65, 85, 0.2); border-color: #334155; color: #64748b; text-decoration: line-through; }

        .ab-client-info {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }

        .ab-client-name {
            font-size: 14px;
            font-weight: 800;
            color: #ffffff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .ab-wa-btn {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.15s ease;
            flex-shrink: 0;
        }

        .ab-wa-btn:hover {
            background: #10b981;
            color: #ffffff;
        }

        .ab-service-info {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 12px;
            color: #94a3b8;
            padding-top: 8px;
            border-top: 1px solid #0f172a;
        }

        .ab-service-names {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 180px;
        }

        .ab-service-price {
            font-weight: 900;
            color: #fbbf24;
        }

        .ab-card-actions {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 6px;
            padding-top: 10px;
            border-top: 1px solid #0f172a;
        }

        .ab-action-btn {
            padding: 7px 4px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
            text-align: center;
            transition: all 0.15s ease;
            border: 1px solid transparent;
        }

        .ab-btn-start {
            background: rgba(168, 85, 247, 0.12);
            border-color: rgba(168, 85, 247, 0.35);
            color: #d8b4fe;
        }

        .ab-btn-start:hover {
            background: rgba(168, 85, 247, 0.25);
        }

        .ab-btn-finish {
            background: rgba(16, 185, 129, 0.12);
            border-color: rgba(16, 185, 129, 0.35);
            color: #34d399;
        }

        .ab-btn-finish:hover {
            background: rgba(16, 185, 129, 0.25);
        }

        .ab-btn-noshow {
            background: #1e293b;
            border-color: #334155;
            color: #94a3b8;
        }

        .ab-btn-noshow:hover {
            background: rgba(244, 63, 94, 0.2);
            border-color: rgba(244, 63, 94, 0.4);
            color: #fb7185;
        }

        .ab-empty-state {
            padding: 40px 16px;
            text-align: center;
            border: 1px dashed #1e293b;
            border-radius: 16px;
            color: #64748b;
            font-size: 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        .ab-empty-btn {
            color: #d97706;
            font-weight: 800;
            background: transparent;
            border: none;
            cursor: pointer;
            text-decoration: underline;
            font-size: 12px;
        }

        /* 5. MODAL */
        .ab-modal-mask {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(8px);
            z-index: 99999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }

        .ab-modal-window {
            background: #0f172a;
            border: 1px solid #1e293b;
            border-radius: 24px;
            max-width: 480px;
            width: 100%;
            padding: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.8);
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .ab-modal-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 14px;
            border-bottom: 1px solid #1e293b;
        }

        .ab-modal-head h3 {
            font-size: 17px;
            font-weight: 900;
            color: #ffffff;
            margin: 0;
        }

        .ab-modal-x {
            background: transparent;
            border: none;
            color: #94a3b8;
            font-size: 26px;
            cursor: pointer;
            line-height: 1;
        }

        .ab-modal-tabs {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 6px;
            background: #020617;
            padding: 5px;
            border-radius: 14px;
            border: 1px solid #1e293b;
        }

        .ab-tab-item {
            padding: 9px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 800;
            border: none;
            cursor: pointer;
            text-align: center;
            transition: all 0.15s;
        }

        .ab-tab-item.active {
            background: #1e293b;
            color: #fbbf24;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }

        .ab-tab-item.inactive {
            background: transparent;
            color: #64748b;
        }

        .ab-form-stack {
            display: flex;
            flex-direction: column;
            gap: 14px;
            width: 100%;
        }

        .ab-field {
            display: flex;
            flex-direction: column;
            gap: 6px;
            width: 100%;
        }

        .ab-field-label {
            font-size: 11px;
            font-weight: 700;
            color: #cbd5e1;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .ab-field-input, .ab-field-select {
            width: 100%;
            background: #020617;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 11px 14px;
            font-size: 13px;
            color: #ffffff;
            outline: none;
            box-sizing: border-box;
            transition: border-color 0.15s;
        }

        .ab-field-input:focus, .ab-field-select:focus {
            border-color: #d97706;
        }

        .ab-error-msg {
            color: #fb7185;
            font-size: 11px;
            font-weight: 600;
        }

        .ab-modal-foot {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 12px;
            padding-top: 14px;
            border-top: 1px solid #1e293b;
        }

        .ab-foot-btn-sec {
            background: #1e293b;
            border: 1px solid #334155;
            color: #cbd5e1;
            font-size: 12px;
            font-weight: 700;
            padding: 10px 18px;
            border-radius: 12px;
            cursor: pointer;
        }

        .ab-foot-btn-pri {
            background: #d97706;
            border: none;
            color: #020617;
            font-size: 12px;
            font-weight: 900;
            padding: 11px 22px;
            border-radius: 12px;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            box-shadow: 0 4px 14px rgba(217, 119, 6, 0.35);
        }

        .ab-foot-btn-pri:hover {
            background: #f59e0b;
        }

        .ab-foot-btn-success {
            background: #10b981;
            border: none;
            color: #ffffff;
            font-size: 12px;
            font-weight: 900;
            padding: 11px 22px;
            border-radius: 12px;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35);
        }

        .ab-foot-btn-success:hover {
            background: #059669;
        }
    </style>

    <div class="ab-wrap">
        {{-- 1. BARRA SUPERIOR & FILTRO DE DATA --}}
        <div class="ab-toolbar">
            <div class="ab-toolbar-header">
                {{-- SELETOR DE DATAS --}}
                <div class="ab-date-nav">
                    <button type="button" wire:click="previousDay" class="ab-nav-btn" title="Dia Anterior">
                        <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>

                    <button type="button" wire:click="setToday" class="ab-nav-today {{ $isToday ? 'is-active' : 'is-inactive' }}">
                        Hoje
                    </button>

                    <button type="button" wire:click="nextDay" class="ab-nav-btn" title="Próximo Dia">
                        <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>

                    <input type="date" wire:model.live="selectedDate" class="ab-date-picker" />

                    <div class="ab-date-title">
                        <span>📅 {{ $dateFormatted }}</span>
                    </div>
                </div>

                {{-- BOTÃO NOVO ENCAIXE --}}
                <button type="button" wire:click="mountAction('quickAppointment')" class="ab-btn-cta">
                    <svg style="width: 15px; height: 15px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>+ Novo Encaixe Rápido</span>
                </button>
            </div>

            {{-- 2. CARDS DE MÉTRICAS DO DIA --}}
            <div class="ab-stats-grid">
                <div class="ab-stat-box">
                    <span class="ab-stat-label">Total Agendado</span>
                    <span class="ab-stat-val">{{ $stats['total_count'] }} <span class="ab-stat-sub">clientes</span></span>
                </div>

                <div class="ab-stat-box">
                    <span class="ab-stat-label">Em Atendimento</span>
                    <span class="ab-stat-val ab-stat-val-amber">{{ $stats['in_progress_count'] }} <span class="ab-stat-sub">na cadeira</span></span>
                </div>

                <div class="ab-stat-box">
                    <span class="ab-stat-label">Concluídos</span>
                    <span class="ab-stat-val ab-stat-val-emerald">{{ $stats['completed_count'] }} <span class="ab-stat-sub">finalizados</span></span>
                </div>

                <div class="ab-stat-box">
                    <span class="ab-stat-label">Faturamento Previsto</span>
                    <span class="ab-stat-val">R$ {{ number_format($stats['total_revenue'], 2, ',', '.') }}</span>
                </div>
            </div>
        </div>

        {{-- 3. QUADRO EM COLUNAS (UM BARBEIRO POR COLUNA) --}}
        @if ($professionals->isEmpty())
            <div class="ab-empty-state" style="padding: 60px 20px;">
                <span style="font-size: 28px;">💈</span>
                <p style="font-size: 14px; font-weight: 700; color: #cbd5e1;">Nenhum barbeiro ativo cadastrado nesta unidade.</p>
                <a href="{{ url('/admin/' . filament()->getTenant()->slug . '/professionals/create') }}" class="ab-empty-btn">
                    + Cadastrar primeiro barbeiro
                </a>
            </div>
        @else
            <div class="ab-board-columns">
                @foreach ($professionals as $pro)
                    @php
                        $appts = $byBarber[$pro->id] ?? collect();
                        $barberRevenue = $appts->where('status', '!=', 'cancelled')->sum('service_price');
                    @endphp

                    <div class="ab-col">
                        {{-- CABEÇALHO DO BARBEIRO --}}
                        <div class="ab-col-head">
                            <div class="ab-barber-details">
                                <div class="ab-avatar">
                                    @if ($pro->photo_url)
                                        <img src="{{ $pro->photo_url }}" alt="{{ $pro->name }}" />
                                    @else
                                        {{ strtoupper(mb_substr($pro->name, 0, 1)) }}
                                    @endif
                                </div>
                                <div>
                                    <div class="ab-barber-name">{{ $pro->display_name }}</div>
                                    <div class="ab-barber-summary">{{ $appts->count() }} corte(s) &middot; R$ {{ number_format($barberRevenue, 2, ',', '.') }}</div>
                                </div>
                            </div>

                            <button
                                type="button"
                                wire:click="mountAction('quickAppointment', @js(['professionalId' => $pro->id]))"
                                class="ab-col-add"
                                title="Encaixar cliente para {{ $pro->display_name }}"
                            >
                                <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                                </svg>
                            </button>
                        </div>

                        {{-- LISTA DE AGENDAMENTOS DO BARBEIRO --}}
                        <div class="ab-card-stack">
                            @forelse ($appts as $appt)
                                @php
                                    $pillClass = match ($appt->status) {
                                        'scheduled' => 'ab-pill-scheduled',
                                        'confirmed' => 'ab-pill-confirmed',
                                        'in_progress' => 'ab-pill-in_progress',
                                        'completed' => 'ab-pill-completed',
                                        'no_show' => 'ab-pill-no_show',
                                        default => 'ab-pill-cancelled',
                                    };
                                @endphp

                                <div class="ab-card">
                                    {{-- HORÁRIO & STATUS --}}
                                    <div class="ab-card-row1">
                                        <div class="ab-time-badge">
                                            <span>⏰ {{ $appt->scheduled_at->format('H:i') }}</span>
                                            <small>até {{ $appt->ends_at->format('H:i') }}</small>
                                        </div>
                                        <span class="ab-pill {{ $pillClass }}">
                                            {{ $appt->status_label }}
                                        </span>
                                    </div>

                                    {{-- CLIENTE & WHATSAPP --}}
                                    <div class="ab-client-info">
                                        <span class="ab-client-name">{{ $appt->client_name }}</span>
                                        @if ($appt->client_phone)
                                            @if (app(\App\Services\SensitiveClientDataAccess::class)->canView())
                                                @php
                                                    app(\App\Services\SensitiveClientDataAccess::class)->record($appt, ['client_phone']);
                                                    $cleanPhone = preg_replace('/\D/', '', $appt->client_phone);
                                                    $phone55 = str_starts_with($cleanPhone, '55') ? $cleanPhone : '55' . $cleanPhone;
                                                @endphp
                                                <a
                                                    href="https://api.whatsapp.com/send?phone={{ $phone55 }}"
                                                    target="_blank"
                                                    class="ab-wa-btn"
                                                    title="Conversar no WhatsApp"
                                                >
                                                    <svg style="width: 14px; height: 14px;" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/>
                                                    </svg>
                                                </a>
                                            @else
                                                <span class="text-xs text-gray-500">{{ \App\Services\SensitiveClientData::maskPhone($appt->client_phone) }}</span>
                                            @endif
                                        @endif
                                    </div>

                                    {{-- SERVIÇOS & VALOR --}}
                                    <div class="ab-service-info">
                                        <span class="ab-service-names" title="{{ $appt->services_summary }}">✂️ {{ $appt->services_summary }}</span>
                                        <span class="ab-service-price">R$ {{ number_format($appt->service_price, 2, ',', '.') }}</span>
                                    </div>

                                    {{-- AÇÕES DE ATENDIMENTO --}}
                                    @if (!in_array($appt->status, ['completed', 'cancelled']))
                                        <div class="ab-card-actions">
                                            @if ($appt->status !== 'in_progress')
                                                <button
                                                    type="button"
                                                    wire:click="updateStatus({{ $appt->id }}, 'in_progress')"
                                                    class="ab-action-btn ab-btn-start"
                                                >
                                                    ▶️ Atender
                                                </button>
                                            @else
                                                <span class="ab-action-btn ab-btn-start" style="cursor: default; opacity: 0.9;">
                                                    Em Corte
                                                </span>
                                            @endif

                                            <button
                                                type="button"
                                                wire:click="openCheckoutModal({{ $appt->id }})"
                                                class="ab-action-btn ab-btn-finish"
                                            >
                                                ✅ Concluir
                                            </button>

                                            <button
                                                type="button"
                                                wire:click="updateStatus({{ $appt->id }}, 'no_show')"
                                                class="ab-action-btn ab-btn-noshow"
                                            >
                                                🚫 Faltou
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <div class="ab-empty-state">
                                    <span>☕</span>
                                    <p>Sem agendamentos para este barbeiro.</p>
                                    <button
                                        type="button"
                                        wire:click="mountAction('quickAppointment', @js(['professionalId' => $pro->id]))"
                                        class="ab-empty-btn"
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
    </div>

    {{-- 4. MODAL DE CONCLUSÃO & RECEBIMENTO (CHECKOUT DE PAGAMENTO) --}}
    @if ($checkoutModalOpen)
        <div class="ab-modal-mask">
            <div class="ab-modal-window">
                <div class="ab-modal-head">
                    <h3>🧾 Concluir Corte & Registrar Pagamento</h3>
                    <button type="button" wire:click="$set('checkoutModalOpen', false)" class="ab-modal-x">&times;</button>
                </div>

                <div style="background: #020617; border: 1px solid #1e293b; border-radius: 16px; padding: 16px; display: flex; flex-direction: column; gap: 8px;">
                    <div style="display: flex; justify-content: space-between; font-size: 13px;">
                        <span style="color: #94a3b8;">Cliente:</span>
                        <strong style="color: #ffffff;">{{ $checkoutClientName }}</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 13px;">
                        <span style="color: #94a3b8;">Serviço(s):</span>
                        <span style="color: #cbd5e1;">{{ $checkoutServicesSummary }}</span>
                    </div>
                </div>

                <div class="ab-form-stack">
                    <div class="ab-field">
                        <label class="ab-field-label">Valor do Corte / Serviços (R$)</label>
                        <input type="number" step="0.01" wire:model.live="checkoutAmount" class="ab-field-input" style="font-size: 16px; font-weight: 900; color: #fbbf24;" />
                    </div>

                    {{-- VENDA DE PRODUTOS NO ATENDIMENTO --}}
                    <div class="ab-field" style="background: #090d16; border: 1px dashed #334155; border-radius: 14px; padding: 14px;">
                        <label class="ab-field-label" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <span>🧴 Vender Produto ao Cliente (Gera Comissão)</span>
                            <span style="font-size: 11px; color: #10b981; font-weight: 800;">Estoque Integrado</span>
                        </label>

                        <div style="display: flex; gap: 8px;">
                            <select wire:model="selectedProductIdToAdd" class="ab-field-input" style="flex: 1; font-size: 12px;">
                                <option value="">-- Selecione um produto para adicionar --</option>
                                @foreach ($this->availableProducts as $prod)
                                    <option value="{{ $prod->id }}">
                                        {{ $prod->name }} - R$ {{ number_format($prod->price, 2, ',', '.') }} ({{ $prod->stock_quantity }} un. em estoque - Comis: {{ number_format($prod->commission_rate, 0) }}%)
                                    </option>
                                @endforeach
                            </select>
                            <button type="button" wire:click="addProductToCheckout" style="background: #3b82f6; color: #ffffff; border: none; border-radius: 10px; padding: 0 14px; font-size: 12px; font-weight: 800; cursor: pointer;">
                                + Adicionar
                            </button>
                        </div>

                        @if (count($checkoutProducts) > 0)
                            <div style="margin-top: 10px; display: flex; flex-direction: column; gap: 6px;">
                                @foreach ($checkoutProducts as $idx => $item)
                                    <div style="display: flex; justify-content: space-between; align-items: center; background: #020617; border: 1px solid #1e293b; border-radius: 8px; padding: 8px 12px; font-size: 12px;">
                                        <div>
                                            <strong style="color: #ffffff;">{{ $item['name'] }}</strong>
                                            <span style="color: #94a3b8; margin-left: 6px;">({{ $item['quantity'] }}x R$ {{ number_format($item['price'], 2, ',', '.') }})</span>
                                            <span style="color: #10b981; font-size: 11px; margin-left: 4px;">· Comis: {{ number_format($item['commission_rate'], 0) }}%</span>
                                        </div>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <span style="font-weight: 800; color: #fbbf24;">R$ {{ number_format($item['price'] * $item['quantity'], 2, ',', '.') }}</span>
                                            <button type="button" wire:click="removeProductFromCheckout({{ $idx }})" style="color: #ef4444; background: none; border: none; font-size: 16px; cursor: pointer; line-height: 1;">&times;</button>
                                        </div>
                                    </div>
                                @endforeach

                                <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 8px; font-size: 13px; border-top: 1px solid #1e293b;">
                                    <span style="color: #cbd5e1; font-weight: 700;">Subtotal em Produtos:</span>
                                    <strong style="color: #10b981;">R$ {{ number_format($this->checkoutProductsTotal, 2, ',', '.') }}</strong>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- TOTAL CONSOLIDADO --}}
                    @if (count($checkoutProducts) > 0)
                        <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid #10b981; border-radius: 12px; padding: 12px 16px; display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: #ffffff; font-weight: 800; font-size: 13px;">TOTAL A COBRAR DO CLIENTE:</span>
                            <span style="color: #34d399; font-size: 18px; font-weight: 900;">R$ {{ number_format($this->checkoutGrandTotal, 2, ',', '.') }}</span>
                        </div>
                    @endif

                    <div class="ab-field">
                        <label class="ab-field-label">Forma de Pagamento</label>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px;">
                            <button
                                type="button"
                                wire:click="$set('checkoutPaymentMethod', 'pix')"
                                style="padding: 12px; border-radius: 12px; font-size: 12px; font-weight: 800; border: 1px solid {{ $checkoutPaymentMethod === 'pix' ? '#38bdf8' : '#334155' }}; background: {{ $checkoutPaymentMethod === 'pix' ? 'rgba(56, 189, 248, 0.15)' : '#020617' }}; color: {{ $checkoutPaymentMethod === 'pix' ? '#38bdf8' : '#94a3b8' }}; cursor: pointer;"
                            >
                                📱 PIX
                            </button>
                            <button
                                type="button"
                                wire:click="$set('checkoutPaymentMethod', 'dinheiro')"
                                style="padding: 12px; border-radius: 12px; font-size: 12px; font-weight: 800; border: 1px solid {{ $checkoutPaymentMethod === 'dinheiro' ? '#34d399' : '#334155' }}; background: {{ $checkoutPaymentMethod === 'dinheiro' ? 'rgba(52, 211, 153, 0.15)' : '#020617' }}; color: {{ $checkoutPaymentMethod === 'dinheiro' ? '#34d399' : '#94a3b8' }}; cursor: pointer;"
                            >
                                💵 Dinheiro
                            </button>
                            <button
                                type="button"
                                wire:click="$set('checkoutPaymentMethod', 'debito')"
                                style="padding: 12px; border-radius: 12px; font-size: 12px; font-weight: 800; border: 1px solid {{ $checkoutPaymentMethod === 'debito' ? '#c084fc' : '#334155' }}; background: {{ $checkoutPaymentMethod === 'debito' ? 'rgba(192, 132, 252, 0.15)' : '#020617' }}; color: {{ $checkoutPaymentMethod === 'debito' ? '#c084fc' : '#94a3b8' }}; cursor: pointer;"
                            >
                                💳 Cartão Débito
                            </button>
                            <button
                                type="button"
                                wire:click="$set('checkoutPaymentMethod', 'credito')"
                                style="padding: 12px; border-radius: 12px; font-size: 12px; font-weight: 800; border: 1px solid {{ $checkoutPaymentMethod === 'credito' ? '#fbbf24' : '#334155' }}; background: {{ $checkoutPaymentMethod === 'credito' ? 'rgba(251, 191, 36, 0.15)' : '#020617' }}; color: {{ $checkoutPaymentMethod === 'credito' ? '#fbbf24' : '#94a3b8' }}; cursor: pointer;"
                            >
                                💳 Cartão Crédito
                            </button>
                        </div>
                    </div>

                    <div class="ab-modal-foot">
                        <button type="button" wire:click="$set('checkoutModalOpen', false)" class="ab-foot-btn-sec">
                            Cancelar
                        </button>
                        <button type="button" wire:click="confirmCheckout" class="ab-foot-btn-success">
                            Confirmar Recebimento & Concluir ✓
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>

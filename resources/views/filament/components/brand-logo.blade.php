@php
    $isAuthPage = isset($isAuthPage) ? (bool) $isAuthPage : (
        request()->routeIs('filament.*.auth.*')
        || request()->is('*login*')
        || request()->is('*password-reset*')
    );

    $isSaasMaster = false;
    try {
        $isSaasMaster = \Filament\Facades\Filament::getCurrentPanel()?->getId() === 'saas'
            || str_starts_with(request()->getHost(), 'master.')
            || request()->getHost() === 'master.aurosoft.com.br';
    } catch (\Throwable $e) {
        $isSaasMaster = false;
    }

    $school = null;
    if (! $isSaasMaster) {
        try {
            $school = \Filament\Facades\Filament::getTenant();

            if (! $school && auth()->check()) {
                $school = auth()->user()->schools()->first();
            }

            if (! $school) {
                $routeTenant = request()->route('tenant');
                if ($routeTenant) {
                    $school = is_numeric($routeTenant)
                        ? \App\Models\School::find((int) $routeTenant)
                        : null;
                }
            }

            if (! $school && session()->has('current_school_id')) {
                $school = \App\Models\School::find(session('current_school_id'));
            }

            if (! $school && class_exists(\App\Models\School::class)) {
                $school = \App\Models\School::getHeadquarters()
                    ?? \App\Models\School::query()->first();
            }
        } catch (\Throwable $e) {
            $school = null;
        }
    }
@endphp

@if ($isAuthPage && $school)
    {{-- Apenas a Logo do Cliente em Destaque na Tela de Login --}}
    @if ($school->logo_url)
        <div class="flex items-center justify-center select-none w-full" style="display: flex; align-items: center; justify-content: center; width: 100%; height: 100%;">
            <img
                src="{{ $school->logo_url }}"
                alt="{{ $school->trade_name ?: $school->legal_name }}"
                class="object-contain drop-shadow-sm mx-auto"
                style="max-height: 48px; max-width: 180px; width: auto; height: auto; object-fit: contain; display: block;"
            />
        </div>
    @else
        <div class="flex items-center justify-center gap-2 select-none w-full" style="display: flex; align-items: center; justify-content: center;">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-600 text-white font-black text-base shadow-md shadow-primary-500/25 ring-2 ring-primary-500/10" style="height: 40px; width: 40px; display: flex; align-items: center; justify-content: center;">
                {{ strtoupper(mb_substr($school->trade_name ?: $school->legal_name ?: 'E', 0, 1)) }}
            </div>
            <span class="text-sm font-bold text-slate-800 dark:text-white leading-tight">
                {{ $school->trade_name ?: $school->legal_name }}
            </span>
        </div>
    @endif
@elseif ($school && ! $isSaasMaster)
    {{-- Logo da Escola no Topo do Header (única no header) --}}
    @if ($school->logo_url)
        <div class="flex items-center gap-2 select-none py-1">
            <img
                src="{{ $school->logo_url }}"
                alt="{{ $school->trade_name ?: $school->legal_name }}"
                class="object-contain drop-shadow-sm transition-transform duration-200 hover:scale-[1.02]"
                style="max-height: 38px; max-width: 180px; width: auto; height: auto; object-fit: contain; display: block;"
            />
        </div>
    @else
        <div class="flex items-center gap-2.5 select-none py-1">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-tr from-primary-600 via-primary-500 to-amber-500 text-white font-black shadow-md shadow-primary-500/20 text-base select-none ring-1 ring-white/10">
                {{ strtoupper(mb_substr($school->trade_name ?: $school->legal_name ?: 'E', 0, 1)) }}
            </div>
            <div class="flex flex-col text-left leading-tight max-w-[190px]">
                <span class="text-sm font-black tracking-tight truncate text-slate-900 dark:text-white" title="{{ $school->trade_name ?: $school->legal_name }}">
                    {{ $school->trade_name ?: $school->legal_name }}
                </span>
                <span class="text-[10px] font-semibold text-primary-500 dark:text-primary-400 truncate">
                    Unidade Escolar
                </span>
            </div>
        </div>
    @endif
@else
    {{-- Logo Padrão Aurosoft (usada no SaaS Master ou quando não houver escola selecionada) --}}
    <div class="flex items-center gap-2.5 select-none">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-tr from-amber-500 via-amber-400 to-amber-300 text-slate-950 font-black shadow-md shadow-amber-500/20 text-lg select-none">
            A
        </div>
        <div class="flex flex-col text-left leading-none">
            <span class="text-sm font-black tracking-wider uppercase text-slate-900 dark:text-white">Aurosoft</span>
            <span class="text-[10px] font-semibold text-amber-500 dark:text-amber-400">Gestão Escolar & LMS</span>
        </div>
    </div>
@endif

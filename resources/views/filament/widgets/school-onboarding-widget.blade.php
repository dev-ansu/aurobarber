<x-filament-widgets::widget
    x-show="@js(!$isDismissed && $this->progressPercent < 100)"
    @class([
        'col-span-full w-full' => true,
        'hidden' => $isDismissed || $this->progressPercent >= 100,
    ])
>
@if(!$isDismissed && $this->progressPercent < 100)
    <div class="w-full rounded-2xl border border-amber-500/20 bg-gradient-to-r from-amber-500/10 via-amber-500/5 to-transparent dark:bg-gray-900/90 shadow-sm p-6">
        <div class="flex flex-wrap items-center justify-between gap-4 mb-5">
            <div class="flex items-center gap-3">
                <div class="h-12 w-12 rounded-xl bg-amber-500/20 text-amber-500 flex items-center justify-center">
                    <x-filament::icon icon="heroicon-o-rocket-launch" class="w-6 h-6" />
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <span>Primeiros Passos com o Aurosoft</span>
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-500">
                            {{ $this->progressPercent }}% Concluído
                        </span>
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        Siga o checklist abaixo para deixar sua instituição 100% pronta para a rotina diária de aulas e cobranças.
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button
                    wire:click="dismiss"
                    type="button"
                    title="Ocultar este checklist"
                    class="text-xs text-gray-400 hover:text-gray-200 px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-800 hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                    Ocultar Guia
                </button>
            </div>
        </div>

        <!-- Barra de Progresso Visual -->
        <div class="w-full bg-gray-200 dark:bg-gray-800 rounded-full h-2 mb-6 overflow-hidden">
            <div
                class="bg-gradient-to-r from-amber-500 to-amber-400 h-2 rounded-full transition-all duration-500"
                style="width: {{ $this->progressPercent }}%"></div>
        </div>

        <!-- Grade de Etapas -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-3.5">
            @foreach($this->steps as $index => $step)
                <div class="min-w-0 p-4 rounded-xl border {{ $step['done'] ? 'bg-emerald-500/5 border-emerald-500/20' : 'bg-white dark:bg-gray-900 border-gray-100 dark:border-gray-800 shadow-xs' }} flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[10px] font-bold uppercase tracking-wider {{ $step['done'] ? 'text-emerald-500' : 'text-gray-400' }}">
                                Etapa {{ $index + 1 }}
                            </span>
                            @if($step['done'])
                                <span class="flex items-center gap-1 text-[11px] font-bold text-emerald-500">
                                    <x-filament::icon icon="heroicon-o-check-circle" class="w-4 h-4 text-emerald-500" />
                                    <span>Pronto</span>
                                </span>
                            @else
                                <span class="text-[11px] font-semibold text-amber-500">Pendente</span>
                            @endif
                        </div>

                        <h4 class="text-xs font-bold text-gray-900 dark:text-white leading-snug mb-1">
                            {{ $step['title'] }}
                        </h4>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 leading-relaxed mb-3">
                            {{ $step['description'] }}
                        </p>
                    </div>

                    @if(!$step['done'])
                        <a
                            href="{{ $step['url'] }}"
                            class="inline-flex items-center justify-center gap-1 w-full px-2.5 py-1.5 text-xs font-bold rounded-lg bg-amber-500 hover:bg-amber-600 text-gray-950 transition shadow-xs">
                            <span>Configurar</span>
                            <x-filament::icon icon="heroicon-o-arrow-right" class="w-3.5 h-3.5" />
                        </a>
                    @else
                        <div class="text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold text-center py-1">
                            ✓ Concluído com sucesso
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endif
</x-filament-widgets::widget>

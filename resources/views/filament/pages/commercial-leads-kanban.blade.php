<x-filament-panels::page>
    @php
        $stages = $this->stages();
        $leadsByStage = $this->leadsByStage();
        $stageCounts = $this->stageCounts();
        $stageKeys = array_keys($stages);
    @endphp

    <div class="space-y-4">
        <!-- BARRA SUPERIOR DE FILTROS & AÇÕES -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                <div class="w-full sm:w-64">
                    <input
                        type="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Buscar por nome, telefone..."
                        class="block w-full rounded-lg border-gray-300 py-2 px-3 text-sm shadow-sm focus:border-primary-600 focus:ring-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    />
                </div>

                <div class="w-full sm:w-56">
                    <select
                        wire:model.live="period"
                        class="block w-full rounded-lg border-gray-300 py-2 px-3 text-sm shadow-sm focus:border-primary-600 focus:ring-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white font-medium"
                    >
                        <option value="30_days">Últimos 30 dias (Rápido)</option>
                        <option value="60_days">Últimos 60 dias</option>
                        <option value="this_month">Mês Atual</option>
                        <option value="active_only">Apenas Ativos (Em negociação)</option>
                        <option value="all">Todo o Histórico</option>
                    </select>
                </div>

                <div class="w-full sm:w-64">
                    <select
                        wire:model.live="collaboratorId"
                        class="block w-full rounded-lg border-gray-300 py-2 px-3 text-sm shadow-sm focus:border-primary-600 focus:ring-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    >
                        <option value="">Todos os captadores/vendedores</option>
                        @foreach($this->collaborators() as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <a
                    href="{{ \App\Filament\Resources\CommercialLeads\CommercialLeadResource::getUrl('index') }}"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                >
                    <x-filament::icon icon="heroicon-o-table-cells" class="h-4 w-4" />
                    <span>Visualizar em Tabela</span>
                </a>
            </div>
        </div>

        <!-- QUADRO KANBAN (COLUNAS DO FUNIL) -->
        <div class="flex gap-4 overflow-x-auto pb-6 pt-1 select-none" style="min-height: 600px;">
            @foreach($stages as $stageKey => $stageInfo)
                @php
                    $leadsInStage = $leadsByStage->get($stageKey) ?? collect();
                    $currentIndex = array_search($stageKey, $stageKeys, true);
                    $prevStage = $currentIndex > 0 ? $stageKeys[$currentIndex - 1] : null;
                    $nextStage = $currentIndex < count($stageKeys) - 1 ? $stageKeys[$currentIndex + 1] : null;
                @endphp

                <div
                    class="flex w-80 flex-shrink-0 flex-col rounded-xl border {{ $stageInfo['border'] }} {{ $stageInfo['bg'] }} p-3 shadow-sm transition"
                    ondragover="event.preventDefault(); this.classList.add('ring-2', 'ring-primary-500');"
                    ondragleave="this.classList.remove('ring-2', 'ring-primary-500');"
                    ondrop="this.classList.remove('ring-2', 'ring-primary-500'); handleDrop(event, '{{ $stageKey }}');"
                >
                    <!-- CABEÇALHO DA ETAPA -->
                    <div class="mb-3 flex items-center justify-between pb-2 border-b border-gray-200/60 dark:border-white/10">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-gray-800 dark:text-gray-200">
                            {{ $stageInfo['label'] }}
                        </h3>
                        @php
                            $totalCount = $stageCounts[$stageKey] ?? $leadsInStage->count();
                        @endphp
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-black {{ $stageInfo['badge'] }}" title="{{ $leadsInStage->count() }} exibidos de {{ $totalCount }} no total">
                            {{ $totalCount }}
                        </span>
                    </div>

                    <!-- CARDS DE LEADS -->
                    <div class="flex flex-1 flex-col gap-2.5 overflow-y-auto" style="max-height: 700px;">
                        @forelse($leadsInStage as $lead)
                            @php
                                $cleanPhone = preg_replace('/\D/', '', (string) $lead->phone);
                                $isFollowUpOverdue = $lead->next_follow_up_at && $lead->next_follow_up_at->isPast() && !in_array($lead->status, ['lost', 'enrolled'], true);
                            @endphp

                            <div
                                draggable="true"
                                ondragstart="handleDragStart(event, {{ $lead->id }})"
                                class="group relative cursor-grab rounded-lg border border-gray-200 bg-white p-3 shadow-sm hover:shadow-md transition dark:border-gray-800 dark:bg-gray-900 active:cursor-grabbing"
                            >
                                <div class="flex items-start justify-between gap-2">
                                    <h4 class="font-bold text-sm text-gray-900 dark:text-white leading-tight">
                                        {{ $lead->name }}
                                    </h4>
                                    <span class="text-xs text-gray-400">#{{ $lead->id }}</span>
                                </div>

                                @if($lead->formation)
                                    <div class="mt-1.5">
                                        <span class="inline-flex items-center rounded bg-primary-50 px-2 py-0.5 text-xs font-medium text-primary-700 dark:bg-primary-950/40 dark:text-primary-300">
                                            {{ $lead->formation->name }}
                                        </span>
                                    </div>
                                @endif

                                @if($lead->contract)
                                    <div class="mt-1.5">
                                        <span class="inline-flex items-center gap-1 rounded bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                                            <x-filament::icon icon="heroicon-m-check-badge" class="h-3.5 w-3.5" />
                                            <span>Contrato #{{ $lead->contract->contract_number }}</span>
                                        </span>
                                    </div>
                                @endif

                                @if($lead->lossReason)
                                    <div class="mt-1.5" title="{{ $lead->loss_notes }}">
                                        <span class="inline-flex items-center gap-1 rounded bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700 dark:bg-rose-950/40 dark:text-rose-300">
                                            <x-filament::icon icon="heroicon-m-x-circle" class="h-3.5 w-3.5" />
                                            <span>{{ $lead->lossReason->name }}</span>
                                        </span>
                                    </div>
                                @endif

                                @if($lead->source)
                                    <div class="mt-1">
                                        <span class="inline-flex items-center gap-1 rounded bg-sky-50 dark:bg-sky-950/40 px-1.5 py-0.5 text-[10px] font-semibold text-sky-700 dark:text-sky-300">
                                            <span>📍</span>
                                            <span>{{ \App\Models\CommercialLead::sources()[$lead->source] ?? $lead->source }}</span>
                                        </span>
                                    </div>
                                @endif

                                <div class="mt-2 space-y-1 text-xs text-gray-600 dark:text-gray-400">
                                    @if($lead->phone)
                                        <div class="flex items-center gap-1.5">
                                            <a
                                                href="https://wa.me/55{{ $cleanPhone }}?text=Ol%C3%A1%20{{ urlencode($lead->name) }},%20tudo%20bem?%20Somos%20da%20equipe%20pedag%C3%B3gica%20da%20escola."
                                                target="_blank"
                                                class="inline-flex items-center gap-1 font-semibold text-emerald-600 hover:underline dark:text-emerald-400"
                                            >
                                                <x-filament::icon icon="heroicon-m-chat-bubble-oval-left" class="h-3.5 w-3.5" />
                                                <span>{{ $lead->phone }}</span>
                                            </a>
                                        </div>
                                    @endif

                                    @if($lead->collaborator)
                                        <div class="flex items-center gap-1 text-gray-500">
                                            <x-filament::icon icon="heroicon-m-user" class="h-3.5 w-3.5 text-gray-400" />
                                            <span>{{ $lead->collaborator->person?->name }}</span>
                                        </div>
                                    @endif

                                    @if($lead->next_follow_up_at)
                                        <div class="flex items-center gap-1 font-medium {{ $isFollowUpOverdue ? 'text-danger-600 font-bold' : 'text-gray-500' }}">
                                            <x-filament::icon icon="heroicon-m-clock" class="h-3.5 w-3.5" />
                                            <span>Contato: {{ $lead->next_follow_up_at->format('d/m H:i') }}</span>
                                            @if($isFollowUpOverdue)
                                                <span class="text-xs uppercase text-danger-600">(Atrasado)</span>
                                            @endif
                                        </div>
                                    @endif
                                </div>

                                <!-- AÇÃO DIRETA: MATRICULAR ALUNO -->
                                @if(!$lead->contract && $lead->status !== 'lost')
                                    <div class="mt-2.5 pt-2 border-t border-gray-100 dark:border-white/5 flex items-center justify-between">
                                        <button
                                            type="button"
                                            wire:click="openEnrollModal({{ $lead->id }})"
                                            wire:loading.attr="disabled"
                                            class="inline-flex items-center gap-1.5 rounded-md bg-emerald-600 px-2.5 py-1 text-xs font-semibold text-white shadow-sm hover:bg-emerald-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600 transition"
                                            title="Iniciar matrícula do aluno no sistema de contratos"
                                        >
                                            <x-filament::icon icon="heroicon-m-academic-cap" class="h-3.5 w-3.5" />
                                            <span>Matricular</span>
                                        </button>
                                    </div>
                                @endif

                                <!-- CONTROLES DE MOVIMENTAÇÃO RÁPIDA (BOTÕES) -->
                                <div class="mt-3 flex items-center justify-between border-t border-gray-100 pt-2 dark:border-white/5">
                                    <div>
                                        @if($prevStage)
                                            <button
                                                type="button"
                                                wire:click="updateLeadStatus({{ $lead->id }}, '{{ $prevStage }}')"
                                                class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-white/10 dark:hover:text-white"
                                                title="Mover para etapa anterior: {{ $stages[$prevStage]['label'] }}"
                                            >
                                                <x-filament::icon icon="heroicon-m-arrow-left" class="h-4 w-4" />
                                            </button>
                                        @endif
                                    </div>

                                    <div class="text-[10px] text-gray-400">
                                        {{ $lead->updated_at?->diffForHumans() }}
                                    </div>

                                    <div>
                                        @if($nextStage)
                                            <button
                                                type="button"
                                                wire:click="updateLeadStatus({{ $lead->id }}, '{{ $nextStage }}')"
                                                class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-white/10 dark:hover:text-white"
                                                title="Avançar para: {{ $stages[$nextStage]['label'] }}"
                                            >
                                                <x-filament::icon icon="heroicon-m-arrow-right" class="h-4 w-4" />
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-lg border border-dashed border-gray-300 p-4 text-center text-xs text-gray-400 dark:border-gray-800">
                                Nenhum lead nesta etapa
                            </div>
                        @endforelse

                        @if($leadsInStage->count() < ($stageCounts[$stageKey] ?? 0))
                            <div class="pt-2 pb-1 text-center">
                                <button
                                    wire:click="loadMore('{{ $stageKey }}')"
                                    type="button"
                                    class="w-full py-1.5 px-2.5 rounded-lg border border-dashed border-gray-300 hover:border-primary-500 bg-white/70 hover:bg-white text-[11px] font-bold text-gray-700 dark:border-gray-700 dark:hover:border-primary-400 dark:bg-gray-800/60 dark:hover:bg-gray-800 dark:text-gray-200 transition shadow-2xs"
                                >
                                    + Carregar mais (+25)
                                </button>
                                <span class="block text-[10px] text-gray-400 mt-1">
                                    Exibindo {{ $leadsInStage->count() }} de {{ $stageCounts[$stageKey] }}
                                </span>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- MODAL DE MOTIVO DE PERDA / DESISTÊNCIA -->
    @if($showLossModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-gray-900/60 p-4 backdrop-blur-sm">
            <div class="relative w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3 dark:border-white/10">
                    <div class="flex items-center gap-2">
                        <div class="rounded-full bg-rose-100 p-2 text-rose-600 dark:bg-rose-950/50 dark:text-rose-400">
                            <x-filament::icon icon="heroicon-o-x-circle" class="h-6 w-6" />
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">Registrar Perda / Desistência</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Informe o motivo para mantermos as estatísticas comerciais da escola atualizadas.</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        wire:click="closeLossModal"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                    >
                        <x-filament::icon icon="heroicon-m-x-mark" class="h-5 w-5" />
                    </button>
                </div>

                <div class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                            Motivo da Desistência / Perda <span class="text-rose-500">*</span>
                        </label>
                        <select
                            wire:model="lossReasonId"
                            class="block w-full rounded-lg border-gray-300 py-2 px-3 text-sm shadow-sm focus:border-rose-600 focus:ring-rose-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        >
                            <option value="">Selecione um motivo...</option>
                            @foreach($this->lossReasons() as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('lossReasonId')
                            <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                            Detalhes / Observações adicionais (opcional)
                        </label>
                        <textarea
                            wire:model="lossNotes"
                            rows="3"
                            placeholder="Ex: O cliente optou por adiar a matrícula para o próximo semestre devido a mudanças na faculdade..."
                            class="block w-full rounded-lg border-gray-300 py-2 px-3 text-sm shadow-sm focus:border-rose-600 focus:ring-rose-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        ></textarea>
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-end gap-3 border-t border-gray-100 pt-4 dark:border-white/10">
                    <button
                        type="button"
                        wire:click="closeLossModal"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                    >
                        Cancelar
                    </button>
                    <button
                        type="button"
                        wire:click="confirmLoss"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-rose-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-rose-500 transition"
                    >
                        <x-filament::icon icon="heroicon-m-check" class="h-4 w-4" />
                        <span>Confirmar Perda</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- MODAL DE MATRÍCULA E DEFINIÇÃO DE RESPONSÁVEL -->
    @if($showEnrollModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-gray-900/60 p-4 backdrop-blur-sm">
            <div class="relative w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3 dark:border-white/10">
                    <div class="flex items-center gap-2">
                        <div class="rounded-full bg-emerald-100 p-2 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400">
                            <x-filament::icon icon="heroicon-o-academic-cap" class="h-6 w-6" />
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">Matricular {{ $enrollLeadName }}</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Defina quem será o responsável financeiro pelo contrato do aluno.</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        wire:click="closeEnrollModal"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                    >
                        <x-filament::icon icon="heroicon-m-x-mark" class="h-5 w-5" />
                    </button>
                </div>

                <div class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                            Quem assinará e pagará o contrato? <span class="text-emerald-500">*</span>
                        </label>
                        <select
                            wire:model.live="enrollResponsibleMode"
                            class="block w-full rounded-lg border-gray-300 py-2 px-3 text-sm shadow-sm focus:border-emerald-600 focus:ring-emerald-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        >
                            <option value="student">O próprio aluno (maior de idade / pagador)</option>
                            <option value="other">Outra pessoa (Pai, Mãe, Responsável Legal)</option>
                        </select>
                    </div>

                    @if($enrollResponsibleMode === 'other')
                        <div class="rounded-xl border border-emerald-200 bg-emerald-50/40 p-4 space-y-3 dark:border-emerald-900/50 dark:bg-emerald-950/20">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                    Nome do Responsável <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    wire:model="enrollResponsibleName"
                                    placeholder="Ex: Maria Silva (Mãe)"
                                    class="block w-full rounded-lg border-gray-300 py-2 px-3 text-sm shadow-sm focus:border-emerald-600 focus:ring-emerald-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                />
                                @error('enrollResponsibleName')
                                    <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                        Parentesco <span class="text-rose-500">*</span>
                                    </label>
                                    <select
                                        wire:model="enrollResponsibleRelationship"
                                        class="block w-full rounded-lg border-gray-300 py-2 px-3 text-sm shadow-sm focus:border-emerald-600 focus:ring-emerald-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                    >
                                        @foreach($this->relationships() as $rel)
                                            <option value="{{ $rel->value }}">{{ $rel->getLabel() }}</option>
                                        @endforeach
                                    </select>
                                    @error('enrollResponsibleRelationship')
                                        <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                        Telefone / WhatsApp
                                    </label>
                                    <input
                                        type="text"
                                        wire:model="enrollResponsiblePhone"
                                        placeholder="(85) 99999-9999"
                                        class="block w-full rounded-lg border-gray-300 py-2 px-3 text-sm shadow-sm focus:border-emerald-600 focus:ring-emerald-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                    />
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="mt-6 flex items-center justify-end gap-3 border-t border-gray-100 pt-4 dark:border-white/10">
                    <button
                        type="button"
                        wire:click="closeEnrollModal"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                    >
                        Cancelar
                    </button>
                    <button
                        type="button"
                        wire:click="confirmEnroll"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-500 transition"
                    >
                        <x-filament::icon icon="heroicon-m-arrow-right" class="h-4 w-4" />
                        <span>Abrir Formulário de Contrato</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- SCRIPT DE DRAG AND DROP -->
    <script>
        function handleDragStart(event, leadId) {
            event.dataTransfer.setData('text/plain', leadId);
        }

        function handleDrop(event, targetStage) {
            event.preventDefault();
            const leadId = event.dataTransfer.getData('text/plain');
            if (leadId) {
                @this.call('updateLeadStatus', parseInt(leadId), targetStage);
            }
        }
    </script>
</x-filament-panels::page>

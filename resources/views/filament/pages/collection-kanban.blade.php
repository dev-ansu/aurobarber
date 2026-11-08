<x-filament-panels::page>
    @php
        $stageColors = [
            'upcoming' => ['dot' => 'bg-blue-500'],
            'delay_light' => ['dot' => 'bg-amber-500'],
            'delay_moderate' => ['dot' => 'bg-orange-500'],
            'critical' => ['dot' => 'bg-rose-500'],
            'in_agreement' => ['dot' => 'bg-purple-500'],
        ];
    @endphp

    <div class="space-y-6">
        <!-- Barra Superior de Busca e Ações -->
        <div class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-50 text-xl dark:bg-primary-950/50">
                    📋
                </div>
                <div>
                    <h2 class="text-base font-bold text-gray-900 dark:text-white">Régua e Fila de Cobrança da Secretaria</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Acompanhamento de alunos a vencer e inadimplentes por faixas operacionais de atraso.</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="w-72">
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Buscar por aluno, contrato, responsável..."
                        class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-xs text-gray-900 placeholder-gray-400 shadow-sm focus:border-primary-500 focus:bg-white focus:ring-1 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:placeholder-gray-500"
                    />
                </div>
                <button
                    type="button"
                    wire:click="loadCards"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                >
                    ↻ Atualizar
                </button>
            </div>
        </div>

        <!-- Grade Kanban com rolagem horizontal para não achatar as colunas -->
        <div class="flex items-start gap-4 overflow-x-auto pb-6 pt-1">
            @foreach($this->stages() as $stageKey => $stage)
                @php
                    $stageCards = $cards[$stageKey] ?? [];
                    $count = count($stageCards);
                    $config = $stageColors[$stageKey] ?? ['dot' => 'bg-gray-500'];
                @endphp
                <div class="flex w-80 shrink-0 flex-col rounded-2xl border border-gray-200 bg-gray-50/70 p-3.5 shadow-sm dark:border-gray-800 dark:bg-gray-900/60">
                    <!-- Cabeçalho da Coluna -->
                    <div class="mb-3 flex items-center justify-between border-b border-gray-200 pb-2.5 dark:border-gray-800">
                        <div class="flex items-center gap-2">
                            <span class="h-2.5 w-2.5 rounded-full {{ $config['dot'] }}"></span>
                            <span class="text-xs font-bold text-gray-900 dark:text-white">{{ $stage['label'] }}</span>
                        </div>
                        <span class="rounded-full border border-gray-200 bg-gray-100 px-2 py-0.5 text-[11px] font-bold text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                            {{ $count }}
                        </span>
                    </div>

                    <!-- Lista de Cartões da Coluna -->
                    <div class="flex flex-1 flex-col gap-3 overflow-y-auto max-h-[72vh] pr-1">
                        @forelse($stageCards as $card)
                            <div class="rounded-xl border border-gray-200 bg-white p-3.5 shadow-sm transition hover:shadow-md dark:border-gray-700 dark:bg-gray-800">
                                <!-- Topo do Card: Nome e Valor -->
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0 flex-1">
                                        <div class="text-xs font-bold text-gray-900 dark:text-white line-clamp-2" title="{{ $card['student_name'] }}">
                                            {{ $card['student_name'] }}
                                        </div>
                                        <div class="mt-0.5 text-[11px] text-gray-500 dark:text-gray-400">
                                            Contrato #{{ $card['contract_number'] }}
                                            @if($card['installment_number'])
                                                • Parc. {{ $card['installment_number'] }}/{{ $card['total_installments'] }}
                                            @endif
                                        </div>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <div class="text-xs font-extrabold text-gray-900 dark:text-white">
                                            R$ {{ number_format($card['amount'], 2, ',', '.') }}
                                        </div>
                                        <div class="mt-1">
                                            @if($card['days_diff'] > 0)
                                                <span class="inline-flex items-center rounded-md border border-rose-300 bg-rose-50 px-2 py-0.5 text-[10px] font-bold text-rose-700 dark:border-rose-900 dark:bg-rose-950 dark:text-rose-300">
                                                    {{ $card['days_diff'] }}d atraso
                                                </span>
                                            @elseif($card['days_diff'] === 0)
                                                <span class="inline-flex items-center rounded-md border border-amber-300 bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-700 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-300">
                                                    Vence hoje
                                                </span>
                                            @else
                                                <span class="inline-flex items-center rounded-md border border-blue-300 bg-blue-50 px-2 py-0.5 text-[10px] font-bold text-blue-700 dark:border-blue-900 dark:bg-blue-950 dark:text-blue-300">
                                                    Vence em {{ abs($card['days_diff']) }}d
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- Responsável / Contato -->
                                @if($card['responsible_name'])
                                    <div class="mt-2 text-[11px] text-gray-500 dark:text-gray-400 truncate">
                                        Resp: <span class="font-medium text-gray-800 dark:text-gray-200">{{ $card['responsible_name'] }}</span>
                                    </div>
                                @endif

                                <!-- Último Histórico de Contato Registrado -->
                                @if($card['latest_note'])
                                    <div class="mt-2.5 rounded-lg border border-gray-200 bg-gray-50 p-2 text-[11px] text-gray-700 dark:border-gray-700 dark:bg-gray-900/80 dark:text-gray-300">
                                        <div class="font-semibold text-gray-900 dark:text-gray-100">
                                            💬 Último contato ({{ $card['latest_note_date'] }}):
                                        </div>
                                        <div class="mt-0.5 italic text-gray-600 dark:text-gray-400">
                                            "{{ \Illuminate\Support\Str::limit($card['latest_note'], 95) }}"
                                        </div>
                                    </div>
                                @endif

                                <!-- Botões de Ação do Card -->
                                <div class="mt-3 flex items-center justify-between gap-1.5 border-t border-gray-100 pt-2.5 dark:border-gray-700">
                                    <button
                                        type="button"
                                        wire:click="openWhatsApp({{ $card['installment_id'] }})"
                                        class="flex-1 h-8 inline-flex items-center justify-center gap-1 rounded-lg border border-emerald-300 bg-emerald-50 px-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-100 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 dark:hover:bg-emerald-900"
                                        title="Abrir WhatsApp com cobrança e Pix Copia e Cola"
                                    >
                                        <span>💬</span>
                                        <span>WhatsApp</span>
                                    </button>

                                    <button
                                        type="button"
                                        wire:click="openFollowUpModal({{ $card['contract_id'] }})"
                                        class="flex-1 h-8 inline-flex items-center justify-center gap-1 rounded-lg border border-gray-300 bg-gray-100 px-2 text-xs font-semibold text-gray-700 hover:bg-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                                        title="Registrar conversa/ligação com o aluno ou responsável"
                                    >
                                        <span>📝</span>
                                        <span>Anotar</span>
                                    </button>

                                    @if($stageKey === 'critical')
                                        <button
                                            type="button"
                                            wire:click="openAgreementModal({{ $card['contract_id'] }})"
                                            class="flex-1 h-8 inline-flex items-center justify-center gap-1 rounded-lg border border-amber-300 bg-amber-50 px-2 text-xs font-semibold text-amber-700 hover:bg-amber-100 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-300 dark:hover:bg-amber-900"
                                            title="Formalizar Acordo de Renegociação"
                                        >
                                            <span>🤝</span>
                                            <span>Acordo</span>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="py-8 text-center text-xs text-gray-400 dark:text-gray-500">
                                Nenhum aluno nesta faixa.
                            </div>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Modal de Anotação de Contato -->
        @if($showFollowUpModal)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/70 p-4 backdrop-blur-xs">
                <div class="w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 shadow-2xl dark:border-gray-700 dark:bg-gray-800">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">Registrar Contato de Cobrança</h3>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Anote o que foi combinado com o aluno/responsável. Ficará salvo no histórico do contrato para toda a equipe visualizar.
                    </p>

                    <div class="mt-4 space-y-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300">O que foi conversado?</label>
                            <textarea
                                wire:model="followUpNote"
                                rows="4"
                                placeholder="Ex: Mãe atendeu, informou que o salário é liberado no dia 15 e comparecerá à escola para quitar a parcela..."
                                class="mt-1 w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs text-gray-900 placeholder-gray-400 shadow-sm focus:border-primary-500 focus:bg-white focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:placeholder-gray-500"
                            ></textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300">Data de Retorno / Promessa (Opcional)</label>
                            <input
                                type="date"
                                wire:model="nextFollowUpDate"
                                class="mt-1 w-full rounded-lg border border-gray-300 bg-gray-50 p-2 text-xs text-gray-900 shadow-sm focus:border-primary-500 focus:bg-white focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                            />
                        </div>
                    </div>

                    <div class="mt-5 flex items-center justify-end gap-2">
                        <button
                            type="button"
                            wire:click="$set('showFollowUpModal', false)"
                            class="rounded-lg border border-gray-300 bg-gray-100 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-200 dark:border-gray-600 dark:bg-gray-750 dark:text-gray-200 dark:hover:bg-gray-700"
                        >
                            Cancelar
                        </button>
                        <button
                            type="button"
                            wire:click="saveFollowUp"
                            class="rounded-lg bg-primary-600 px-4 py-2 text-xs font-bold text-white hover:bg-primary-500 focus:outline-none"
                        >
                            Salvar Acompanhamento
                        </button>
                    </div>
                </div>
            </div>
        @endif

        <!-- Modal de Acordo / Renegociação de Dívidas -->
        @if($showAgreementModal)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/75 p-4 backdrop-blur-xs overflow-y-auto">
                <div class="my-8 w-full max-w-2xl rounded-2xl border border-gray-200 bg-white p-6 shadow-2xl dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex items-start justify-between border-b border-gray-200 pb-3 dark:border-gray-700">
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">
                                🤝 Formalizar Acordo e Renegociação de Dívida
                            </h3>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                Aluno: <span class="font-bold text-gray-800 dark:text-gray-200">{{ $agreementStudentName }}</span> • Contrato #{{ $agreementContractNumber }}
                            </p>
                        </div>
                        <button
                            type="button"
                            wire:click="$set('showAgreementModal', false)"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-lg leading-none"
                        >
                            ✕
                        </button>
                    </div>

                    <div class="mt-4 space-y-4 text-xs">
                        <!-- Seleção das parcelas pendentes -->
                        <div>
                            <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                                Selecione as parcelas que entrarão no acordo:
                            </label>
                            <div class="max-h-40 overflow-y-auto rounded-xl border border-gray-200 bg-gray-50 p-2.5 space-y-1.5 dark:border-gray-700 dark:bg-gray-900/60">
                                @foreach($agreementPendingInstallments as $inst)
                                    <label class="flex items-center justify-between rounded-lg p-1.5 hover:bg-gray-100 dark:hover:bg-gray-800 cursor-pointer">
                                        <div class="flex items-center gap-2">
                                            <input
                                                type="checkbox"
                                                value="{{ $inst['id'] }}"
                                                wire:model.live="agreementSelectedInstallmentIds"
                                                class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800"
                                            />
                                            <span class="text-gray-800 dark:text-gray-200">
                                                Parcela {{ $inst['installment_number'] }}/{{ $inst['total_installments'] }} (Venc: {{ $inst['due_date'] }})
                                            </span>
                                        </div>
                                        <span class="font-extrabold text-gray-900 dark:text-white">
                                            R$ {{ number_format($inst['amount'], 2, ',', '.') }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <!-- Condições de Negociação (Desconto e Entrada) -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block font-semibold text-gray-700 dark:text-gray-300">Desconto Concedido (R$)</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    wire:model.live.debounce.300ms="agreementDiscount"
                                    placeholder="0,00"
                                    class="mt-1 w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-xs text-gray-900 shadow-sm focus:border-primary-500 focus:bg-white focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                                />
                                <span class="text-[10px] text-gray-500 dark:text-gray-400">Ex: Isenção de multa ou abatimento.</span>
                            </div>

                            <div>
                                <label class="block font-semibold text-gray-700 dark:text-gray-300">Valor de Entrada (R$)</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    wire:model.live.debounce.300ms="agreementDownPayment"
                                    placeholder="0,00"
                                    class="mt-1 w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-xs text-gray-900 shadow-sm focus:border-primary-500 focus:bg-white focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                                />
                                <span class="text-[10px] text-gray-500 dark:text-gray-400">Valor pago à vista no ato do acordo.</span>
                            </div>
                        </div>

                        <!-- Se houver entrada: forma de pagamento e se já foi paga -->
                        @if((float) $agreementDownPayment > 0)
                            <div class="rounded-xl border border-emerald-200 bg-emerald-50/70 p-3 dark:border-emerald-900/60 dark:bg-emerald-950/40">
                                <label class="flex items-center gap-2 cursor-pointer font-semibold text-emerald-800 dark:text-emerald-300">
                                    <input
                                        type="checkbox"
                                        wire:model="agreementDownPaymentPaid"
                                        class="rounded border-emerald-300 text-emerald-600 focus:ring-emerald-500"
                                    />
                                    Entrada foi paga agora no balcão (dar baixa imediata no caixa aberto)
                                </label>
                            </div>
                        @endif

                        <!-- Parcelamento do Saldo Restante -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block font-semibold text-gray-700 dark:text-gray-300">Parcelar saldo em quantas vezes?</label>
                                <select
                                    wire:model.live="agreementInstallmentsCount"
                                    class="mt-1 w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-xs text-gray-900 shadow-sm focus:border-primary-500 focus:bg-white focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                                >
                                    @for($i = 1; $i <= 24; $i++)
                                        <option value="{{ $i }}">{{ $i }}x {{ $i === 1 ? '(Parcela única)' : '' }}</option>
                                    @endfor
                                </select>
                            </div>

                            <div>
                                <label class="block font-semibold text-gray-700 dark:text-gray-300">1º Vencimento do Saldo</label>
                                <input
                                    type="date"
                                    wire:model="agreementFirstDueDate"
                                    class="mt-1 w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-xs text-gray-900 shadow-sm focus:border-primary-500 focus:bg-white focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                                />
                            </div>
                        </div>

                        <!-- Observações -->
                        <div>
                            <label class="block font-semibold text-gray-700 dark:text-gray-300">Observações / Termos Combinados</label>
                            <input
                                type="text"
                                wire:model="agreementNotes"
                                placeholder="Ex: Acordo firmado na secretaria; parcelas novas geradas."
                                class="mt-1 w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-xs text-gray-900 shadow-sm focus:border-primary-500 focus:bg-white focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                            />
                        </div>

                        <!-- Card de Resumo Financeiro da Renegociação -->
                        <div class="rounded-xl border border-gray-200 bg-gray-100/70 p-3.5 space-y-1.5 dark:border-gray-700 dark:bg-gray-900/80">
                            <div class="flex justify-between text-gray-600 dark:text-gray-400">
                                <span>Dívida Original Selecionada:</span>
                                <span class="font-bold text-gray-900 dark:text-white">R$ {{ number_format($this->agreementTotalOriginal, 2, ',', '.') }}</span>
                            </div>
                            @if((float) $agreementDiscount > 0)
                                <div class="flex justify-between text-emerald-600 dark:text-emerald-400">
                                    <span>Desconto Concedido:</span>
                                    <span class="font-bold">- R$ {{ number_format((float) $agreementDiscount, 2, ',', '.') }}</span>
                                </div>
                            @endif
                            <div class="flex justify-between border-t border-gray-200 pt-1 dark:border-gray-700 font-bold text-gray-900 dark:text-white">
                                <span>Total Final Acordado:</span>
                                <span>R$ {{ number_format($this->agreementTotalAgreed, 2, ',', '.') }}</span>
                            </div>
                            @if((float) $agreementDownPayment > 0)
                                <div class="flex justify-between text-blue-600 dark:text-blue-400">
                                    <span>Entrada:</span>
                                    <span class="font-bold">R$ {{ number_format((float) $agreementDownPayment, 2, ',', '.') }}</span>
                                </div>
                            @endif
                            <div class="flex justify-between border-t border-gray-200 pt-1.5 dark:border-gray-700 text-sm font-extrabold text-primary-600 dark:text-primary-400">
                                <span>Saldo a Reparcelar:</span>
                                <span>
                                    R$ {{ number_format($this->agreementRemainingBalance, 2, ',', '.') }}
                                    <span class="text-xs font-normal text-gray-500 dark:text-gray-400">
                                        ({{ $agreementInstallmentsCount }}x de R$ {{ number_format($this->agreementInstallmentValue, 2, ',', '.') }})
                                    </span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-3 border-t border-gray-200 pt-3 dark:border-gray-700">
                        <button
                            type="button"
                            wire:click="$set('showAgreementModal', false)"
                            class="rounded-lg border border-gray-300 bg-gray-100 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-200 dark:border-gray-600 dark:bg-gray-750 dark:text-gray-200 dark:hover:bg-gray-700"
                        >
                            Cancelar
                        </button>
                        <button
                            type="button"
                            wire:click="saveAgreement"
                            class="rounded-lg bg-primary-600 px-5 py-2 text-xs font-bold text-white shadow-sm hover:bg-primary-500 focus:outline-none"
                        >
                            Formalizar Acordo de Dívida
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>

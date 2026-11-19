<x-filament-panels::page>
    <div class="mx-auto w-full max-w-5xl space-y-6">
        {{-- 1. HEADER DO SUPORTE --}}
        <x-filament::section>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="max-w-2xl space-y-1.5">
                    <x-filament::badge color="success">Canal Oficial de Atendimento</x-filament::badge>
                    <h2 class="text-xl font-bold text-gray-950 dark:text-white">Contato de Suporte ao Cliente</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Este WhatsApp será exibido para as barbearias quando estiverem com a licença em carência ou suspensas por faturas em aberto.
                    </p>
                </div>

                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl border border-success-500/30 bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-400">
                    <x-filament::icon icon="heroicon-o-chat-bubble-left-right" class="h-7 w-7" />
                </div>
            </div>
        </x-filament::section>

        <div class="grid grid-cols-1 items-start gap-6 md:grid-cols-2">
            {{-- 2. FORMULÁRIO DE EDIÇÃO --}}
            <x-filament::section
                heading="Configurar Número do WhatsApp"
                description="Informe o número completo com DDI (55 para Brasil) e DDD sem espaços ou caracteres especiais."
            >
                <form wire:submit="save" class="space-y-5">
                    <div class="space-y-2">
                        <label class="block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                            Número de Telefone / WhatsApp *
                        </label>

                        <div class="relative flex items-center">
                            <span class="pointer-events-none absolute left-3.5 text-base opacity-80">💬</span>
                            <input
                                type="tel"
                                wire:model="supportPhone"
                                placeholder="Ex: 5585999999999"
                                class="block h-11 w-full rounded-lg border border-gray-300 bg-white pl-10 pr-3.5 font-mono text-sm text-gray-950 shadow-sm outline-none transition placeholder:text-gray-400 focus:border-success-500 focus:ring-1 focus:ring-success-500 dark:border-white/10 dark:bg-white/5 dark:text-white dark:placeholder:text-gray-500"
                            />
                        </div>

                        @error('supportPhone')
                            <p class="text-xs font-semibold text-danger-600 dark:text-danger-400">{{ $message }}</p>
                        @enderror

                        <p class="text-xs leading-relaxed text-gray-500 dark:text-gray-400">
                            💡 Exemplos válidos:
                            <span class="font-mono font-semibold text-success-600 dark:text-success-400">5585988887777</span>
                            ou
                            <span class="font-mono font-semibold text-success-600 dark:text-success-400">(85) 98888-7777</span>.
                            O sistema salva automaticamente o padrão internacional limpo.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 border-t border-gray-200 pt-4 dark:border-white/10">
                        <x-filament::button
                            type="submit"
                            color="success"
                            icon="heroicon-m-check"
                        >
                            Salvar Contato Oficial
                        </x-filament::button>

                        @if(!empty($supportPhone))
                            @php
                                $cleanDigits = preg_replace('/\D/', '', $supportPhone);
                            @endphp
                            <x-filament::button
                                tag="a"
                                href="https://wa.me/{{ $cleanDigits }}?text=Ol%C3%A1!%20Sou%20da%20equipe%20AuroBarber%20testando%20o%20canal%20de%20suporte."
                                target="_blank"
                                rel="noopener noreferrer"
                                color="gray"
                                icon="heroicon-m-arrow-top-right-on-square"
                            >
                                Abrir Teste
                            </x-filament::button>
                        @endif
                    </div>
                </form>
            </x-filament::section>

            {{-- 3. CARD DE PRÉ-VISUALIZAÇÃO (COMO A BARBEARIA ENXERGA) --}}
            <x-filament::section
                heading="Visão do Cliente Suspenso"
                description="Veja abaixo como o botão de socorro aparece na tela de bloqueio da barbearia."
            >
                <div class="space-y-4">
                    <div class="space-y-3 rounded-xl border border-danger-500/30 bg-danger-50 p-4 dark:bg-danger-500/10">
                        <div class="text-sm font-bold text-danger-600 dark:text-danger-400">
                            🔒 Acesso Temporariamente Bloqueado
                        </div>
                        <p class="text-xs leading-relaxed text-gray-700 dark:text-gray-300">
                            Sua assinatura está suspensa. Fale com a equipe pelo canal de atendimento:
                        </p>

                        @if(!empty($supportPhone))
                            <div class="flex w-full items-center justify-center gap-1.5 rounded-xl bg-success-600 px-4 py-2.5 text-center text-sm font-bold text-white shadow-sm dark:bg-success-500 dark:text-gray-950">
                                <span>💬 Falar no WhatsApp</span>
                            </div>
                        @else
                            <div class="w-full rounded-xl border border-dashed border-gray-300 bg-gray-100 px-4 py-2.5 text-center text-xs font-semibold text-gray-500 dark:border-white/15 dark:bg-white/5 dark:text-gray-400">
                                (Nenhum contato cadastrado)
                            </div>
                        @endif
                    </div>

                    <div class="space-y-1 border-t border-gray-200 pt-3 text-xs text-gray-500 dark:border-white/10 dark:text-gray-400">
                        <div>• Suporte 100% responsivo.</div>
                        <div>• Zero interferência no banco das barbearias.</div>
                    </div>
                </div>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>

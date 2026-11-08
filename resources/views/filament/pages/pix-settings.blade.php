<x-filament-panels::page>
    <div class="space-y-6">
        <!-- BANNER DE STATUS E APRESENTAÇÃO -->
        <x-filament::section>
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h3 class="text-base font-semibold text-gray-950 dark:text-white flex items-center gap-2">
                        <span>Padrão Oficial Banco Central (BR Code / EMVCo)</span>
                        @php
                            $isEnabled = (bool) ($this->data['pix_enabled'] ?? true);
                        @endphp
                        @if ($isEnabled && $this->pixPreview)
                            <x-filament::badge color="success" icon="heroicon-m-check-badge">PIX Ativo no Carnê</x-filament::badge>
                        @elseif ($isEnabled)
                            <x-filament::badge color="warning" icon="heroicon-m-exclamation-triangle">Chave Pendente</x-filament::badge>
                        @else
                            <x-filament::badge color="gray" icon="heroicon-m-no-symbol">PIX Desativado</x-filament::badge>
                        @endif
                    </h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        O Aurosoft gera o QR Code e o código Pix de forma <strong>100% local e offline</strong>, com cálculo matemático de CRC16 integrado. Não requer intermediários, provedores externos pagos, nem cobrança de taxas por transação.
                    </p>
                </div>
            </div>
        </x-filament::section>

        <!-- FORMULÁRIO DE CONFIGURAÇÃO -->
        <x-filament::section>
            <form wire:submit="save" class="space-y-6">
                {{ $this->form }}

                <div class="flex justify-end">
                    <x-filament::button type="submit" icon="heroicon-o-check" size="lg">
                        Salvar configurações do PIX
                    </x-filament::button>
                </div>
            </form>
        </x-filament::section>

        @if (! ($this->data['pix_enabled'] ?? true))
            <x-filament::section>
                <div class="flex items-center gap-3 text-sm text-gray-700 dark:text-gray-300">
                    <x-filament::icon icon="heroicon-m-no-symbol" class="h-6 w-6 text-gray-400 flex-shrink-0" />
                    <div>
                        <div class="font-semibold text-gray-900 dark:text-white">O PIX está desativado no sistema</div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            Nenhum QR Code ou dado do PIX será impresso nos carnês dos alunos. Para reativar a qualquer momento caso seu chefe decida usar, basta ligar o botão <strong>"Habilitar PIX nos Carnês e Cobranças"</strong> acima e salvar.
                        </p>
                    </div>
                </div>
            </x-filament::section>
        @endif

        <!-- PRÉVIA VISUAL EM TEMPO REAL -->
        @if ($preview = $this->pixPreview)
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-eye" class="h-5 w-5 text-primary-500" />
                        <span>Prévia em Tempo Real (Impressão no Carnê)</span>
                    </div>
                </x-slot>
                <x-slot name="description">
                    Veja como os dados e o QR Code aparecerão nas lâminas dos carnês de pagamento entregues aos alunos.
                </x-slot>

                <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-start mt-2">
                    <!-- CARTÃO SIMULADO DO CARNÊ -->
                    <div class="md:col-span-5 bg-white dark:bg-gray-900 border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-xl p-4 shadow-sm">
                        <div class="text-xs uppercase font-bold text-gray-500 dark:text-gray-400 mb-2 tracking-wider">
                            Simulação da Lâmina do Carnê
                        </div>
                        <div class="flex items-center gap-4 bg-gray-50 dark:bg-gray-800/80 p-3 rounded-lg border border-gray-200 dark:border-gray-700">
                            <div class="bg-white p-1 rounded border border-gray-300 flex-shrink-0">
                                <img src="{{ $preview['qr_code_uri'] }}" alt="QR Code Pix" class="w-24 h-24 object-contain" />
                            </div>
                            <div class="flex-1 min-w-0 space-y-1">
                                <div class="inline-flex items-center gap-1 bg-slate-900 text-white text-[10px] font-extrabold px-2 py-0.5 rounded tracking-wide uppercase">
                                    Pague com Pix
                                </div>
                                <div class="text-xs font-bold text-gray-900 dark:text-white truncate">
                                    {{ $preview['merchant_name'] }}
                                </div>
                                <div class="text-[11px] text-gray-600 dark:text-gray-300">
                                    <span class="font-semibold text-gray-500">Chave:</span> {{ $preview['pix_key'] }}
                                </div>
                                <div class="text-[11px] text-gray-600 dark:text-gray-300">
                                    <span class="font-semibold text-gray-500">Cidade:</span> {{ $preview['merchant_city'] }}
                                </div>
                                <div class="text-[10px] text-gray-500 dark:text-gray-400 font-mono">
                                    TXID: {{ $preview['txid'] }}
                                </div>
                            </div>
                        </div>
                        @if (!empty($preview['instructions']))
                            <div class="mt-2 text-[11px] text-gray-600 dark:text-gray-400 italic bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900/40 p-2 rounded">
                                <strong>Nota no carnê:</strong> {{ $preview['instructions'] }}
                            </div>
                        @endif
                    </div>

                    <!-- CÓDIGO COPIA E COLA E DETALHES -->
                    <div class="md:col-span-7 space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Código Pix Copia e Cola (Payload BR Code EMVCo)
                            </label>
                            <div class="relative" x-data="{ copied: false }">
                                <textarea readonly rows="3" class="w-full font-mono text-xs bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg p-2.5 text-gray-800 dark:text-gray-200 select-all cursor-pointer focus:ring-primary-500 focus:border-primary-500">{{ $preview['copy_paste'] }}</textarea>
                                <button type="button"
                                        x-on:click="navigator.clipboard.writeText('{{ $preview['copy_paste'] }}'); copied = true; setTimeout(() => copied = false, 2500)"
                                        class="absolute top-2 right-2 inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold text-gray-700 bg-white hover:bg-gray-100 border border-gray-300 rounded shadow-xs dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600">
                                    <span x-show="!copied">Copiar código</span>
                                    <span x-show="copied" class="text-green-600 font-bold">Copiado!</span>
                                </button>
                            </div>
                            <p class="mt-1 text-[11px] text-gray-500">
                                Código estático padronizado com checksum CRC16. Pode ser lido por qualquer aplicativo de banco no Brasil.
                            </p>
                        </div>

                        <!-- VALIDAÇÃO E FLUXO OPERACIONAL -->
                        <div class="bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-900/50 rounded-lg p-3 text-xs text-blue-900 dark:text-blue-200 space-y-1">
                            <div class="font-bold flex items-center gap-1.5">
                                <x-filament::icon icon="heroicon-m-information-circle" class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                                Como validar o recebimento do PIX de forma 100% offline:
                            </div>
                            <ol class="list-decimal list-inside space-y-0.5 text-blue-800 dark:text-blue-300 ml-1">
                                <li>O aluno ou responsável realiza o pagamento lendo o QR Code do carnê no app do seu banco.</li>
                                <li>O aluno apresenta o comprovante no caixa da escola ou envia pelo WhatsApp da secretaria.</li>
                                <li>No Aurosoft, em <strong>Financeiro &gt; Parcelas</strong>, o operador localiza a parcela do contrato e clica em <strong>Receber</strong>.</li>
                                <li>Informa a forma de pagamento <strong>PIX</strong> e vincula ao <strong>Caixa / Gaveta física</strong> aberto.</li>
                                <li>O sistema liquida a parcela, atualiza o carnê e registra a receita no <strong>DRE Gerencial</strong>.</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>


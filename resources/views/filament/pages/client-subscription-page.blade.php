<x-filament-panels::page>
    @php
        $shop = $this->barbershop;
        $supportPhone = preg_replace('/\D/', '', $this->supportPhone);
        $expiresAt = $shop?->license_expires_at ? \Carbon\Carbon::parse($shop->license_expires_at) : null;
        $isInGrace = $shop?->isInGracePeriod() ?? false;
        $isValid = $shop?->isLicenseActive() ?? true;
        $isExpired = $shop?->isLicenseExpired() ?? false;
        $currentPlan = $shop?->saasPlan;
        $monthlyPrice = $shop ? $shop->effectiveMonthlyPrice() : 89.90;
        $activeProfessionalsCount = $shop ? $shop->professionals()->where('is_active', true)->count() : 0;
        $submissions = $this->paymentSubmissions;
        $openRequest = $this->openPlanChangeRequest;
    @endphp

    @if($shop)
        <div x-data="{ activeTab: @entangle('activeTab'), copiedKey: false, copiedPayload: false }" class="space-y-6">
            @if(session('status'))
                <div class="rounded-2xl border border-success-300 bg-success-50 p-4 text-sm font-medium text-success-900 dark:border-success-700 dark:bg-success-950/30 dark:text-success-200">
                    {{ session('status') }}
                </div>
            @endif

            {{-- Banner de Alerta de Licença Vencida / Suspensa / Carência --}}
            @if(! $isValid)
                <section class="rounded-2xl border border-danger-300 bg-danger-50 p-5 dark:border-danger-700/60 dark:bg-danger-950/30" role="alert">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-danger-100 text-danger-600 dark:bg-danger-900/50 dark:text-danger-400">
                            <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-6 w-6" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <h2 class="text-base font-bold text-danger-900 dark:text-danger-200">
                                @if($shop->isSuspended())
                                    Sua licença está suspensa
                                @elseif($isExpired)
                                    Sua licença expirou
                                @else
                                    Sua licença precisa ser regularizada
                                @endif
                            </h2>
                            <p class="mt-1 text-sm text-danger-800 dark:text-danger-300">
                                @if($isExpired && $expiresAt)
                                    A licença venceu em {{ $expiresAt->format('d/m/Y') }} e o acesso ao painel administrativo está limitado. Realize o pagamento via Pix abaixo e envie o comprovante para validação.
                                @else
                                    Realize o pagamento da mensalidade via Pix abaixo e envie o comprovante, ou fale com o suporte para liberar o acesso ao painel.
                                @endif
                            </p>
                            <a href="#billing-payment" x-on:click="activeTab = 'assinatura'" class="mt-3 inline-flex items-center gap-1.5 rounded-lg bg-danger-600 px-3.5 py-2 text-xs font-bold text-white transition hover:bg-danger-700">
                                Ir para pagamento / regularização
                            </a>
                        </div>
                    </div>
                </section>
            @elseif($isInGrace && $expiresAt)
                <section class="rounded-2xl border border-warning-300 bg-warning-50 p-5 dark:border-warning-700/60 dark:bg-warning-950/30" role="status">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-warning-100 text-warning-700 dark:bg-warning-900/50 dark:text-warning-300">
                            <x-filament::icon icon="heroicon-o-clock" class="h-6 w-6" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <h2 class="text-base font-bold text-warning-900 dark:text-warning-200">
                                Sua licença venceu em {{ $expiresAt->format('d/m/Y') }}
                            </h2>
                            <p class="mt-1 text-sm text-warning-800 dark:text-warning-300">
                                Você está no período de carência ({{ $shop->graceDaysRemaining() }} dia(s) restante(s)). Faça o pagamento via Pix para evitar o bloqueio do painel administrativo.
                            </p>
                            <a href="#billing-payment" x-on:click="activeTab = 'assinatura'" class="mt-3 inline-flex items-center gap-1.5 rounded-lg bg-warning-600 px-3.5 py-2 text-xs font-bold text-white transition hover:bg-warning-700">
                                Ir para pagamento
                            </a>
                        </div>
                    </div>
                </section>
            @endif

            {{-- Navegação em Abas --}}
            <div class="flex flex-wrap border-b border-gray-200 dark:border-gray-800">
                <button
                    type="button"
                    x-on:click="activeTab = 'assinatura'"
                    :class="activeTab === 'assinatura'
                        ? 'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400'
                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                    class="flex cursor-pointer items-center gap-2 border-b-2 px-5 py-3 text-sm font-semibold transition"
                >
                    <x-filament::icon icon="heroicon-o-credit-card" class="h-5 w-5" />
                    <span>Minha Assinatura &amp; Faturamento</span>
                </button>

                <button
                    type="button"
                    x-on:click="activeTab = 'planos'"
                    :class="activeTab === 'planos'
                        ? 'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400'
                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                    class="flex cursor-pointer items-center gap-2 border-b-2 px-5 py-3 text-sm font-semibold transition"
                >
                    <x-filament::icon icon="heroicon-o-rectangle-stack" class="h-5 w-5" />
                    <span>Planos &amp; Upgrade</span>
                </button>

                <button
                    type="button"
                    x-on:click="activeTab = 'historico'"
                    :class="activeTab === 'historico'
                        ? 'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400'
                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                    class="flex cursor-pointer items-center gap-2 border-b-2 px-5 py-3 text-sm font-semibold transition"
                >
                    <x-filament::icon icon="heroicon-o-document-check" class="h-5 w-5" />
                    <span>Histórico de Pagamentos</span>
                    @if($submissions->isNotEmpty())
                        <span class="rounded-full bg-primary-100 px-2 py-0.5 text-xs font-bold text-primary-700 dark:bg-primary-950 dark:text-primary-300">
                            {{ $submissions->count() }}
                        </span>
                    @endif
                </button>
            </div>

            {{-- ABA 1: MINHA ASSINATURA & FATURAMENTO --}}
            <div x-show="activeTab === 'assinatura'" class="space-y-6">
                {{-- Card do Plano Ativo Atual --}}
                <section class="rounded-2xl border border-primary-300/30 bg-primary-50/70 p-6 dark:border-primary-500/20 dark:bg-primary-950/20">
                    <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-sm font-semibold text-gray-600 dark:text-gray-300">Plano atual</p>
                                @if($isInGrace)
                                    <x-filament::badge color="warning">Em Período de Carência</x-filament::badge>
                                @elseif($isValid)
                                    <x-filament::badge color="success">Ativa &amp; Regular</x-filament::badge>
                                @else
                                    <x-filament::badge color="danger">Suspensa / Vencida</x-filament::badge>
                                @endif
                            </div>

                            <h2 class="mt-1.5 text-2xl font-bold text-gray-950 dark:text-white">
                                {{ $currentPlan?->name ?: 'Configuração personalizada' }}
                            </h2>

                            @unless($currentPlan)
                                <p class="mt-1 max-w-2xl text-sm text-gray-600 dark:text-gray-400">
                                    Estas são as condições atuais da assinatura de <strong>{{ $shop->name }}</strong>. Veja na aba Planos &amp; Upgrade as opções disponíveis.
                                </p>
                            @endunless

                            <p class="mt-2 text-lg font-bold text-primary-700 dark:text-primary-300">
                                R$ {{ number_format($monthlyPrice, 2, ',', '.') }}
                                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">/ mês</span>
                            </p>

                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                Vencimento:
                                <strong class="text-gray-900 dark:text-white">
                                    {{ $expiresAt ? $expiresAt->format('d/m/Y') : 'Vitalícia / Isenta' }}
                                </strong>
                            </p>
                        </div>

                        <div class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2 lg:grid-cols-4">
                            <div class="min-w-0 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                                <div class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Unidade Atual</div>
                                <div class="mt-1 truncate text-base font-bold text-gray-950 dark:text-white">{{ $shop->name }}</div>
                                <div class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">/{{ $shop->slug }}</div>
                            </div>

                            <div class="min-w-0 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                                <div class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Unidades / Filiais</div>
                                <div class="mt-1 text-base font-bold text-gray-950 dark:text-white">
                                    @if($shop->hasUnrestrictedAccess())
                                        VIP (Ilimitadas)
                                    @else
                                        {{ $shop->getCurrentUnitsCount() }} / {{ $shop->getAllowedUnits() }} un.
                                    @endif
                                </div>
                                <div class="mt-0.5 text-xs">
                                    @if($shop->hasUnrestrictedAccess())
                                        <span class="text-purple-600 dark:text-purple-400 font-semibold">Acesso Total Liberado</span>
                                    @elseif($shop->canAddBranch())
                                        <span class="text-emerald-600 dark:text-emerald-400">{{ $shop->getRemainingUnits() }} restante(s)</span>
                                    @else
                                        <span class="text-amber-600 dark:text-amber-400 font-semibold">Limite do plano atingido</span>
                                    @endif
                                </div>
                            </div>

                            <div class="min-w-0 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                                <div class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Barbeiros</div>
                                <div class="mt-1 text-base font-bold text-gray-950 dark:text-white">{{ $activeProfessionalsCount }} ativos</div>
                                <div class="mt-0.5 text-xs text-emerald-600 dark:text-emerald-400">Cadeiras ilimitadas</div>
                            </div>

                            <div class="min-w-0 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                                <div class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Ciclo</div>
                                <div class="mt-1 text-base font-bold text-gray-950 dark:text-white">{{ $currentPlan?->cycle_label ?: 'Mensal (30 dias)' }}</div>
                                <button type="button" x-on:click="activeTab = 'planos'" class="mt-0.5 cursor-pointer text-xs font-semibold text-primary-600 hover:underline dark:text-primary-400">
                                    Ver planos →
                                </button>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Bloco de Pagamento via Pix --}}
                @if($this->billingSettings && $this->paymentSectionVisible && $this->pixPaymentDetails)
                    @php $payment = $this->latestPaymentSubmission; @endphp
                    <section id="billing-payment" class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <div class="mb-5">
                            <h2 class="text-xl font-bold text-gray-950 dark:text-white">Pagar mensalidade via Pix</h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                Valor: <strong class="text-gray-900 dark:text-gray-100">R$ {{ number_format($this->pixPaymentDetails['amount'], 2, ',', '.') }}</strong>.
                                Após o envio, a equipe Aurosoft validará o comprovante e renovará a licença da barbearia.
                            </p>
                        </div>

                        @if($payment?->status === 'pending')
                            <div class="mb-5 rounded-xl border border-warning-300 bg-warning-50 p-4 text-sm text-warning-900 dark:border-warning-700 dark:bg-warning-950/30 dark:text-warning-200">
                                Seu comprovante enviado em {{ $payment->created_at?->format('d/m/Y H:i') }} está aguardando validação. Você poderá enviar outro após a análise.
                            </div>
                        @elseif($payment?->status === 'rejected')
                            <div class="mb-5 rounded-xl border border-danger-300 bg-danger-50 p-4 text-sm text-danger-900 dark:border-danger-700 dark:bg-danger-950/30 dark:text-danger-200">
                                O comprovante anterior não foi aprovado. {{ $payment->review_note ?: 'Confira os dados do pagamento e envie um novo comprovante.' }}
                            </div>
                        @elseif(! $isValid)
                            <div class="mb-5 rounded-xl border border-danger-300 bg-danger-50 p-4 text-sm text-danger-900 dark:border-danger-700 dark:bg-danger-950/30 dark:text-danger-200">
                                A licença está vencida ou suspensa. O painel administrativo será liberado assim que o comprovante for validado.
                            </div>
                        @endif

                        <div class="grid gap-6 md:grid-cols-[220px_1fr]">
                            <div class="flex flex-col items-center justify-center rounded-xl border border-gray-200 bg-white p-3">
                                <img src="{{ $this->pixPaymentDetails['qr_code_uri'] }}" alt="QR Code Pix da mensalidade" class="h-48 w-48 object-contain" />
                                <span class="mt-2 text-[11px] font-semibold text-gray-500">Escaneie no app do banco</span>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <label for="saas-pix-key" class="mb-1 block text-sm font-semibold text-gray-700 dark:text-gray-300">Chave Pix</label>
                                    <div class="flex gap-2">
                                        <input
                                            id="saas-pix-key"
                                            readonly
                                            value="{{ $this->pixPaymentDetails['key'] }}"
                                            class="min-w-0 flex-1 rounded-lg border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                                        />
                                        <x-filament::button
                                            color="gray"
                                            type="button"
                                            x-on:click="navigator.clipboard.writeText(@js($this->pixPaymentDetails['key'])); copiedKey = true; setTimeout(() => copiedKey = false, 2000)"
                                        >
                                            <span x-show="!copiedKey">Copiar</span>
                                            <span x-show="copiedKey" x-cloak>Copiado!</span>
                                        </x-filament::button>
                                    </div>
                                </div>

                                <div>
                                    <label for="saas-pix-payload" class="mb-1 block text-sm font-semibold text-gray-700 dark:text-gray-300">Pix copia e cola</label>
                                    <textarea
                                        id="saas-pix-payload"
                                        readonly
                                        rows="3"
                                        class="w-full rounded-lg border-gray-300 bg-gray-50 p-2.5 font-mono text-xs text-gray-900 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                                    >{{ $this->pixPaymentDetails['payload'] }}</textarea>
                                    <x-filament::button
                                        class="mt-2"
                                        color="gray"
                                        type="button"
                                        x-on:click="navigator.clipboard.writeText(@js($this->pixPaymentDetails['payload'])); copiedPayload = true; setTimeout(() => copiedPayload = false, 2000)"
                                    >
                                        <span x-show="!copiedPayload">Copiar código Pix</span>
                                        <span x-show="copiedPayload" x-cloak>Código copiado!</span>
                                    </x-filament::button>
                                </div>

                                @if($this->billingSettings->payment_instructions)
                                    <p class="whitespace-pre-line text-sm text-gray-600 dark:text-gray-400">{{ $this->billingSettings->payment_instructions }}</p>
                                @endif

                                @if($supportPhone)
                                    <a
                                        class="inline-flex items-center gap-1.5 text-sm font-semibold text-primary-700 hover:underline dark:text-primary-300"
                                        href="https://wa.me/{{ $supportPhone }}?text={{ urlencode('Olá! Preciso de suporte sobre a assinatura da barbearia ' . $shop->name . ' (' . $shop->slug . ').') }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        Falar com o suporte pelo WhatsApp →
                                    </a>
                                @endif
                            </div>
                        </div>

                        @if($payment?->status !== 'pending')
                            <form
                                action="{{ route('tenant.billing.payments.store', ['tenant' => $shop->slug]) }}"
                                method="POST"
                                enctype="multipart/form-data"
                                class="mt-6 border-t border-gray-200 pt-5 dark:border-gray-700"
                            >
                                @csrf
                                <label for="payment-proof" class="mb-2 block text-sm font-semibold text-gray-800 dark:text-gray-200">
                                    Enviar comprovante (PDF, JPG ou PNG; até 10 MB)
                                </label>
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                                    <input
                                        id="payment-proof"
                                        name="proof"
                                        type="file"
                                        accept=".pdf,.jpg,.jpeg,.png"
                                        required
                                        class="block w-full text-sm text-gray-700 file:mr-4 file:rounded-lg file:border-0 file:bg-gray-100 file:px-4 file:py-2 file:font-semibold dark:text-gray-300 dark:file:bg-gray-800"
                                    />
                                    <x-filament::button type="submit" color="primary" class="shrink-0">
                                        Enviar para validação
                                    </x-filament::button>
                                </div>
                                @error('proof')
                                    <p class="mt-2 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p>
                                @enderror
                            </form>
                        @endif
                    </section>
                @elseif($this->billingSettings && ! $this->paymentSectionVisible)
                    <section id="billing-payment" class="flex flex-col gap-3 rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-900 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="font-bold text-gray-950 dark:text-white">Pagamento via Pix</h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                @if($expiresAt)
                                    A opção de pagamento aparecerá automaticamente 5 dias antes do vencimento, em {{ $expiresAt->format('d/m/Y') }}. Se preferir, você pode antecipar.
                                @else
                                    Sua licença não tem vencimento configurado. Se desejar pagar antes, você pode antecipar o pagamento.
                                @endif
                            </p>
                        </div>
                        <x-filament::button color="gray" wire:click="showEarlyPayment">
                            Antecipar pagamento
                        </x-filament::button>
                    </section>
                @else
                    <section id="billing-payment" class="flex flex-col gap-3 rounded-2xl border border-warning-300 bg-warning-50 p-5 text-sm text-warning-900 dark:border-warning-700 dark:bg-warning-950/30 dark:text-warning-200 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            O pagamento via Pix ainda não foi habilitado no sistema. Entre em contato com o suporte Aurosoft para renovar ou regularizar sua licença.
                        </div>
                        @if($supportPhone)
                            <x-filament::button
                                tag="a"
                                href="https://wa.me/{{ $supportPhone }}?text={{ urlencode('Olá! Gostaria de renovar a licença da barbearia ' . $shop->name . ' (' . $shop->slug . ').') }}"
                                target="_blank"
                                color="success"
                                size="sm"
                                class="shrink-0"
                            >
                                Falar no WhatsApp
                            </x-filament::button>
                        @endif
                    </section>
                @endif
            </div>

            {{-- ABA 2: PLANOS & UPGRADE --}}
            <div x-show="activeTab === 'planos'" x-cloak id="comparativo-planos" class="space-y-4">
                <div>
                    <h2 class="text-xl font-bold text-gray-950 dark:text-white">Planos Disponíveis (Barbeiros Ilimitados)</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Compare os ciclos e condições disponíveis. Uma solicitação não altera sua licença automaticamente — nossa equipe alinhará a mudança com você.
                    </p>
                </div>

                @if($openRequest)
                    <div class="rounded-xl border border-info-300 bg-info-50 p-4 text-sm text-info-900 dark:border-info-700 dark:bg-info-950/30 dark:text-info-200">
                        Você possui uma solicitação aberta para o plano <strong>{{ $openRequest->requestedPlan?->name ?? 'selecionado' }}</strong> (enviada em {{ $openRequest->created_at?->format('d/m/Y H:i') }}). Nossa equipe entrará em contato em breve.
                    </div>
                @endif

                <div class="grid gap-5 lg:grid-cols-3">
                    @foreach($this->activePlans as $plan)
                        @php
                            $isCurrent = (int) $shop->saas_plan_id === (int) $plan->id;
                        @endphp
                        <article @class([
                            'flex flex-col rounded-2xl border bg-white p-5 dark:bg-gray-900',
                            'border-primary-500 ring-1 ring-primary-500' => $isCurrent,
                            'border-gray-200 dark:border-gray-700' => ! $isCurrent,
                        ])>
                            <div class="flex items-center justify-between gap-2">
                                <h3 class="text-lg font-bold text-gray-950 dark:text-white">{{ $plan->name }}</h3>
                                @if($isCurrent)
                                    <x-filament::badge color="success">Plano atual</x-filament::badge>
                                @elseif($plan->badge)
                                    <x-filament::badge color="warning">{{ $plan->badge }}</x-filament::badge>
                                @endif
                            </div>

                            @if($plan->description)
                                <p class="mt-2 min-h-10 text-sm text-gray-500 dark:text-gray-400">{{ $plan->description }}</p>
                            @endif

                            <div class="mt-4">
                                <p class="text-2xl font-black text-gray-950 dark:text-white">
                                    R$ {{ number_format((float) $plan->monthly_price, 2, ',', '.') }}<span class="text-sm font-medium text-gray-500">/mês</span>
                                </p>
                                @if($plan->billing_cycle_months > 1)
                                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                        Ciclo {{ strtolower($plan->cycle_label) }}: R$ {{ number_format($plan->cycle_total, 2, ',', '.') }}
                                    </p>
                                @endif
                                <div class="mt-2 inline-flex items-center gap-1.5 rounded-md bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                    <x-filament::icon icon="heroicon-m-building-storefront" class="h-3.5 w-3.5 text-primary-500" />
                                    <span>Gestão de até {{ $plan->max_units }} {{ $plan->max_units === 1 ? 'unidade' : 'unidades' }}</span>
                                </div>
                            </div>

                            @if(! empty($plan->features))
                                <ul class="mt-5 flex-1 space-y-2 border-t border-gray-200 pt-4 text-sm text-gray-700 dark:border-gray-800 dark:text-gray-300">
                                    @foreach($plan->features as $feature)
                                        <li class="flex items-start gap-2">
                                            <span class="font-bold text-emerald-500">✓</span>
                                            <span>{{ $feature }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <div class="flex-1"></div>
                            @endif

                            <div class="mt-6 flex flex-col gap-2">
                                @if($isCurrent)
                                    <x-filament::button color="gray" disabled>
                                        Seu plano atual
                                    </x-filament::button>
                                @else
                                    <x-filament::button
                                        color="primary"
                                        wire:click="requestPlanChange({{ $plan->id }})"
                                        wire:confirm="Enviar solicitação para alterar a assinatura de {{ $shop->name }} para o {{ $plan->name }}? Seu plano atual não será alterado automaticamente."
                                    >
                                        Solicitar {{ $plan->name }}
                                    </x-filament::button>

                                    @if($supportPhone)
                                        <a
                                            href="https://wa.me/{{ $supportPhone }}?text={{ urlencode('Olá! Gostaria de alterar a assinatura da barbearia ' . $shop->name . ' (' . $shop->slug . ') para o ' . $plan->name . '.') }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="text-center text-xs font-semibold text-gray-500 hover:text-primary-600 dark:text-gray-400 dark:hover:text-primary-400"
                                        >
                                            Ou falar diretamente no WhatsApp →
                                        </a>
                                    @endif
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>

            {{-- ABA 3: HISTÓRICO DE PAGAMENTOS --}}
            <div x-show="activeTab === 'historico'" x-cloak class="space-y-4">
                <div>
                    <h2 class="text-xl font-bold text-gray-950 dark:text-white">Histórico de Comprovantes &amp; Pagamentos</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Acompanhe o status dos comprovantes Pix enviados pela sua barbearia.
                    </p>
                </div>

                @if($submissions->isEmpty())
                    <div class="rounded-2xl border border-gray-200 bg-white p-8 text-center text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400">
                        Nenhum comprovante enviado até o momento.
                    </div>
                @else
                    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                                <thead class="bg-gray-50 dark:bg-gray-800/60">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Enviado em</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Plano</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Valor</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Situação</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Observação</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                                    @foreach($submissions as $item)
                                        <tr>
                                            <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
                                                {{ $item->created_at?->format('d/m/Y H:i') }}
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-gray-300">
                                                {{ $item->saasPlan?->name ?: 'Assinatura' }}
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 font-semibold text-gray-900 dark:text-white">
                                                R$ {{ number_format((float) $item->amount, 2, ',', '.') }}
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3">
                                                @if($item->status === 'approved')
                                                    <x-filament::badge color="success">Aprovado</x-filament::badge>
                                                @elseif($item->status === 'rejected')
                                                    <x-filament::badge color="danger">Recusado</x-filament::badge>
                                                @else
                                                    <x-filament::badge color="warning">Aguardando validação</x-filament::badge>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-400">
                                                {{ $item->review_note ?: '—' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif
</x-filament-panels::page>

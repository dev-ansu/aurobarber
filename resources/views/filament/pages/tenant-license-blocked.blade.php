<x-filament-panels::page>
    @php
        $barbershop = $this->getBarbershop();
        $cleanPhone = app(\App\Services\SaasSupportContact::class)->phoneDigits();
        $barberName = $barbershop?->name ?? 'Barbearia';
        $waMessage = urlencode("Olá! Estou no painel do AuroBarber da barbearia '{$barberName}' e gostaria de regularizar a nossa licença de acesso.");
    @endphp

    <div class="mx-auto my-8 flex w-full max-w-2xl flex-col gap-6">
        {{-- Card Principal de Bloqueio --}}
        <div class="flex flex-col items-center gap-5 rounded-2xl border border-danger-500/30 bg-white p-8 text-center shadow-lg ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            {{-- Ícone de Bloqueio --}}
            <div class="flex h-16 w-16 items-center justify-center rounded-2xl border border-danger-500/30 bg-danger-50 text-danger-600 dark:bg-danger-500/15 dark:text-danger-400">
                <x-filament::icon icon="heroicon-o-lock-closed" class="h-8 w-8" />
            </div>

            <div class="space-y-2">
                <x-filament::badge color="danger" size="lg">Licença de Software Suspensa</x-filament::badge>
                <h2 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
                    {{ $barberName }}
                </h2>
                <p class="text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                    O acesso ao painel de agendamentos, clientes e financeiro desta unidade foi suspenso temporariamente devido à pendência na renovação da licença.
                </p>
            </div>

            {{-- Detalhes da Licença --}}
            <div class="w-full space-y-2.5 rounded-xl border border-gray-200 bg-gray-50 p-4 text-left text-sm dark:border-white/10 dark:bg-white/5">
                <div class="flex items-center justify-between border-b border-gray-200 pb-2 dark:border-white/10">
                    <span class="font-medium text-gray-500 dark:text-gray-400">Status do Sistema:</span>
                    <span class="inline-flex items-center gap-1.5 font-bold text-danger-600 dark:text-danger-400">
                        <span class="h-2 w-2 rounded-full bg-danger-500"></span>
                        Bloqueado por Inadimplência
                    </span>
                </div>

                <div class="flex items-center justify-between border-b border-gray-200 pb-2 dark:border-white/10">
                    <span class="font-medium text-gray-500 dark:text-gray-400">Vencimento Registrado:</span>
                    <span class="font-mono font-semibold text-gray-950 dark:text-white">
                        {{ $barbershop?->license_expires_at ? $barbershop->license_expires_at->format('d/m/Y') : 'Não definido' }}
                    </span>
                </div>

                @if(!empty($barbershop?->suspension_reason))
                    <div class="flex flex-col gap-1 pt-1">
                        <span class="font-medium text-gray-500 dark:text-gray-400">Motivo Registrado:</span>
                        <span class="italic text-gray-700 dark:text-gray-200">
                            {{ $barbershop->suspension_reason }}
                        </span>
                    </div>
                @endif
            </div>

            {{-- Botões de Ação --}}
            <div class="mt-2 flex w-full flex-col gap-3">
                @if($barbershop)
                    <x-filament::button
                        tag="a"
                        href="{{ \App\Filament\Pages\ClientSubscriptionPage::getUrl(['tenant' => $barbershop->slug]) }}#billing-payment"
                        color="primary"
                        size="lg"
                        icon="heroicon-o-qr-code"
                        class="w-full"
                    >
                        Pagar via Pix / Enviar Comprovante
                    </x-filament::button>
                @endif

                @if(!empty($cleanPhone))
                    <x-filament::button
                        tag="a"
                        href="https://wa.me/{{ $cleanPhone }}?text={{ $waMessage }}"
                        target="_blank"
                        color="success"
                        size="lg"
                        icon="heroicon-o-chat-bubble-left-right"
                        class="w-full"
                    >
                        Falar com o Financeiro no WhatsApp
                    </x-filament::button>
                @endif

                <x-filament::button
                    tag="a"
                    href="{{ request()->url() }}"
                    color="gray"
                    size="lg"
                    icon="heroicon-o-arrow-path"
                    class="w-full"
                >
                    Verificar Regularização Agora
                </x-filament::button>
            </div>

            {{-- Logout --}}
            <form method="POST" action="{{ filament()->getLogoutUrl() }}" class="mt-1">
                @csrf
                <button type="submit" class="text-xs font-semibold text-gray-500 underline transition hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    Sair da Conta (Logout)
                </button>
            </form>
        </div>

        {{-- Rodapé Informativo --}}
        <div class="text-center text-xs text-gray-500 dark:text-gray-400">
            AuroBarber • Plataforma de Gestão Inteligente para Barbearias e Salões
        </div>
    </div>
</x-filament-panels::page>

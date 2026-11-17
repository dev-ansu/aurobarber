<x-filament-panels::page>
    @php
        $shop = $this->barbershop;
        $supportPhone = preg_replace('/\D/', '', $this->supportPhone);
        $expiresAt = $shop?->license_expires_at ? \Carbon\Carbon::parse($shop->license_expires_at) : null;
        $isInGrace = $shop?->isInGracePeriod() ?? false;
        $isValid = $shop?->isLicenseActive() ?? true;
    @endphp

    @if($shop)
        <x-filament::section
            heading="Status Atual da Licença"
            description="Acompanhe a vigência da assinatura da sua barbearia e canais de renovação."
            icon="heroicon-o-shield-check"
        >
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
                <div style="background: #0f172a; border: 1px solid #1e293b; border-radius: 14px; padding: 16px;">
                    <div style="font-size: 11px; text-transform: uppercase; font-weight: 800; color: #94a3b8;">Unidade Licenciada</div>
                    <div style="font-size: 18px; font-weight: 900; color: #ffffff; margin-top: 4px;">{{ $shop->name }}</div>
                    <div style="font-size: 12px; color: #64748b; margin-top: 2px;">/{{ $shop->slug }}</div>
                </div>

                <div style="background: #0f172a; border: 1px solid #1e293b; border-radius: 14px; padding: 16px;">
                    <div style="font-size: 11px; text-transform: uppercase; font-weight: 800; color: #94a3b8;">Status da Assinatura</div>
                    <div style="margin-top: 8px;">
                        @if($isInGrace)
                            <x-filament::badge color="warning" size="lg">Em Período de Carência (5 dias)</x-filament::badge>
                        @elseif($isValid)
                            <x-filament::badge color="success" size="lg">Ativa & Regular</x-filament::badge>
                        @else
                            <x-filament::badge color="danger" size="lg">Suspensa / Vencida</x-filament::badge>
                        @endif
                    </div>
                </div>

                <div style="background: #0f172a; border: 1px solid #1e293b; border-radius: 14px; padding: 16px;">
                    <div style="font-size: 11px; text-transform: uppercase; font-weight: 800; color: #94a3b8;">Vencimento Atual</div>
                    <div style="font-size: 18px; font-weight: 900; color: #fbbf24; margin-top: 4px;">
                        {{ $expiresAt ? $expiresAt->format('d/m/Y') : 'Vitalícia / Isenta' }}
                    </div>
                    <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                        Valor atual: R$ {{ number_format((float) ($shop->monthly_price ?: 89.90), 2, ',', '.') }} • Barbeiros Ilimitados
                    </div>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section
            heading="Planos Disponíveis (Barbeiros Ilimitados)"
            description="Todos os planos incluem cadeiras/barbeiros ilimitados, agendamento online, controle de caixa, comissões e estoque."
            icon="heroicon-o-sparkles"
        >
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px;">
                @foreach([
                    ['name' => 'Plano Mensal', 'price' => '89,90', 'period' => '/mês', 'desc' => 'Renovação mensal flexível sem fidelidade.', 'badge' => 'Flexível'],
                    ['name' => 'Plano Semestral', 'price' => '79,90', 'period' => '/mês', 'desc' => 'Economia garantida no ciclo de 6 meses.', 'badge' => 'Mais Popular'],
                    ['name' => 'Plano Anual', 'price' => '69,90', 'period' => '/mês', 'desc' => 'Maior desconto para parceria anual.', 'badge' => 'Maior Economia'],
                ] as $plan)
                    <div style="background: #0f172a; border: 1px solid #1e293b; border-radius: 16px; padding: 20px; display: flex; flex-direction: column; justify-content: space-between; gap: 16px;">
                        <div>
                            <x-filament::badge color="warning">{{ $plan['badge'] }}</x-filament::badge>
                            <h3 style="font-size: 17px; font-weight: 900; color: #ffffff; margin: 10px 0 4px 0;">{{ $plan['name'] }}</h3>
                            <div style="font-size: 26px; font-weight: 900; color: #fbbf24;">
                                R$ {{ $plan['price'] }} <span style="font-size: 12px; color: #94a3b8; font-weight: 500;">{{ $plan['period'] }}</span>
                            </div>
                            <p style="font-size: 12px; color: #94a3b8; margin-top: 8px;">{{ $plan['desc'] }}</p>
                        </div>

                        <x-filament::button
                            tag="a"
                            href="https://wa.me/{{ $supportPhone }}?text={{ urlencode('Olá! Gostaria de renovar/alterar a assinatura da barbearia ' . $shop->name . ' (' . $shop->slug . ') para o ' . $plan['name'] . '.') }}"
                            target="_blank"
                            color="success"
                            icon="heroicon-o-chat-bubble-left-right"
                        >
                            Solicitar / Enviar Comprovante
                        </x-filament::button>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>

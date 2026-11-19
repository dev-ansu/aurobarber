@props([
    'data' => [],
])

@php
    $badge = $data['section_badge'] ?? 'A Evolução da Sua Barbearia';
    $title = $data['title'] ?? 'Por que as barbearias mais lucrativas estão migrando para o AuroBarber?';
    $subtitle = $data['subtitle'] ?? 'Compare a rotina de quem ainda depende de caderno ou aplicativos engessados versus a liberdade do AuroBarber Cloud.';
    $items = $data['items'] ?? [
        [
            'feature' => 'Agendamento pelo Cliente',
            'traditional_value' => 'Mensagens infinitas no WhatsApp, cliente desiste por demora no retorno',
            'aurobarber_value' => 'Link White-Label no Instagram, agendamento em 30 segundos 24h por dia'
        ],
        [
            'feature' => 'Taxas sobre Faturamento',
            'traditional_value' => 'Apps tradicionais cobram 10% a 20% sobre cada serviço ou cliente agendado',
            'aurobarber_value' => '0% de taxa por corte ou Pix. 100% do dinheiro da bancada fica no seu bolso'
        ],
        [
            'feature' => 'Fechamento de Comissões',
            'traditional_value' => 'Caderno rasurado, desconfiança de barbeiro e horas de cálculos no sábado',
            'aurobarber_value' => 'Cálculo automático em 1 clique com recibo jurídico impresso/assinado'
        ],
        [
            'feature' => 'Privacidade da Equipe',
            'traditional_value' => 'Barbeiros vendo o caixa geral da barbearia ou faturamento dos colegas',
            'aurobarber_value' => 'Visão restrita no celular: cada profissional só vê sua agenda e seu extrato'
        ],
        [
            'feature' => 'Consumo de Bar & Produtos',
            'traditional_value' => 'Cervejas sumindo da geladeira e pomadas vendidas sem registro na comanda',
            'aurobarber_value' => 'Comanda integrada ao corte com baixa imediata no estoque e markup de lucro'
        ],
        [
            'feature' => 'Retenção de Clientes',
            'traditional_value' => 'Cartão de visita com carimbo de papel que o cliente sempre perde na carteira',
            'aurobarber_value' => 'Cartão Fidelidade 100% digital com alerta inteligente de clientes ausentes há +20 dias'
        ],
    ];
@endphp

<section id="comparativo" class="py-20 sm:py-28 bg-slate-900/60 border-b border-slate-800/80">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-16">
            <span class="text-xs font-bold text-amber-400 uppercase tracking-widest">{{ $badge }}</span>
            <h2 class="text-2xl sm:text-4xl font-black text-white mt-1.5 tracking-tight">
                {{ $title }}
            </h2>
            <p class="text-sm sm:text-base text-slate-400 mt-3 leading-relaxed">
                {{ $subtitle }}
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-5xl mx-auto">
            {{-- Coluna 1: O Jeito Antigo --}}
            <div class="rounded-3xl border border-rose-500/30 bg-slate-950/80 p-8 space-y-6">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-2xl bg-rose-500/15 text-rose-400 font-black text-xl flex items-center justify-center shrink-0">✕</div>
                    <div>
                        <h3 class="text-lg font-black text-white">WhatsApp Manual, Caderno ou Apps Caros</h3>
                        <span class="text-xs text-rose-400 font-semibold">Tempo perdido, furos na agenda e taxas ocultas</span>
                    </div>
                </div>

                <ul class="space-y-4 pt-2">
                    @foreach($items as $item)
                        <li class="flex items-start gap-3 text-xs text-slate-400 leading-relaxed">
                            <span class="text-rose-500 font-bold shrink-0 mt-0.5">✕</span>
                            <div>
                                <strong class="text-slate-300 block mb-0.5">{{ $item['feature'] }}:</strong>
                                {{ $item['traditional_value'] }}
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Coluna 2: Com o AuroBarber --}}
            <div class="rounded-3xl border-2 border-emerald-500/50 bg-slate-950 p-8 space-y-6 shadow-2xl shadow-emerald-500/5 relative">
                <div class="absolute -top-3.5 right-6 px-3.5 py-1 rounded-full bg-emerald-500 text-slate-950 font-black text-[10px] uppercase tracking-wider shadow">
                    Alta Performance
                </div>

                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-2xl bg-emerald-500/20 text-emerald-400 font-black text-xl flex items-center justify-center shrink-0">✓</div>
                    <div>
                        <h3 class="text-lg font-black text-white">AuroBarber Cloud</h3>
                        <span class="text-xs text-emerald-400 font-semibold">Agenda cheia, comissão justa e zero dor de cabeça</span>
                    </div>
                </div>

                <ul class="space-y-4 pt-2">
                    @foreach($items as $item)
                        <li class="flex items-start gap-3 text-xs text-slate-300 leading-relaxed">
                            <span class="text-emerald-400 font-bold shrink-0 mt-0.5">✓</span>
                            <div>
                                <strong class="text-white block mb-0.5">{{ $item['feature'] }}:</strong>
                                {{ $item['aurobarber_value'] }}
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</section>

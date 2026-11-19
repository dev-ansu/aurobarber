@props([
    'data' => [],
])

@php
    $badge = $data['section_badge'] ?? 'Tire suas dúvidas';
    $title = $data['title'] ?? 'Perguntas frequentes sobre o AuroBarber';
    $subtitle = $data['subtitle'] ?? 'Tudo o que você precisa saber antes de transformar a gestão da sua barbearia.';
    $faqs = $data['faqs'] ?? [
        [
            'question' => 'O cliente precisa baixar algum aplicativo na Play Store ou Apple Store?',
            'answer' => 'Não! Esse é um dos maiores diferenciais do AuroBarber. O seu cliente acessa um link direto e leve (ex: barber.aurosoft.com.br/suabarbearia), sem senha e sem atrito. Se quiser, ele pode instalar o atalho PWA direto no celular em 1 toque.'
        ],
        [
            'question' => 'O AuroBarber cobra porcentagem sobre meus cortes ou sobre agendamentos?',
            'answer' => 'Zero por cento! Você paga apenas a mensalidade fixa do plano escolhido. Todo o dinheiro dos cortes, Pix e comandas vai 100% direto para o caixa da sua barbearia.'
        ],
        [
            'question' => 'Os barbeiros conseguem ver o faturamento total da barbearia?',
            'answer' => 'Não. Ao ativar a Visão Restrita para o login do barbeiro, ele só consegue visualizar os agendamentos da própria cadeira e o valor das próprias comissões. Os relatórios gerenciais e o lucro total da barbearia são visíveis exclusivamente para você (dono ou gerente).'
        ],
        [
            'question' => 'Como funciona o cálculo de comissões e a segurança jurídica?',
            'answer' => 'Você define as porcentagens de comissão de serviços e produtos para cada profissional. O sistema calcula automaticamente o valor bruto, desconta vales ou adiantamentos e emite um recibo detalhado de prestação de serviços com base na Lei do Salão Parceiro para o profissional assinar.'
        ],
        [
            'question' => 'Consigo controlar a venda de bebidas, cervejas e pomadas?',
            'answer' => 'Sim! O AuroBarber conta com módulo de Comanda e Balcão completo. Enquanto o cliente corta o cabelo, você adiciona cervejas ou cosméticos na comanda dele, dando baixa em tempo real no seu estoque.'
        ],
        [
            'question' => 'Como funciona o teste grátis de 7 dias?',
            'answer' => 'Basta clicar em qualquer botão de contratação ou demonstração e preencher seus dados. Nossa equipe criará seu ambiente na hora para você testar com seus barbeiros e clientes sem nenhum compromisso.'
        ],
    ];
@endphp

<section id="faq" class="py-20 sm:py-28 bg-slate-950 border-b border-slate-800/80" x-data="{ openFaq: null }">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-12">
            <span class="text-xs font-bold text-amber-400 uppercase tracking-widest">{{ $badge }}</span>
            <h2 class="text-2xl sm:text-4xl font-black text-white mt-1.5 tracking-tight">
                {{ $title }}
            </h2>
            <p class="text-sm sm:text-base text-slate-400 mt-3 leading-relaxed">
                {{ $subtitle }}
            </p>
        </div>

        <div class="space-y-3.5">
            @foreach($faqs as $index => $faq)
                <div class="rounded-2xl border border-slate-800/90 bg-slate-900/60 overflow-hidden transition">
                    <button
                        type="button"
                        @click="openFaq = (openFaq === {{ $index }} ? null : {{ $index }})"
                        class="w-full p-5 text-left flex items-center justify-between gap-4 cursor-pointer hover:bg-slate-900 transition"
                    >
                        <span class="text-sm sm:text-base font-bold text-white">{{ $faq['question'] ?? '' }}</span>
                        <span
                            class="text-amber-400 font-black text-lg transition-transform shrink-0"
                            :class="openFaq === {{ $index }} ? 'rotate-45' : ''"
                        >
                            +
                        </span>
                    </button>
                    <div
                        x-show="openFaq === {{ $index }}"
                        x-cloak
                        x-transition
                        class="px-5 pb-5 pt-1 text-xs sm:text-sm text-slate-300 leading-relaxed border-t border-slate-800/60"
                    >
                        {!! nl2br(e($faq['answer'] ?? '')) !!}
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

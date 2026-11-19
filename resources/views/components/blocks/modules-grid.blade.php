@props([
    'data' => [],
])

@php
    $badge = $data['section_badge'] ?? 'Ecossistema Completo';
    $title = $data['title'] ?? 'Tudo o que sua barbearia precisa em uma única assinatura';
    $subtitle = $data['subtitle'] ?? 'Sem cobrança extra por módulo e sem taxas escondidas sobre os seus cortes.';
    $modules = $data['modules'] ?? [
        [
            'icon' => '📱',
            'title' => 'Página Pública & App PWA',
            'tag' => 'Agendamento',
            'description' => 'Link próprio White-Label com suas cores e logotipo, instalável na tela inicial do celular dos seus clientes.'
        ],
        [
            'icon' => '💰',
            'title' => 'Comissões & Recibos',
            'tag' => 'Financeiro',
            'description' => 'Cálculo automático de comissões por barbeiro e emissão de recibo jurídico assinado para segurança trabalhista.'
        ],
        [
            'icon' => '🍺',
            'title' => 'Bar, Produtos & Estoque',
            'tag' => 'Balcão',
            'description' => 'Controle de bebidas, pomadas e insumos com alerta de estoque baixo e lançamento direto na comanda do corte.'
        ],
        [
            'icon' => '🎁',
            'title' => 'Cartão Fidelidade & CRM',
            'tag' => 'Fidelização',
            'description' => 'Selos automáticos a cada visita, alerta de clientes sumidos há +20 dias e lista de aniversariantes do mês.'
        ],
        [
            'icon' => '💈',
            'title' => 'Acesso Restrito do Barbeiro',
            'tag' => 'Equipe',
            'description' => 'Cada colaborador acessa pelo próprio celular apenas sua agenda e extrato de comissões, sem ver o caixa da casa.'
        ],
        [
            'icon' => '🏦',
            'title' => 'Fechamento de Caixa Diário',
            'tag' => 'Fluxo de Caixa',
            'description' => 'Controle de abertura de gaveta, sangrias, suprimentos e conferência por PIX, Dinheiro, Crédito e Débito.'
        ],
        [
            'icon' => '⭐',
            'title' => 'Pesquisa 5 Estrelas & Ranking',
            'tag' => 'Qualidade',
            'description' => 'Link pós-atendimento para o cliente avaliar de 1 a 5 estrelas e relatórios de desempenho da equipe.'
        ],
        [
            'icon' => '🛡️',
            'title' => 'Segurança 2FA + 30 Dias',
            'tag' => 'Segurança',
            'description' => 'Autenticação em 2 fatores (App ou E-mail) com opção de lembrar seu dispositivo confiável por 30 dias.'
        ],
    ];
@endphp

<section id="modulos" class="py-20 sm:py-28 bg-slate-950 border-b border-slate-800/80 relative">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-14">
            <span class="text-xs font-bold text-amber-400 uppercase tracking-widest">{{ $badge }}</span>
            <h2 class="text-2xl sm:text-4xl font-black text-white mt-1.5 tracking-tight">
                {{ $title }}
            </h2>
            <p class="text-sm sm:text-base text-slate-400 mt-3 leading-relaxed">
                {{ $subtitle }}
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
            @foreach($modules as $mod)
                <div class="rounded-2xl border border-slate-800/90 bg-slate-900/60 p-6 hover:border-amber-500/40 hover:bg-slate-900 transition-all duration-300 group">
                    <div class="h-12 w-12 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center text-2xl mb-4 group-hover:scale-110 transition-transform">
                        {{ $mod['icon'] ?? '✂️' }}
                    </div>
                    @if(!empty($mod['tag']))
                        <span class="text-[10px] font-bold uppercase tracking-wider text-amber-400/80 mb-1 block">
                            {{ $mod['tag'] }}
                        </span>
                    @endif
                    <h3 class="text-base font-bold text-white group-hover:text-amber-400 transition">
                        {{ $mod['title'] ?? '' }}
                    </h3>
                    <p class="text-xs text-slate-400 mt-2 leading-relaxed">
                        {{ $mod['description'] ?? '' }}
                    </p>
                </div>
            @endforeach
        </div>
    </div>
</section>

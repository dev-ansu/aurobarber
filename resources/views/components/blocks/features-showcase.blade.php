@props([
    'data' => [],
])

@php
    $badge = $data['section_badge'] ?? 'Tecnologia Desenhada para a Bancada';
    $title = $data['title'] ?? 'Conheça por dentro o sistema que organiza sua equipe e fideliza seus clientes';
    $subtitle = $data['subtitle'] ?? 'Navegue pelas abas abaixo e veja como o AuroBarber resolve desde o agendamento no Instagram até o fechamento das comissões no sábado à noite.';
    $customFeatures = $data['features'] ?? [];
@endphp

<section id="recursos" class="py-20 sm:py-28 bg-slate-950 border-b border-slate-800/80 relative" x-data="{ activeShowcaseTab: 'agenda' }">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-12 sm:mb-16">
            <span class="text-xs font-bold text-amber-400 uppercase tracking-widest">{{ $badge }}</span>
            <h2 class="text-2xl sm:text-4xl font-black text-white mt-1.5 tracking-tight">
                {{ $title }}
            </h2>
            <p class="text-sm sm:text-base text-slate-400 mt-3 leading-relaxed">
                {{ $subtitle }}
            </p>
        </div>

        @if(!empty($customFeatures))
            {{-- Se configurado no CMS com itens personalizados --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach($customFeatures as $item)
                    <div class="rounded-3xl border border-slate-800 bg-slate-900/60 p-6 space-y-4 hover:border-amber-500/40 transition">
                        @if(!empty($item['badge']))
                            <span class="inline-block px-3 py-1 rounded-full bg-amber-500/10 text-amber-400 text-[10px] font-black uppercase tracking-wider">
                                {{ $item['badge'] }}
                            </span>
                        @endif
                        <h3 class="text-xl font-black text-white">{{ $item['title'] ?? '' }}</h3>
                        <p class="text-xs text-slate-300 leading-relaxed">{{ $item['description'] ?? '' }}</p>
                        @if(!empty($item['benefits']) && is_array($item['benefits']))
                            <ul class="space-y-2 text-xs text-slate-300 pt-2 border-t border-slate-800">
                                @foreach($item['benefits'] as $b)
                                    <li class="flex items-center gap-2">
                                        <span class="text-emerald-400 font-bold">✓</span>
                                        <span>{{ $b }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        @if(!empty($item['image']))
                            <div class="mt-4 rounded-xl overflow-hidden border border-slate-800">
                                <img src="{{ asset('storage/' . $item['image']) }}" alt="{{ $item['title'] ?? '' }}" class="w-full object-cover">
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            {{-- Tour Interativo em Abas Padrão AuroBarber --}}
            <div class="flex flex-wrap items-center justify-center gap-2 mb-8">
                <button
                    type="button"
                    @click="activeShowcaseTab = 'agenda'"
                    :class="activeShowcaseTab === 'agenda' ? 'bg-amber-500 text-slate-950 shadow-lg shadow-amber-500/20' : 'bg-slate-900 text-slate-300 hover:bg-slate-800'"
                    class="px-4 py-2.5 rounded-xl font-bold text-xs transition cursor-pointer flex items-center gap-2"
                >
                    <span>📅</span>
                    <span>Agenda Diária & Encaixes</span>
                </button>

                <button
                    type="button"
                    @click="activeShowcaseTab = 'vitrine'"
                    :class="activeShowcaseTab === 'vitrine' ? 'bg-amber-500 text-slate-950 shadow-lg shadow-amber-500/20' : 'bg-slate-900 text-slate-300 hover:bg-slate-800'"
                    class="px-4 py-2.5 rounded-xl font-bold text-xs transition cursor-pointer flex items-center gap-2"
                >
                    <span>📱</span>
                    <span>Site White-Label & App PWA</span>
                </button>

                <button
                    type="button"
                    @click="activeShowcaseTab = 'comissoes'"
                    :class="activeShowcaseTab === 'comissoes' ? 'bg-amber-500 text-slate-950 shadow-lg shadow-amber-500/20' : 'bg-slate-900 text-slate-300 hover:bg-slate-800'"
                    class="px-4 py-2.5 rounded-xl font-bold text-xs transition cursor-pointer flex items-center gap-2"
                >
                    <span>💰</span>
                    <span>Comissões & Recibo Jurídico</span>
                </button>

                <button
                    type="button"
                    @click="activeShowcaseTab = 'comanda'"
                    :class="activeShowcaseTab === 'comanda' ? 'bg-amber-500 text-slate-950 shadow-lg shadow-amber-500/20' : 'bg-slate-900 text-slate-300 hover:bg-slate-800'"
                    class="px-4 py-2.5 rounded-xl font-bold text-xs transition cursor-pointer flex items-center gap-2"
                >
                    <span>🍺</span>
                    <span>Comanda de Bar & Estoque</span>
                </button>

                <button
                    type="button"
                    @click="activeShowcaseTab = 'fidelidade'"
                    :class="activeShowcaseTab === 'fidelidade' ? 'bg-amber-500 text-slate-950 shadow-lg shadow-amber-500/20' : 'bg-slate-900 text-slate-300 hover:bg-slate-800'"
                    class="px-4 py-2.5 rounded-xl font-bold text-xs transition cursor-pointer flex items-center gap-2"
                >
                    <span>🎁</span>
                    <span>Cartão Fidelidade & CRM</span>
                </button>

                <button
                    type="button"
                    @click="activeShowcaseTab = 'barbeiro'"
                    :class="activeShowcaseTab === 'barbeiro' ? 'bg-amber-500 text-slate-950 shadow-lg shadow-amber-500/20' : 'bg-slate-900 text-slate-300 hover:bg-slate-800'"
                    class="px-4 py-2.5 rounded-xl font-bold text-xs transition cursor-pointer flex items-center gap-2"
                >
                    <span>💈</span>
                    <span>Visão do Barbeiro</span>
                </button>
            </div>

            {{-- Painel de Conteúdo das Abas --}}
            <div class="rounded-3xl border border-slate-800 bg-slate-900/60 p-6 sm:p-10 shadow-2xl">
                {{-- ABA 1: AGENDA --}}
                <div x-show="activeShowcaseTab === 'agenda'" class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                    <div class="lg:col-span-5 space-y-4">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-500/10 text-amber-400 text-xs font-bold">
                            <span>⚡</span>
                            <span>Adeus Furos e Confusão na Recepção</span>
                        </div>
                        <h3 class="text-2xl sm:text-3xl font-black text-white leading-tight">
                            Agenda em Grade Dinâmica com Visão de Todas as Cadeiras
                        </h3>
                        <p class="text-sm text-slate-300 leading-relaxed">
                            Controle toda a barbearia em uma tela intuitiva. Visualize agendamentos por barbeiro, encaixes rápidos de balcão e intervalos de almoço sem sobreposição de horários.
                        </p>
                        <ul class="space-y-2.5 text-xs text-slate-300 pt-2">
                            <li class="flex items-center gap-2"><span class="text-emerald-400 font-bold">✓</span> <strong>Cores por Status:</strong> Pendente, Confirmado, Concluído e Cancelado em tempo real</li>
                            <li class="flex items-center gap-2"><span class="text-emerald-400 font-bold">✓</span> <strong>Bloqueio Inteligente:</strong> Horários de almoço e folgas respeitados automaticamente</li>
                            <li class="flex items-center gap-2"><span class="text-emerald-400 font-bold">✓</span> <strong>Histórico Completo:</strong> Veja serviços anteriores e preferências de cada cliente</li>
                        </ul>
                    </div>
                    <div class="lg:col-span-7">
                        <div class="rounded-2xl border border-slate-800 bg-slate-950 p-5 shadow-2xl space-y-3 font-mono text-xs">
                            <div class="flex items-center justify-between pb-3 border-b border-slate-800 text-slate-400">
                                <span class="font-bold text-white font-sans">Sábado, 15:30 — Agenda em Tempo Real</span>
                                <span class="text-amber-400 font-sans font-bold">4 Barbeiros em Atendimento</span>
                            </div>
                            <div class="space-y-2 font-sans">
                                <div class="p-3 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-amber-500/20 text-amber-400 flex items-center justify-center font-bold text-xs">C1</div>
                                        <div>
                                            <div class="font-bold text-white text-xs">Lucas Almeida • Corte + Barboterapia</div>
                                            <div class="text-[11px] text-slate-400">Barbeiro: Marcos Vinícius • R$ 85,00</div>
                                        </div>
                                    </div>
                                    <span class="px-2 py-1 rounded-md bg-emerald-500/20 text-emerald-300 text-[10px] font-bold">EM ANDAMENTO</span>
                                </div>
                                <div class="p-3 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-sky-500/20 text-sky-400 flex items-center justify-center font-bold text-xs">C2</div>
                                        <div>
                                            <div class="font-bold text-white text-xs">Rafael Santos • Degradê Navalhado</div>
                                            <div class="text-[11px] text-slate-400">Barbeiro: Gabriel Costa • R$ 45,00</div>
                                        </div>
                                    </div>
                                    <span class="px-2 py-1 rounded-md bg-amber-500/20 text-amber-300 text-[10px] font-bold">PRÓXIMO (15:45)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ABA 2: SITE E APP --}}
                <div x-show="activeShowcaseTab === 'vitrine'" x-cloak class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                    <div class="lg:col-span-5 space-y-4">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/10 text-emerald-400 text-xs font-bold">
                            <span>✨</span>
                            <span>Sua Marca em Primeiro Lugar</span>
                        </div>
                        <h3 class="text-2xl sm:text-3xl font-black text-white leading-tight">
                            Link Próprio White-Label e App PWA Instalável no Celular
                        </h3>
                        <p class="text-sm text-slate-300 leading-relaxed">
                            Coloque o link da sua barbearia na bio do Instagram (ex: <code>barber.aurosoft.com.br/suabarbearia</code>). O cliente escolhe barbeiro, serviço e horário em menos de 30 segundos, sem precisar baixar apps pesados.
                        </p>
                        <ul class="space-y-2.5 text-xs text-slate-300 pt-2">
                            <li class="flex items-center gap-2"><span class="text-emerald-400 font-bold">✓</span> <strong>Sua Logomarca e Cores:</strong> Experiência 100% personalizada com a identidade da sua marca</li>
                            <li class="flex items-center gap-2"><span class="text-emerald-400 font-bold">✓</span> <strong>App PWA no Celular:</strong> O cliente adiciona à tela inicial com 1 clique</li>
                            <li class="flex items-center gap-2"><span class="text-emerald-400 font-bold">✓</span> <strong>Confirmação Instantânea:</strong> Agendamento no banco sem duplicidade de horários</li>
                        </ul>
                    </div>
                    <div class="lg:col-span-7">
                        <div class="max-w-md mx-auto rounded-3xl border-2 border-slate-700 bg-slate-950 p-6 shadow-2xl space-y-4">
                            <div class="text-center space-y-2">
                                <div class="w-16 h-16 mx-auto rounded-2xl bg-amber-500/20 text-amber-400 flex items-center justify-center text-3xl font-black shadow-lg">💈</div>
                                <h4 class="text-base font-black text-white">Barbearia Navalha de Ouro</h4>
                                <p class="text-xs text-slate-400">Escolha o serviço e agende seu horário online em segundos</p>
                            </div>
                            <div class="space-y-2 pt-2">
                                <div class="p-3 rounded-xl bg-slate-900 border border-slate-800 flex justify-between items-center">
                                    <div>
                                        <div class="text-xs font-bold text-white">Corte Social / Degradê</div>
                                        <div class="text-[11px] text-slate-400">40 minutos</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-xs font-black text-amber-400">R$ 45,00</div>
                                        <span class="text-[10px] text-emerald-400 font-bold">Selecionar</span>
                                    </div>
                                </div>
                                <div class="p-3 rounded-xl bg-slate-900 border border-slate-800 flex justify-between items-center">
                                    <div>
                                        <div class="text-xs font-bold text-white">Barboterapia Completa</div>
                                        <div class="text-[11px] text-slate-400">Toalha quente + massagem</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-xs font-black text-amber-400">R$ 40,00</div>
                                        <span class="text-[10px] text-emerald-400 font-bold">Selecionar</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ABA 3: COMISSÕES --}}
                <div x-show="activeShowcaseTab === 'comissoes'" x-cloak class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                    <div class="lg:col-span-5 space-y-4">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/10 text-emerald-400 text-xs font-bold">
                            <span>⚖️</span>
                            <span>Segurança Jurídica Total</span>
                        </div>
                        <h3 class="text-2xl sm:text-3xl font-black text-white leading-tight">
                            Comissões por Barbeiro com Emissão de Recibo Jurídico
                        </h3>
                        <p class="text-sm text-slate-300 leading-relaxed">
                            Defina a porcentagem de comissão individual de cada profissional (ex: 50% serviços, 10% produtos). O sistema calcula tudo no fechamento e gera o recibo discriminado para assinatura do barbeiro parceiro.
                        </p>
                        <ul class="space-y-2.5 text-xs text-slate-300 pt-2">
                            <li class="flex items-center gap-2"><span class="text-emerald-400 font-bold">✓</span> <strong>Lei Salão Parceiro:</strong> Total conformidade para proteção do estabelecimento</li>
                            <li class="flex items-center gap-2"><span class="text-emerald-400 font-bold">✓</span> <strong>Desconto de Taxas e Adiantamentos:</strong> Abatimento automático de vales e sangrias</li>
                            <li class="flex items-center gap-2"><span class="text-emerald-400 font-bold">✓</span> <strong>Baixa em Lote:</strong> Feche a folha do sábado em 2 minutos sem erros de calculadora</li>
                        </ul>
                    </div>
                    <div class="lg:col-span-7">
                        <div class="rounded-2xl border border-slate-800 bg-slate-950 p-6 shadow-2xl space-y-4">
                            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                                <div>
                                    <div class="text-xs text-slate-400 font-bold uppercase tracking-wider">Extrato Semanal de Parceria</div>
                                    <div class="text-sm font-black text-white">Barbeiro: Marcos Vinícius</div>
                                </div>
                                <span class="px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-300 text-xs font-black">Pronto p/ Liquidação</span>
                            </div>
                            <div class="grid grid-cols-3 gap-3 text-center">
                                <div class="p-3 rounded-xl bg-slate-900 border border-slate-800">
                                    <div class="text-[11px] text-slate-400">Total Faturado</div>
                                    <div class="text-base font-black text-white font-mono mt-0.5">R$ 2.450,00</div>
                                </div>
                                <div class="p-3 rounded-xl bg-slate-900 border border-slate-800">
                                    <div class="text-[11px] text-slate-400">Taxa Parceria (50%)</div>
                                    <div class="text-base font-black text-amber-400 font-mono mt-0.5">R$ 1.225,00</div>
                                </div>
                                <div class="p-3 rounded-xl bg-slate-900 border border-slate-800">
                                    <div class="text-[11px] text-slate-400">Líquido a Pagar</div>
                                    <div class="text-base font-black text-emerald-400 font-mono mt-0.5">R$ 1.175,00</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ABA 4: COMANDA & BAR --}}
                <div x-show="activeShowcaseTab === 'comanda'" x-cloak class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                    <div class="lg:col-span-5 space-y-4">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-sky-500/10 text-sky-400 text-xs font-bold">
                            <span>🍺</span>
                            <span>Mais Lucro no Balcão</span>
                        </div>
                        <h3 class="text-2xl sm:text-3xl font-black text-white leading-tight">
                            Comanda de Bar, Cervejas, Pomadas e Cosméticos
                        </h3>
                        <p class="text-sm text-slate-300 leading-relaxed">
                            Aumente o ticket médio da sua barbearia. Lance cervejas, refrigerantes, pomadas e shampoos diretamente na comanda do cliente enquanto ele corta o cabelo, com baixa automática no estoque.
                        </p>
                        <ul class="space-y-2.5 text-xs text-slate-300 pt-2">
                            <li class="flex items-center gap-2"><span class="text-emerald-400 font-bold">✓</span> <strong>Comissão de Venda de Produto:</strong> Estimule sua equipe a vender produtos de balcão</li>
                            <li class="flex items-center gap-2"><span class="text-emerald-400 font-bold">✓</span> <strong>Alerta de Estoque Mínimo:</strong> Nunca mais deixe faltar cerveja gelada no sábado</li>
                            <li class="flex items-center gap-2"><span class="text-emerald-400 font-bold">✓</span> <strong>Fechamento Unificado:</strong> Pague corte + consumo em um único PIX ou cartão</li>
                        </ul>
                    </div>
                    <div class="lg:col-span-7">
                        <div class="rounded-2xl border border-slate-800 bg-slate-950 p-6 shadow-2xl space-y-3">
                            <div class="p-3 rounded-xl bg-slate-900 border border-slate-800 flex justify-between items-center">
                                <div class="flex items-center gap-3">
                                    <span class="text-2xl">🍺</span>
                                    <div>
                                        <div class="text-xs font-black text-white">Heineken Long Neck 330ml</div>
                                        <div class="text-[11px] text-slate-400">Estoque atual: 42 un • Custo: R$ 5,50</div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-xs font-black text-white">R$ 12,00</div>
                                    <span class="text-[10px] text-emerald-400 font-bold">+ Adicionar à Comanda</span>
                                </div>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-900 border border-slate-800 flex justify-between items-center">
                                <div class="flex items-center gap-3">
                                    <span class="text-2xl">✨</span>
                                    <div>
                                        <div class="text-xs font-black text-white">Pomada Modeladora Efeito Matte</div>
                                        <div class="text-[11px] text-slate-400">Estoque atual: 18 un • Comissão: 10%</div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-xs font-black text-white">R$ 45,00</div>
                                    <span class="text-[10px] text-emerald-400 font-bold">+ Adicionar à Comanda</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ABA 5: CARTÃO FIDELIDADE --}}
                <div x-show="activeShowcaseTab === 'fidelidade'" x-cloak class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                    <div class="lg:col-span-5 space-y-4">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-pink-500/10 text-pink-400 text-xs font-bold">
                            <span>🎁</span>
                            <span>Clientes Que Retornam Sempre</span>
                        </div>
                        <h3 class="text-2xl sm:text-3xl font-black text-white leading-tight">
                            Cartão Fidelidade 100% Digital e Alerta de Clientes Sumidos
                        </h3>
                        <p class="text-sm text-slate-300 leading-relaxed">
                            Substitua o cartão de papel que o cliente sempre perde por selos digitais automáticos. O sistema avisa quando um cliente fiel não corta o cabelo há mais de 20 dias para você enviar mensagem no WhatsApp.
                        </p>
                        <ul class="space-y-2.5 text-xs text-slate-300 pt-2">
                            <li class="flex items-center gap-2"><span class="text-emerald-400 font-bold">✓</span> <strong>Selos Automáticos:</strong> Concluiu o atendimento, o selo entra na hora na conta do cliente</li>
                            <li class="flex items-center gap-2"><span class="text-emerald-400 font-bold">✓</span> <strong>Resgate Seguro:</strong> O sistema bloqueia resgates duplicados com código de validação</li>
                            <li class="flex items-center gap-2"><span class="text-emerald-400 font-bold">✓</span> <strong>Aniversariantes do Mês:</strong> Envie parabéns e convite especial pelo WhatsApp</li>
                        </ul>
                    </div>
                    <div class="lg:col-span-7">
                        <div class="rounded-2xl border border-slate-800 bg-slate-950 p-5 shadow-2xl space-y-3">
                            <div class="p-3.5 rounded-xl bg-slate-900 border border-slate-800 flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <div class="text-xs font-black text-white">Henrique Soares • (85) 99812-4400</div>
                                    <div class="text-[11px] text-slate-400">Preferência: Degradê na zero alta, café expresso</div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="px-2.5 py-1 rounded-full bg-emerald-500/20 text-emerald-300 text-[10px] font-black">🎁 PRÊMIO LIBERADO (10/10)</span>
                                </div>
                            </div>
                            <div class="p-3.5 rounded-xl bg-slate-900 border border-slate-800 flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <div class="text-xs font-black text-white">Matheus Lima • (85) 98744-1122</div>
                                    <div class="text-[11px] text-rose-400 font-semibold">⏰ Último corte há 26 dias • Hora de chamar!</div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="px-2.5 py-1 rounded-full bg-amber-500/20 text-amber-300 text-[10px] font-bold">⭐ 7 / 10 selos</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ABA 6: VISÃO DO BARBEIRO --}}
                <div x-show="activeShowcaseTab === 'barbeiro'" x-cloak class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                    <div class="lg:col-span-5 space-y-4">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-purple-500/15 text-purple-300 text-xs font-bold">
                            <span>💈</span>
                            <span>Privacidade Financeira Absoluta</span>
                        </div>
                        <h3 class="text-2xl sm:text-3xl font-black text-white leading-tight">
                            Acesso Individual para Cada Barbeiro no Celular
                        </h3>
                        <p class="text-sm text-slate-300 leading-relaxed">
                            Crie um login para cada profissional da equipe. Quando o barbeiro entra pelo celular dele, o AuroBarber ativa a <strong>Visão Restrita</strong>: ele acompanha apenas os próprios agendamentos e comissões, sem ver o faturamento geral nem a agenda dos colegas.
                        </p>
                        <ul class="space-y-2.5 text-xs text-slate-300 pt-2">
                            <li class="flex items-center gap-2"><span class="text-emerald-400 font-bold">✓</span> <strong>Sigilo do Caixa Geral:</strong> O barbeiro não vê o lucro total nem relatórios estratégicos</li>
                            <li class="flex items-center gap-2"><span class="text-emerald-400 font-bold">✓</span> <strong>Segurança na Liquidação:</strong> Apenas o dono ou gerente pode dar baixa como pago</li>
                            <li class="flex items-center gap-2"><span class="text-emerald-400 font-bold">✓</span> <strong>Avaliação 5 Estrelas Pós-Corte:</strong> O cliente avalia o atendimento pelo link de satisfação</li>
                        </ul>
                    </div>
                    <div class="lg:col-span-7">
                        <div class="max-w-md mx-auto rounded-3xl border border-slate-700 bg-slate-950 p-6 shadow-2xl space-y-4">
                            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                                <div>
                                    <div class="text-xs text-purple-400 font-bold uppercase tracking-wider">Visão Restrita do Barbeiro</div>
                                    <div class="text-sm font-black text-white">Marcos Vinícius • Cadeira 01</div>
                                </div>
                                <span class="px-2.5 py-1 rounded-full bg-purple-500/20 text-purple-300 text-[10px] font-bold">🔒 Modo Seguro</span>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="p-3 rounded-xl bg-slate-900 border border-slate-800 text-center">
                                    <div class="text-[11px] text-slate-400">Cortes Hoje</div>
                                    <div class="text-lg font-black text-white font-mono mt-0.5">8 cortes</div>
                                </div>
                                <div class="p-3 rounded-xl bg-slate-900 border border-slate-800 text-center">
                                    <div class="text-[11px] text-slate-400">Minha Comissão Hoje</div>
                                    <div class="text-lg font-black text-emerald-400 font-mono mt-0.5">R$ 210,00</div>
                                </div>
                            </div>
                            <div class="text-[11px] text-slate-500 text-center">
                                Relatórios gerenciais e faturamento global da barbearia bloqueados para este perfil.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</section>

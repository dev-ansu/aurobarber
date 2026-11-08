<x-filament-panels::page>
    <div class="space-y-6">

        <!-- CABEÇALHO HERO COM BUSCA E APRESENTAÇÃO -->
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-primary-600 via-primary-700 to-primary-900 p-6 sm:p-8 text-white shadow-xl">
            <div class="relative z-10 max-w-3xl space-y-4">
                <div class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-bold backdrop-blur-md">
                    <x-heroicon-m-book-open class="h-4 w-4" />
                    <span>Documentação Oficial e Tutoriais de Operação</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                    Central de Conhecimento Aurosoft
                </h1>
                <p class="text-sm sm:text-base text-primary-100 leading-relaxed">
                    Aprenda a operar todos os módulos da sua escola: desde a captação do aluno no CRM, contrato, chamada diária com autoatendimento até o controle financeiro, metas e comissões.
                </p>

                <!-- BUSCA DINÂMICA -->
                <div class="pt-2 max-w-xl">
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-primary-300">
                            <x-heroicon-m-magnifying-glass class="h-5 w-5" />
                        </div>
                        <input
                            type="text"
                            wire:model.live.debounce.250ms="search"
                            placeholder="Buscar tutorial por palavra-chave (ex: pix, chamada, comissão, lms, cancelamento)..."
                            class="block w-full rounded-2xl border-0 bg-white/15 py-3 pl-11 pr-4 text-sm text-white placeholder:text-primary-200 backdrop-blur-md focus:bg-white focus:text-gray-900 focus:placeholder:text-gray-400 focus:ring-2 focus:ring-white transition"
                        />
                    </div>
                </div>
            </div>

            <!-- Fundo decorativo sutil -->
            <div class="absolute -right-12 -top-12 h-64 w-64 rounded-full bg-white/5 blur-2xl"></div>
            <div class="absolute right-24 -bottom-16 h-48 w-48 rounded-full bg-amber-400/10 blur-xl"></div>
        </div>

        <!-- BARRA DE FILTROS POR CATEGORIA -->
        <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none">
            @php
                $categories = [
                    'all' => ['label' => 'Todos os Módulos', 'icon' => 'heroicon-m-squares-2x2'],
                    'comercial' => ['label' => 'Comercial & CRM', 'icon' => 'heroicon-m-user-group'],
                    'contratos' => ['label' => 'Matrículas & Contratos', 'icon' => 'heroicon-m-document-text'],
                    'pedagogico' => ['label' => 'Pedagógico & Chamadas', 'icon' => 'heroicon-m-academic-cap'],
                    'kiosk' => ['label' => 'Totem & Pesquisa NPS', 'icon' => 'heroicon-m-device-phone-mobile'],
                    'financeiro' => ['label' => 'Financeiro & Pix', 'icon' => 'heroicon-m-banknotes'],
                    'gestao' => ['label' => 'Metas & Produtividade', 'icon' => 'heroicon-m-trophy'],
                    'lms' => ['label' => 'LMS Ouro Moderno', 'icon' => 'heroicon-m-computer-desktop'],
                    'seguranca' => ['label' => 'Segurança & Backups', 'icon' => 'heroicon-m-shield-check'],
                    'faq' => ['label' => 'Dúvidas Frequentes (FAQ)', 'icon' => 'heroicon-m-question-mark-circle'],
                ];
            @endphp

            @foreach ($categories as $catKey => $catData)
                <button
                    type="button"
                    wire:click="$set('activeCategory', '{{ $catKey }}')"
                    class="inline-flex shrink-0 items-center gap-1.5 rounded-xl px-3.5 py-2 text-xs font-bold transition {{ $activeCategory === $catKey ? 'bg-primary-600 text-white shadow-sm' : 'bg-white text-gray-700 hover:bg-gray-100 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 border border-gray-200 dark:border-gray-800' }}"
                >
                    <x-dynamic-component :component="$catData['icon']" class="h-4 w-4" />
                    <span>{{ $catData['label'] }}</span>
                </button>
            @endforeach
        </div>

        <!-- GRADE DE CONTEÚDO E TUTORIAIS -->
        <div class="space-y-8">

            {{-- 1. FLUXO GERAL / CICLO DO ALUNO --}}
            @if($this->isSectionVisible('ciclo'))
                <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center gap-3 pb-4 border-b border-gray-100 dark:border-gray-800">
                        <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-primary-100 text-primary-600 dark:bg-primary-950 dark:text-primary-400">
                            <x-heroicon-o-arrow-path-rounded-square class="h-6 w-6" />
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-gray-950 dark:text-white">O Ciclo de Vida do Aluno no Aurosoft</h2>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Entenda a jornada do aluno desde o primeiro contato até a formação.</p>
                        </div>
                    </div>

                    <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="rounded-2xl border border-blue-100 bg-blue-50/40 p-4 dark:border-blue-900/40 dark:bg-blue-950/20">
                            <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-blue-600 text-xs font-black text-white">1</span>
                            <h3 class="mt-2 text-xs font-bold uppercase tracking-wider text-blue-900 dark:text-blue-300">Captação & CRM</h3>
                            <p class="mt-1 text-xs text-blue-800/80 dark:text-blue-300/80 leading-relaxed">
                                O interessado chega via anúncio, indicação ou balcão. É registrado no CRM, agendada visita e acompanhado no Funil Kanban de Vendas.
                            </p>
                        </div>

                        <div class="rounded-2xl border border-emerald-100 bg-emerald-50/40 p-4 dark:border-emerald-900/40 dark:bg-emerald-950/20">
                            <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-emerald-600 text-xs font-black text-white">2</span>
                            <h3 class="mt-2 text-xs font-bold uppercase tracking-wider text-emerald-900 dark:text-emerald-300">Matrícula & Contrato</h3>
                            <p class="mt-1 text-xs text-emerald-800/80 dark:text-emerald-300/80 leading-relaxed">
                                Gerado o contrato, vinculada a formação, definidos os horários de aula (sala e dia da semana) e emitido o carnê com chave Pix.
                            </p>
                        </div>

                        <div class="rounded-2xl border border-purple-100 bg-purple-50/40 p-4 dark:border-purple-900/40 dark:bg-purple-950/20">
                            <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-purple-600 text-xs font-black text-white">3</span>
                            <h3 class="mt-2 text-xs font-bold uppercase tracking-wider text-purple-900 dark:text-purple-300">Aulas & Frequência</h3>
                            <p class="mt-1 text-xs text-purple-800/80 dark:text-purple-300/80 leading-relaxed">
                                O aluno bate ponto no Totem (Kiosk), assiste às aulas no LMS Ouro Moderno e a frequência é auditada diariamente na Agenda do educador.
                            </p>
                        </div>

                        <div class="rounded-2xl border border-amber-100 bg-amber-50/40 p-4 dark:border-amber-900/40 dark:bg-amber-950/20">
                            <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-amber-600 text-xs font-black text-white">4</span>
                            <h3 class="mt-2 text-xs font-bold uppercase tracking-wider text-amber-900 dark:text-amber-300">Retenção & Conclusão</h3>
                            <p class="mt-1 text-xs text-amber-800/80 dark:text-amber-300/80 leading-relaxed">
                                Alunos faltosos entram em radar pedagógico, são entrevistados para evitar evasão, concluem os cursos e recebem o certificado.
                            </p>
                        </div>
                    </div>
                </section>
            @endif

            {{-- 2. MÓDULO COMERCIAL & CRM DE VENDAS --}}
            @if($this->isSectionVisible('comercial'))
                <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-4 border-b border-gray-100 dark:border-gray-800">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-blue-100 text-blue-600 dark:bg-blue-950 dark:text-blue-400">
                                <x-heroicon-o-user-group class="h-6 w-6" />
                            </div>
                            <div>
                                <h2 class="text-base font-bold text-gray-950 dark:text-white">Módulo Comercial & CRM de Vendas</h2>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Como captar leads, agendar visitas, usar o Kanban e calcular comissões.</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <a
                                href="{{ route('filament.admin.pages.commercial-leads-kanban') }}"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-blue-50 px-3 py-1.5 text-xs font-bold text-blue-700 hover:bg-blue-100 dark:bg-blue-950 dark:text-blue-300 transition"
                            >
                                <x-heroicon-m-view-columns class="h-3.5 w-3.5" />
                                <span>Abrir Funil Kanban</span>
                            </a>
                            <a
                                href="{{ route('filament.admin.pages.commission-report') }}"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-gray-100 px-3 py-1.5 text-xs font-bold text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 transition"
                            >
                                <span>Relatório de Comissões</span>
                            </a>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm text-gray-700 dark:text-gray-300">
                        <div class="space-y-3">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-gray-900 dark:text-white">Passo a Passo de Vendas:</h3>
                            <ol class="list-decimal space-y-2 pl-4 text-xs leading-relaxed text-gray-600 dark:text-gray-400">
                                <li><strong>Cadastrar Interessado</strong>: Em <em>Comercial → Interessados</em>, informe nome, WhatsApp e o curso de interesse.</li>
                                <li><strong>Agendar Visita</strong>: Defina o dia e horário para o responsável conhecer a escola ou fazer uma aula experimental.</li>
                                <li><strong>Mover no Funil Kanban</strong>: Arraste o cartão pelo funil (<em>Novo Lead ➔ Contatado ➔ Visita Agendada ➔ Visita Realizada ➔ Em Negociação ➔ Matrícula Fechada</em>).</li>
                                <li><strong>Vincular Matrícula</strong>: Ao fechar o contrato, selecione o interessado para alimentar automaticamente as taxas de conversão da escola.</li>
                            </ol>
                        </div>

                        <div class="space-y-3">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-gray-900 dark:text-white">Regras de Comissões e Metas:</h3>
                            <ul class="list-disc space-y-2 pl-4 text-xs leading-relaxed text-gray-600 dark:text-gray-400">
                                <li><strong>Configuradas pela Escola</strong>: Em <em>Comercial → Regras de Comissão</em>, o gestor define a porcentagem de comissão por curso e os aceleradores por volume de matrículas.</li>
                                <li><strong>Auditoria de Pagamento</strong>: O relatório de comissões mostra o vendedor responsável por cada contrato, o valor faturado e o valor exato a ser pago.</li>
                                <li><strong>Integração com Metas</strong>: Cada contrato fechado soma instantaneamente nas metas de faturamento e quantidade do mês.</li>
                            </ul>
                        </div>
                    </div>
                </section>
            @endif

            {{-- 3. MÓDULO MATRÍCULAS E CONTRATOS --}}
            @if($this->isSectionVisible('contratos'))
                <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-4 border-b border-gray-100 dark:border-gray-800">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400">
                                <x-heroicon-o-document-text class="h-6 w-6" />
                            </div>
                            <div>
                                <h2 class="text-base font-bold text-gray-950 dark:text-white">Matrículas & Gestão de Contratos</h2>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Como formalizar o aluno, alocar em turmas e gerar o plano financeiro.</p>
                            </div>
                        </div>

                        <a
                            href="{{ route('filament.admin.resources.contracts.index') }}"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950 dark:text-emerald-300 transition"
                        >
                            <span>Ir para Contratos</span>
                        </a>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs text-gray-600 dark:text-gray-400">
                        <div class="rounded-2xl bg-gray-50 p-4 dark:bg-gray-800/50 space-y-2">
                            <h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-1">
                                <x-heroicon-m-user class="h-4 w-4 text-primary-600" />
                                1. Aluno & Responsável
                            </h3>
                            <p class="leading-relaxed">
                                Se o aluno for menor de idade, vincule um <strong>Responsável Financeiro</strong> em <em>Escola → Pessoas</em>. O contrato sairá com os dados do responsável para fins legais e emissão de cobranças.
                            </p>
                        </div>

                        <div class="rounded-2xl bg-gray-50 p-4 dark:bg-gray-800/50 space-y-2">
                            <h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-1">
                                <x-heroicon-m-calendar-days class="h-4 w-4 text-primary-600" />
                                2. Grade de Horários & Sala
                            </h3>
                            <p class="leading-relaxed">
                                Ao criar os itens do contrato, defina os dias da semana, horário de início/término e a sala. É essa alocação que gera a lista de presença da <strong>Chamada Diária</strong>.
                            </p>
                        </div>

                        <div class="rounded-2xl bg-gray-50 p-4 dark:bg-gray-800/50 space-y-2">
                            <h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-1">
                                <x-heroicon-m-banknotes class="h-4 w-4 text-primary-600" />
                                3. Parcelas e Carnê
                            </h3>
                            <p class="leading-relaxed">
                                Escolha a quantidade de parcelas, dia de vencimento e descontos pontuais. As parcelas são criadas automaticamente e podem ser impressas com QR Code Pix.
                            </p>
                        </div>
                    </div>
                </section>
            @endif

            {{-- 4. MÓDULO PEDAGÓGICO, CHAMADA & FREQUÊNCIA --}}
            @if($this->isSectionVisible('pedagogico'))
                <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-4 border-b border-gray-100 dark:border-gray-800">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-purple-100 text-purple-600 dark:bg-purple-950 dark:text-purple-400">
                                <x-heroicon-o-academic-cap class="h-6 w-6" />
                            </div>
                            <div>
                                <h2 class="text-base font-bold text-gray-950 dark:text-white">Módulo Pedagógico & Chamada Diária</h2>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Chamadas self-healing, acompanhamento de faltosos e prevenção da evasão.</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <a
                                href="{{ route('filament.admin.pages.agenda') }}"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-purple-50 px-3 py-1.5 text-xs font-bold text-purple-700 hover:bg-purple-100 dark:bg-purple-950 dark:text-purple-300 transition"
                            >
                                <span>Abrir Chamada Diária</span>
                            </a>
                            <a
                                href="{{ route('filament.admin.pages.pedagogical-follow-up-report') }}"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-gray-100 px-3 py-1.5 text-xs font-bold text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 transition"
                            >
                                <span>Acompanhamento Pedagógico</span>
                            </a>
                        </div>
                    </div>

                    <div class="space-y-4 text-xs text-gray-600 dark:text-gray-400">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <h3 class="font-bold text-gray-900 dark:text-white text-sm">Automação de Chamada Failsafe (Auto-recuperável):</h3>
                                <p class="leading-relaxed">
                                     O Aurosoft gera a lista de presença todos os dias à meia-noite. Porém, caso o servidor esteja desligado ou reinicie, o sistema conta com <strong>auto-recuperação imediata</strong>: ao abrir a Agenda ou quando o primeiro aluno bater ponto no Totem, a chamada do dia é criada instantaneamente.
                                </p>
                            </div>

                            <div class="space-y-2">
                                <h3 class="font-bold text-gray-900 dark:text-white text-sm">Alunos em Risco de Evasão (3+ Faltas):</h3>
                                <p class="leading-relaxed">
                                    Em <em>Relatórios → Ausências dos Alunos</em> e no <em>Acompanhamento Pedagógico</em>, o sistema destaca alunos com faltas consecutivas. A recepção/gestão deve agendar uma <strong>Entrevista de Retenção</strong> na Agenda Institucional para entender a causa e manter o aluno engajado.
                                </p>
                            </div>
                        </div>

                        <div class="rounded-2xl bg-amber-50/70 p-4 border border-amber-200/60 dark:bg-amber-950/20 dark:border-amber-900/40 text-amber-900 dark:text-amber-200">
                            <h4 class="font-bold text-xs uppercase tracking-wider mb-1">💡 Dica Pedagógica:</h4>
                            <p class="leading-relaxed">
                                Todas as presenças registradas no Totem ganham a etiqueta <span class="font-bold text-emerald-700 dark:text-emerald-300">Totem (Auditado)</span>, garantindo à coordenação que a frequência lançada reflete a presença física real do aluno na sala de aula.
                            </p>
                        </div>
                    </div>
                </section>
            @endif

            {{-- 5. MÓDULO TOTEM (KIOSK) & PESQUISA NPS --}}
            @if($this->isSectionVisible('kiosk'))
                <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-4 border-b border-gray-100 dark:border-gray-800">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400">
                                <x-heroicon-o-device-phone-mobile class="h-6 w-6" />
                            </div>
                            <div>
                                <h2 class="text-base font-bold text-gray-950 dark:text-white">Totem de Autoatendimento & Pesquisa NPS</h2>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Ponto eletrônico do aluno, avisos na tela e medição de satisfação.</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <a
                                href="{{ url('/presenca') . (\Filament\Facades\Filament::getTenant() ? '?escola=' . \Filament\Facades\Filament::getTenant()->id : '') }}"
                                target="_blank"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-emerald-500 shadow-sm transition"
                            >
                                <x-heroicon-m-arrow-top-right-on-square class="h-3.5 w-3.5" />
                                <span>Abrir Totem do Aluno</span>
                            </a>
                            <a
                                href="{{ route('filament.admin.resources.student-announcements.index') }}"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-gray-100 px-3 py-1.5 text-xs font-bold text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 transition"
                            >
                                <span>Gerenciar Avisos</span>
                            </a>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs text-gray-600 dark:text-gray-400">
                        <div class="space-y-2">
                            <h3 class="font-bold text-gray-900 dark:text-white">1. Como o Aluno Registra Presença</h3>
                            <p class="leading-relaxed">
                                No tablet ou computador da recepção, o aluno posiciona o crachá no leitor de código de barras ou digita o <strong>número do seu contrato</strong>. Se não souber o contrato, basta clicar em <em>"Não sei meu número de contrato"</em> e informar seu nome completo e data de nascimento.
                            </p>
                        </div>

                        <div class="space-y-2">
                            <h3 class="font-bold text-gray-900 dark:text-white">2. Avisos Institucionais na Entrada</h3>
                            <p class="leading-relaxed">
                                Em <em>Escola → Avisos</em>, a direção pode criar comunicados (feriados, eventos, avisos de pagamento). O aviso aparece na tela cheia do Totem para o aluno ler antes de marcar presença.
                            </p>
                        </div>

                        <div class="space-y-2">
                            <h3 class="font-bold text-gray-900 dark:text-white">3. Pesquisa de Satisfação NPS</h3>
                            <p class="leading-relaxed">
                                Periodicamente, o Totem exibe uma pergunta rápida de satisfação (nota de 0 a 10 com carinhas/estrelas). As respostas alimentam o <strong>Relatório NPS</strong> da escola, permitindo corrigir insatisfações de imediato.
                            </p>
                        </div>
                    </div>
                </section>
            @endif

            {{-- 6. MÓDULO FINANCEIRO, CARNÊS & PIX --}}
            @if($this->isSectionVisible('financeiro'))
                <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-4 border-b border-gray-100 dark:border-gray-800">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400">
                                <x-heroicon-o-banknotes class="h-6 w-6" />
                            </div>
                            <div>
                                <h2 class="text-base font-bold text-gray-950 dark:text-white">Módulo Financeiro, Carnês & Pix</h2>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Configuração de chave Pix, baixa de parcelas, recibos e relatórios de DRE.</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <a
                                href="{{ route('filament.admin.pages.configuracao-pix') }}"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950 dark:text-emerald-300 transition"
                            >
                                <x-heroicon-m-qr-code class="h-3.5 w-3.5" />
                                <span>Configurar Pix</span>
                            </a>
                            <a
                                href="{{ route('filament.admin.pages.dre-gerencial') }}"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-gray-100 px-3 py-1.5 text-xs font-bold text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 transition"
                            >
                                <span>Relatório DRE</span>
                            </a>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-xs text-gray-600 dark:text-gray-400">
                        <div class="space-y-3">
                            <h3 class="font-bold text-gray-900 dark:text-white text-sm">Configuração do Pix na Escola:</h3>
                            <ul class="list-disc space-y-2 pl-4 leading-relaxed">
                                <li>Acesse <em>Configurações Financeiras → Configurações do Pix</em>.</li>
                                <li>Defina o tipo de chave (CNPJ, Celular, E-mail ou Aleatória), o nome do favorecido e a cidade da escola.</li>
                                <li>O sistema gera instantaneamente a simulação visual da lâmina de carnê com o QR Code e o código Pix Copia e Cola.</li>
                                <li>O aluno ou responsável pode escanear o QR Code no app de qualquer banco e pagar em segundos.</li>
                            </ul>
                        </div>

                        <div class="space-y-3">
                            <h3 class="font-bold text-gray-900 dark:text-white text-sm">Recebimento e Controle de Caixa:</h3>
                            <ul class="list-disc space-y-2 pl-4 leading-relaxed">
                                <li><strong>Dar baixa</strong>: Em <em>Financeiro → Parcelas</em> ou dentro do Contrato, clique em <strong>Receber</strong>, informe o meio de pagamento e a data de liquidação.</li>
                                <li><strong>Recibos</strong>: Após a baixa, é possível imprimir o comprovante de pagamento para entregar ao cliente.</li>
                                <li><strong>DRE e Inadimplência</strong>: O demonstrativo financeiro calcula receitas brutas, descontos, recebimentos efetivos e parcelas em atraso.</li>
                            </ul>
                        </div>
                    </div>
                </section>
            @endif

            {{-- 7. MÓDULO METAS, AUDITORIA & PRODUTIVIDADE --}}
            @if($this->isSectionVisible('gestao'))
                <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-4 border-b border-gray-100 dark:border-gray-800">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-amber-100 text-amber-600 dark:bg-amber-950 dark:text-amber-400">
                                <x-heroicon-o-trophy class="h-6 w-6" />
                            </div>
                            <div>
                                <h2 class="text-base font-bold text-gray-950 dark:text-white">Metas da Escola, Auditoria & Agenda de Compromissos</h2>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Gestão 360°, anti-fraude de chamada, reuniões institucionais e To-Do List compartilhado.</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <a
                                href="{{ route('filament.admin.resources.school-goals.index') }}"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-800 hover:bg-amber-100 dark:bg-amber-950 dark:text-amber-300 transition"
                            >
                                <x-heroicon-m-cog-6-tooth class="h-3.5 w-3.5" />
                                <span>Definir Metas</span>
                            </a>
                            <a
                                href="{{ route('filament.admin.pages.agenda-compromissos') }}"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-primary-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-primary-500 shadow-sm transition"
                            >
                                <x-heroicon-m-calendar class="h-3.5 w-3.5" />
                                <span>Agenda & Tarefas</span>
                            </a>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs text-gray-600 dark:text-gray-400">
                        <div class="rounded-2xl bg-gray-50 p-4 dark:bg-gray-800/50 space-y-2">
                            <h3 class="font-bold text-gray-900 dark:text-white">🎯 1. Metas Pedagógicas e Comerciais</h3>
                            <p class="leading-relaxed">
                                Definidas pelo gestor em <em>Comercial → Metas da Escola</em>. Acompanham em tempo real o faturamento, matrículas, taxa de assiduidade (%) e o teto máximo de cancelamentos tolerado.
                            </p>
                        </div>

                        <div class="rounded-2xl bg-gray-50 p-4 dark:bg-gray-800/50 space-y-2">
                            <h3 class="font-bold text-gray-900 dark:text-white">🛡️ 2. Auditoria Anti-Fraude</h3>
                            <p class="leading-relaxed">
                                O widget no Dashboard compara presenças confirmadas pelo Totem (crachá/CPF) contra presenças lançadas manualmente pelo educador, emitindo alerta caso haja discrepâncias suspeitas.
                            </p>
                        </div>

                        <div class="rounded-2xl bg-gray-50 p-4 dark:bg-gray-800/50 space-y-2">
                            <h3 class="font-bold text-gray-900 dark:text-white">📅 3. Agenda & Recados Compartilhados</h3>
                            <p class="leading-relaxed">
                                Em <em>Administrativo → Agenda de Compromissos</em>, registre entrevistas de cancelamento, reuniões com pais e recados com a opção <strong>Compartilhar com toda a equipe</strong>.
                            </p>
                        </div>
                    </div>
                </section>
            @endif

            {{-- 8. LMS PRÓPRIO AUROSOFT & INTEGRAÇÃO OURO MODERNO --}}
            @if($this->isSectionVisible('lms'))
                <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 space-y-6">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-4 border-b border-gray-100 dark:border-gray-800">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-indigo-100 text-indigo-600 dark:bg-indigo-950 dark:text-indigo-400">
                                <x-heroicon-o-academic-cap class="h-6 w-6" />
                            </div>
                            <div>
                                <h2 class="text-base font-bold text-gray-950 dark:text-white">Estúdio LMS, Método Jesuítico & Monitor de Laboratório</h2>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Crie cursos interativos com simulador prático de software, controle o ritmo dos alunos e acompanhe a sala em tempo real.</p>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <a
                                href="{{ route('filament.studio.pages.lms-studio') }}"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-primary-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-primary-500 shadow-sm transition"
                            >
                                <x-heroicon-m-pencil-square class="h-3.5 w-3.5" />
                                <span>Estúdio LMS</span>
                            </a>
                            <a
                                href="{{ route('filament.admin.pages.classroom-live-monitor') }}"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-amber-500 px-3 py-1.5 text-xs font-bold text-slate-950 hover:bg-amber-400 shadow-sm transition"
                            >
                                <x-heroicon-m-computer-desktop class="h-3.5 w-3.5" />
                                <span>Monitor de Sala</span>
                            </a>
                            <a
                                href="{{ route('filament.admin.pages.ouro-moderno-student-logs-report') }}"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-sky-50 px-3 py-1.5 text-xs font-bold text-sky-700 hover:bg-sky-100 dark:bg-sky-950 dark:text-sky-300 transition"
                            >
                                <span>Logs Ouro Moderno</span>
                            </a>
                        </div>
                    </div>

                    {{-- CARDS EXPLICATIVOS DOS 4 PILARES DO LMS --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
                        <div class="rounded-2xl border border-indigo-100 bg-indigo-50/40 p-4 dark:border-indigo-900/30 dark:bg-indigo-950/20 space-y-2">
                            <div class="flex items-center gap-2">
                                <span class="text-lg">🏛️</span>
                                <h3 class="font-bold text-indigo-950 dark:text-indigo-200">1. Método Jesuítico</h3>
                            </div>
                            <p class="text-indigo-900/80 dark:text-indigo-300/80 leading-relaxed">
                                Estruture as aulas nos 5 blocos da <em>Ratio Studiorum</em>: <strong>Praelectio</strong> (teoria contextualizada), <strong>Experientia</strong> (prática guiada), <strong>Repetitio</strong> (fixação), <strong>Concertatio</strong> (desafio autônomo) e <strong>Examen</strong> (avaliação de maestria).
                            </p>
                        </div>

                        <div class="rounded-2xl border border-purple-100 bg-purple-50/40 p-4 dark:border-purple-900/30 dark:bg-purple-950/20 space-y-2">
                            <div class="flex items-center gap-2">
                                <span class="text-lg">🎯</span>
                                <h3 class="font-bold text-purple-950 dark:text-purple-200">2. Simulador Prático</h3>
                            </div>
                            <p class="text-purple-900/80 dark:text-purple-300/80 leading-relaxed">
                                O aluno executa tarefas reais em prints de software: <strong>clique</strong>, <strong>duplo clique</strong>, <strong>botão direito</strong>, <strong>seleção de texto</strong> (arrastar com validação de direção) e <strong>digitação de comandos</strong> com confronto de resposta e scaffolding pedagógico após 2 erros.
                            </p>
                        </div>

                        <div class="rounded-2xl border border-amber-100 bg-amber-50/40 p-4 dark:border-amber-900/30 dark:bg-amber-950/20 space-y-2">
                            <div class="flex items-center gap-2">
                                <span class="text-lg">⏱️</span>
                                <h3 class="font-bold text-amber-950 dark:text-amber-200">3. Pacing & Revisão</h3>
                            </div>
                            <p class="text-amber-900/80 dark:text-amber-300/80 leading-relaxed">
                                Evita que o aluno avance sem ler. Cada passo possui <strong>tempo de permanência mínima</strong> (15s a 45s) com cronômetro circular. Ao concluir a aula, o aluno pode <strong>revisar o conteúdo</strong> livremente sem travas de tempo.
                            </p>
                        </div>

                        <div class="rounded-2xl border border-emerald-100 bg-emerald-50/40 p-4 dark:border-emerald-900/30 dark:bg-emerald-950/20 space-y-2">
                            <div class="flex items-center gap-2">
                                <span class="text-lg">🔓</span>
                                <h3 class="font-bold text-emerald-950 dark:text-emerald-200">4. Liberação pelo Educador</h3>
                            </div>
                            <p class="text-emerald-900/80 dark:text-emerald-300/80 leading-relaxed">
                                No <strong>Monitor de Laboratório</strong> ou no painel de <strong>Chamadas</strong>, o educador pode com 1 clique <em>"Liberar Próxima Aula"</em> ou suspender o teto diário de 1 aula/dia para alunos que precisam adiantar o conteúdo.
                            </p>
                        </div>
                    </div>

                    {{-- GUIA PASSO A PASSO: COMO CRIAR UMA SIMULAÇÃO PRÁTICA --}}
                    <div class="rounded-2xl bg-gray-50 p-5 dark:bg-gray-800/60 space-y-3 text-xs text-gray-700 dark:text-gray-300">
                        <h4 class="font-bold text-gray-900 dark:text-white flex items-center gap-2 text-sm">
                            <span>🛠️ Guia Rápido: Como Criar Conteúdo no Estúdio LMS</span>
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="space-y-1.5">
                                <strong class="text-gray-900 dark:text-white block font-bold">1. Texto Formatado & Dicas:</strong>
                                <p class="leading-relaxed">
                                    No passo de teoria, use a barra de ferramentas para aplicar <strong>negrito</strong>, <em>itálico</em>, <u>sublinhado</u>, subtítulos H2, listas e caixas de dicas destacadas. O aluno verá a explicação formatada de forma clara e visual.
                                </p>
                            </div>

                            <div class="space-y-1.5">
                                <strong class="text-gray-900 dark:text-white block font-bold">2. Simulação com Seleção de Texto:</strong>
                                <p class="leading-relaxed">
                                    Ao criar um passo de simulação prática, suba o print do software. Selecione a ação <em>"Selecionar Texto (Arrastar)"</em>, marque o ponto inicial e final na imagem e defina a direção obrigatória (ex: da esquerda para a direita). O aluno terá que arrastar o mouse exatamente como no Office/Word.
                                </p>
                            </div>

                            <div class="space-y-1.5">
                                <strong class="text-gray-900 dark:text-white block font-bold">3. Simulação com Digitação de Texto:</strong>
                                <p class="leading-relaxed">
                                    Selecione a ação <em>"Digitar Texto / Comando"</em>, defina a resposta exata esperada (ex: <code>=SOMA(A1:B5)</code>) e se maiúsculas/minúsculas devem ser diferenciadas. No player, um campo de texto real aparecerá sobre o print para o aluno digitar e pressionar Enter.
                                </p>
                            </div>
                        </div>
                    </div>
                </section>
            @endif

            {{-- 9. SEGURANÇA, PERMISSÕES & BACKUPS --}}
            @if($this->isSectionVisible('seguranca'))
                <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-4 border-b border-gray-100 dark:border-gray-800">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-rose-100 text-rose-600 dark:bg-rose-950 dark:text-rose-400">
                                <x-heroicon-o-shield-check class="h-6 w-6" />
                            </div>
                            <div>
                                <h2 class="text-base font-bold text-gray-950 dark:text-white">Segurança, Permissões & Backups</h2>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Blindagem de acesso por papel de usuário e salvaguarda dos dados da escola.</p>
                            </div>
                        </div>

                        <a
                            href="{{ route('filament.admin.pages.backup-management') }}"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-gray-100 px-3 py-1.5 text-xs font-bold text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 transition"
                        >
                            <span>Gerenciar Backups</span>
                        </a>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-xs text-gray-600 dark:text-gray-400 leading-relaxed">
                        <div class="space-y-2">
                            <h3 class="font-bold text-gray-900 dark:text-white text-sm">Controle de Perfis (Spatie Shield):</h3>
                            <p>
                                O Aurosoft adota a política <strong>Zero-Trust</strong>: cada funcionário (educador, recepcionista, consultor de vendas, gerente financeiro) só enxerga e edita as telas liberadas para o seu papel. Professores não alteram notas fiscais nem metas, e consultores não excluem contratos.
                            </p>
                        </div>

                        <div class="space-y-2">
                            <h3 class="font-bold text-gray-900 dark:text-white text-sm">Rotinas de Backup e Restauração:</h3>
                            <p>
                                Em <em>Ajuda e suporte → Backup e restauração</em>, o gestor pode baixar um arquivo compactado (ZIP) com o banco de dados completo e todos os anexos. A restauração possui trava de segurança: exige digitar a palavra <code>RESTAURAR</code> em maiúsculas para prevenir qualquer acidente.
                            </p>
                        </div>
                    </div>
                </section>
            @endif

            {{-- 10. FAQ - PERGUNTAS FREQUENTES --}}
            @if($this->isSectionVisible('faq'))
                <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 space-y-4">
                    <div class="flex items-center gap-3 pb-4 border-b border-gray-100 dark:border-gray-800">
                        <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-amber-100 text-amber-600 dark:bg-amber-950 dark:text-amber-400">
                            <x-heroicon-o-question-mark-circle class="h-6 w-6" />
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-gray-950 dark:text-white">Perguntas Frequentes (FAQ) & Resolução Rápida</h2>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Respostas diretas para as situações mais comuns do cotidiano escolar.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                        <div class="rounded-2xl border border-gray-100 bg-gray-50/60 p-4 dark:border-gray-800 dark:bg-gray-800/40 space-y-1.5">
                            <h4 class="font-bold text-gray-900 dark:text-white">❓ O que fazer se o aluno esquecer o crachá no Totem?</h4>
                            <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                                Ele pode digitar o <strong>número do seu contrato</strong> ou clicar em <strong>"Não sei meu número de contrato"</strong> para consultar informando seu <strong>nome completo e data de nascimento</strong>. Caso prefira, o educador também pode lançar a presença na Agenda diária.
                            </p>
                        </div>

                        <div class="rounded-2xl border border-gray-100 bg-gray-50/60 p-4 dark:border-gray-800 dark:bg-gray-800/40 space-y-1.5">
                            <h4 class="font-bold text-gray-900 dark:text-white">❓ Como mudar o ano das metas para planejar o futuro?</h4>
                            <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                                O seletor de ano nas Metas da Escola é dinâmico e alcança até 10 anos à frente. O gestor pode planejar metas para o próximo ano a qualquer momento.
                            </p>
                        </div>

                        <div class="rounded-2xl border border-gray-100 bg-gray-50/60 p-4 dark:border-gray-800 dark:bg-gray-800/40 space-y-1.5">
                            <h4 class="font-bold text-gray-900 dark:text-white">❓ Quem pode ver os compromissos marcados na Agenda?</h4>
                            <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                                Por padrão, o criador e o responsável pelo compromisso. Se marcar a caixa <em>"📢 Compartilhar com toda a equipe"</em>, todos os colaboradores visualizam o aviso ou reunião.
                            </p>
                        </div>

                        <div class="rounded-2xl border border-gray-100 bg-gray-50/60 p-4 dark:border-gray-800 dark:bg-gray-800/40 space-y-1.5">
                            <h4 class="font-bold text-gray-900 dark:text-white">❓ Como trocar minha senha de acesso?</h4>
                            <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                                Clique no seu nome no topo direito da tela, acerte em <strong>Perfil</strong>, digite sua senha atual e a nova senha desejada.
                            </p>
                        </div>
                    </div>
                </section>
            @endif

            {{-- ESTADO VAZIO QUANDO NENHUM TÓPICO BATE COM A BUSCA --}}
            @if($this->visibleSectionsCount === 0)
                <div class="rounded-3xl border border-dashed border-gray-300 dark:border-gray-700 p-12 text-center bg-white dark:bg-gray-900 space-y-4">
                    <div class="inline-flex h-16 w-16 items-center justify-center rounded-full bg-primary-50 dark:bg-primary-950/50 text-primary-600 dark:text-primary-400">
                        <x-heroicon-o-magnifying-glass class="h-8 w-8" />
                    </div>
                    <div class="space-y-1">
                        <h3 class="text-base font-bold text-gray-950 dark:text-white">Nenhum tutorial encontrado</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 max-w-sm mx-auto">
                            Não encontramos nenhum tópico correspondente a "{{ $search }}". Tente buscar por outros termos como <em>pix, contrato, totem, nota, chamada</em>.
                        </p>
                    </div>
                    <div class="pt-2">
                        <button
                            type="button"
                            wire:click="$set('search', ''); $set('activeCategory', 'all')"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-primary-600 px-4 py-2 text-xs font-bold text-white hover:bg-primary-500 transition shadow-sm"
                        >
                            <x-heroicon-m-arrow-path class="h-4 w-4" />
                            <span>Limpar Filtros e Ver Todos</span>
                        </button>
                    </div>
                </div>
            @endif

        </div>

    </div>
</x-filament-panels::page>

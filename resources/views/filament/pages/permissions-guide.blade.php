<x-filament-panels::page>
    <div class="space-y-6">

        {{-- CABEÇALHO HERO COM BUSCA --}}
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-primary-950 to-primary-900 p-6 sm:p-8 text-white shadow-xl border border-primary-800/40">
            <div class="relative z-10 max-w-3xl space-y-4">
                <div class="inline-flex items-center gap-2 rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/30 px-3 py-1 text-xs font-bold backdrop-blur-md">
                    <x-heroicon-m-shield-check class="h-4 w-4 text-amber-400" />
                    <span>Área Exclusiva · Super Administradores da Escola</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white">
                    Manual de Definição de Permissões e Perfis da Equipe
                </h1>
                <p class="text-sm sm:text-base text-slate-300 leading-relaxed">
                    Guia estratégico para o diretor e gestor da escola configurar exatamente quais telas, botões, finanças e cadastros cada colaborador pode visualizar ou alterar, com máxima segurança contra fraudes e erros operacionais.
                </p>

                {{-- BUSCA DINÂMICA EM TEMPO REAL --}}
                <div class="pt-2 max-w-xl">
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                            <x-heroicon-m-magnifying-glass class="h-5 w-5" />
                        </div>
                        <input
                            type="text"
                            wire:model.live.debounce.250ms="search"
                            placeholder="Buscar no manual (ex: secretaria, lms, excluir contrato, caixa, comissao)..."
                            class="block w-full rounded-2xl border-0 bg-white/10 py-3 pl-11 pr-4 text-sm text-white placeholder:text-slate-400 backdrop-blur-md focus:bg-white focus:text-gray-900 focus:placeholder:text-gray-400 focus:ring-2 focus:ring-amber-400 transition"
                        />
                    </div>
                </div>
            </div>

            {{-- Fundo decorativo sutil --}}
            <div class="absolute -right-12 -top-12 h-64 w-64 rounded-full bg-primary-500/10 blur-3xl"></div>
            <div class="absolute right-24 -bottom-16 h-48 w-48 rounded-full bg-amber-500/15 blur-2xl"></div>
        </div>

        {{-- BARRA DE NAVEGAÇÃO POR CATEGORIAS --}}
        <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none">
            @foreach ($this->categories as $catKey => $catData)
                <button
                    type="button"
                    wire:click="$set('activeCategory', '{{ $catKey }}')"
                    class="inline-flex shrink-0 items-center gap-2 rounded-xl px-4 py-2.5 text-xs font-bold transition shadow-sm {{ $activeCategory === $catKey ? 'bg-primary-600 text-white ring-2 ring-primary-500 ring-offset-2 dark:ring-offset-gray-900' : 'bg-white text-gray-700 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700' }}"
                >
                    <x-filament::icon :icon="$catData['icon']" class="h-4 w-4" />
                    <span>{{ $catData['label'] }}</span>
                </button>
            @endforeach
        </div>

        {{-- ==================== SEÇÃO 1: CONCEITO FUNDAMENTAL ==================== --}}
        @php
            $sConceito = [
                'categories' => ['all', 'conceito'],
                'terms' => ['cargo', 'funcao', 'papel', 'role', 'permissao', 'chave', 'shield', 'conceito', 'logica'],
            ];
        @endphp
        @if ($this->isVisibleSection('conceito', $sConceito))
            <section class="rounded-2xl bg-white p-6 shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700 space-y-4">
                <div class="flex items-center gap-3 border-b border-gray-100 pb-4 dark:border-gray-700">
                    <div class="rounded-xl bg-primary-100 p-2.5 text-primary-600 dark:bg-primary-950 dark:text-primary-400">
                        <x-heroicon-m-key class="h-6 w-6" />
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white">1. Como Funciona a Gestão de Acessos no Aurosoft</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">A regra de ouro: Cargos (Papéis) agrupam Chaves (Permissões).</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-700 dark:text-gray-300 leading-relaxed">
                    <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-900/60 border border-gray-200/80 dark:border-gray-700/80 space-y-2">
                        <div class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300 text-xs font-bold">1</span>
                            O que é um Papel / Cargo (Role)?
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400">
                            É a <strong>função da pessoa na escola</strong> (ex: <em>Secretaria</em>, <em>Educador</em>, <em>Consultor Comercial</em>). Você configura o cargo uma única vez na tela de <strong>Administrativo → Papéis</strong> e simplesmente associa os colaboradores a esse cargo. Se a escola contratar 5 recepcionistas, todos herdam as mesmas permissões instantaneamente.
                        </p>
                    </div>

                    <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-900/60 border border-gray-200/80 dark:border-gray-700/80 space-y-2">
                        <div class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300 text-xs font-bold">2</span>
                            O que é uma Permissão Granular?
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400">
                            É o <strong>poder individual de cada botão ou tela</strong>. No Aurosoft, o acesso é granular: uma secretária pode ter permissão para <em>Cadastrar Aluno</em> e <em>Receber Mensalidade</em>, mas <strong>NÃO</strong> ter permissão para <em>Excluir Contrato</em> ou <em>Abonar Juros e Multas</em>.
                        </p>
                    </div>
                </div>

                <div class="rounded-xl bg-amber-50 p-4 border border-amber-200 dark:bg-amber-950/40 dark:border-amber-800/50 text-amber-900 dark:text-amber-200 text-xs leading-relaxed flex items-start gap-3">
                    <x-heroicon-m-exclamation-triangle class="h-5 w-5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" />
                    <div>
                        <strong class="font-bold">Aviso Crítico sobre o papel `super_admin`:</strong>
                        O papel de <code>super_admin</code> deve ser reservado <strong>estritamente para a direção e donos da escola</strong>. Um usuário com <code>super_admin</code> ignora todas as regras, pode apagar o banco, excluir contratos, ver faturamento global e alterar dados bancários. Para funcionários (mesmo gerentes), sempre crie um papel com as permissões que eles realmente precisam.
                    </div>
                </div>
            </section>
        @endif

        {{-- ==================== SEÇÃO 2: MODELOS PRONTOS POR FUNÇÃO (CHECKLISTS) ==================== --}}
        @php
            $sCargos = [
                'categories' => ['all', 'cargos'],
                'terms' => ['secretaria', 'atendimento', 'educador', 'professor', 'comercial', 'vendas', 'financeiro', 'caixa', 'checklist', 'modelo', 'cargo'],
            ];
        @endphp
        @if ($this->isVisibleSection('cargos', $sCargos))
            <section class="rounded-2xl bg-white p-6 shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700 space-y-6">
                <div class="flex items-center gap-3 border-b border-gray-100 pb-4 dark:border-gray-700">
                    <div class="rounded-xl bg-indigo-100 p-2.5 text-indigo-600 dark:bg-indigo-950 dark:text-indigo-400">
                        <x-heroicon-m-user-group class="h-6 w-6" />
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white">2. Modelos Prontos por Função na Escola (Checklists Recomendados)</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Copie estes checklists para configurar a equipe sem dúvidas.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                    {{-- CARGO 1: SECRETARIA / ATENDIMENTO --}}
                    <div class="rounded-2xl border-2 border-primary-200 dark:border-primary-800 p-5 bg-primary-50/20 dark:bg-primary-950/20 space-y-3">
                        <div class="flex items-center justify-between">
                            <h3 class="text-base font-bold text-primary-900 dark:text-primary-100 flex items-center gap-2">
                                <span>📋 Secretaria & Atendimento ao Aluno</span>
                            </h3>
                            <span class="text-[10px] font-bold uppercase tracking-wider bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-200 px-2 py-0.5 rounded-full">
                                Mais Usado
                            </span>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400">
                            Responsável por matricular alunos, cadastrar contatos, imprimir contratos/carnês, consultar horários e receber mensalidades no balcão.
                        </p>

                        <div class="space-y-2 text-xs">
                            <div class="font-bold text-gray-800 dark:text-gray-200">O que marcar na aba "Recursos":</div>
                            <ul class="space-y-1 text-gray-700 dark:text-gray-300">
                                <li>✅ <strong>Pessoas (`Person`):</strong> Listar, Visualizar, Cadastrar e Editar.</li>
                                <li>✅ <strong>Contratos (`Contract`):</strong> Listar, Visualizar, Cadastrar e Editar.</li>
                                <li>✅ <strong>Mensalidades (`Installment`):</strong> Listar, Visualizar e Editar (para dar baixa).</li>
                                <li>✅ <strong>Caixas (`CashRegister`):</strong> Listar, Visualizar e Editar (abrir/fechar caixa).</li>
                                <li>✅ <strong>Movimentações (`CashTransaction`):</strong> Listar, Visualizar e Cadastrar (entradas/saídas).</li>
                                <li>✅ <strong>Horários (`StudentSchedule`):</strong> Listar, Visualizar, Cadastrar e Editar.</li>
                                <li>✅ <strong>Chamada (`StudentAttendance`):</strong> Listar, Visualizar, Cadastrar e Editar.</li>
                                <li>✅ <strong>Modelos de Documentos, Cursos e Formações:</strong> Listar e Visualizar.</li>
                            </ul>

                            <div class="font-bold text-gray-800 dark:text-gray-200 pt-1">O que marcar na aba "Páginas":</div>
                            <ul class="space-y-1 text-gray-700 dark:text-gray-300">
                                <li>✅ <strong>Alunos no LMS sem cadastro</strong> (`view_ouro_moderno_lms_students`)</li>
                                <li>✅ <strong>Quadro de Horários</strong> (`view_schedule_board`)</li>
                                <li>✅ <strong>Agenda da Escola</strong> (`view_agenda`)</li>
                                <li>✅ <strong>Relatório de Inadimplência</strong> (`view_overdue_installment_report`)</li>
                            </ul>

                            <div class="pt-2 text-[11px] font-semibold text-rose-600 dark:text-rose-400">
                                🚫 O que NÃO marcar: Excluir Contrato, Excluir Mensalidade, DRE Gerencial, Gestão de Backups.
                            </div>
                        </div>
                    </div>

                    {{-- CARGO 2: EDUCADOR / PEDAGÓGICO --}}
                    <div class="rounded-2xl border border-gray-200 dark:border-gray-700 p-5 bg-white dark:bg-gray-900/40 space-y-3">
                        <div class="flex items-center justify-between">
                            <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <span>🎓 Educador / Professor de Laboratório</span>
                            </h3>
                            <span class="text-[10px] font-bold uppercase tracking-wider bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200 px-2 py-0.5 rounded-full">
                                Pedagógico
                            </span>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400">
                            Atua no laboratório de informática tirando dúvidas, acompanhando digitação, lançando chamadas e monitorando aulas.
                        </p>

                        <div class="space-y-2 text-xs">
                            <div class="font-bold text-gray-800 dark:text-gray-200">O que marcar na aba "Recursos":</div>
                            <ul class="space-y-1 text-gray-700 dark:text-gray-300">
                                <li>✅ <strong>Chamada (`StudentAttendance`):</strong> Listar, Visualizar, Cadastrar e Editar.</li>
                                <li>✅ <strong>Horários (`StudentSchedule`):</strong> Listar e Visualizar.</li>
                                <li>✅ <strong>Digitação (`TypingExercise`):</strong> Listar, Visualizar, Cadastrar e Editar.</li>
                                <li>✅ <strong>Salas e Cursos:</strong> Listar e Visualizar.</li>
                                <li>✅ <strong>Pessoas (`Person`):</strong> Listar e Visualizar (para ver dados do aluno).</li>
                            </ul>

                            <div class="font-bold text-gray-800 dark:text-gray-200 pt-1">O que marcar na aba "Páginas":</div>
                            <ul class="space-y-1 text-gray-700 dark:text-gray-300">
                                <li>✅ <strong>Monitor de Laboratório (Ao Vivo)</strong> (`view_classroom_live_monitor`)</li>
                                <li>✅ <strong>Estúdio LMS / Aulas</strong> (`view_lms_studio`)</li>
                                <li>✅ <strong>Relatório de Faltas</strong> (`view_student_absences_report`)</li>
                                <li>✅ <strong>Acompanhamento Pedagógico</strong> (`view_pedagogical_follow_up_report`)</li>
                            </ul>

                            <div class="pt-2 text-[11px] font-semibold text-rose-600 dark:text-rose-400">
                                🚫 O que NÃO marcar: Toda a parte Financeira (Caixa, Contratos, Carnês, Pagamentos).
                            </div>
                        </div>
                    </div>

                    {{-- CARGO 3: COMERCIAL / VENDAS --}}
                    <div class="rounded-2xl border border-gray-200 dark:border-gray-700 p-5 bg-white dark:bg-gray-900/40 space-y-3">
                        <div class="flex items-center justify-between">
                            <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <span>🎯 Consultor Comercial / Vendas</span>
                            </h3>
                            <span class="text-[10px] font-bold uppercase tracking-wider bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200 px-2 py-0.5 rounded-full">
                                CRM
                            </span>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400">
                            Focado em captar novos interessados, agendar visitas comerciais na escola, apresentar cursos e bater metas de vendas.
                        </p>

                        <div class="space-y-2 text-xs">
                            <div class="font-bold text-gray-800 dark:text-gray-200">O que marcar na aba "Recursos":</div>
                            <ul class="space-y-1 text-gray-700 dark:text-gray-300">
                                <li>✅ <strong>Interessados (`CommercialLead`):</strong> Listar, Visualizar, Cadastrar e Editar.</li>
                                <li>✅ <strong>Visitas (`CommercialVisit`):</strong> Listar, Visualizar, Cadastrar e Editar.</li>
                                <li>✅ <strong>Pessoas (`Person`):</strong> Listar e Cadastrar.</li>
                                <li>✅ <strong>Formações e Cursos:</strong> Listar e Visualizar (para consultar preços e pacotes).</li>
                            </ul>

                            <div class="font-bold text-gray-800 dark:text-gray-200 pt-1">O que marcar na aba "Páginas":</div>
                            <ul class="space-y-1 text-gray-700 dark:text-gray-300">
                                <li>✅ <strong>Funil de Vendas (Kanban)</strong> (`view_commercial_leads_kanban`)</li>
                                <li>✅ <strong>Relatório Comercial</strong> (`view_commercial_report`)</li>
                                <li>✅ <strong>Relatório de Vendas por Vendedor</strong> (`view_sales_by_seller_report`)</li>
                            </ul>
                        </div>
                    </div>

                    {{-- CARGO 4: FINANCEIRO & CONTROLADORIA --}}
                    <div class="rounded-2xl border border-gray-200 dark:border-gray-700 p-5 bg-white dark:bg-gray-900/40 space-y-3">
                        <div class="flex items-center justify-between">
                            <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <span>💰 Financeiro & Caixa Central</span>
                            </h3>
                            <span class="text-[10px] font-bold uppercase tracking-wider bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200 px-2 py-0.5 rounded-full">
                                Contas & DRE
                            </span>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400">
                            Responsável pelo fechamento dos caixas, contas bancárias, contas a pagar, conciliação e relatórios de DRE.
                        </p>

                        <div class="space-y-2 text-xs">
                            <div class="font-bold text-gray-800 dark:text-gray-200">O que marcar na aba "Recursos":</div>
                            <ul class="space-y-1 text-gray-700 dark:text-gray-300">
                                <li>✅ <strong>Mensalidades (`Installment`):</strong> Listar, Visualizar, Cadastrar e Editar.</li>
                                <li>✅ <strong>Caixas e Transações:</strong> Listar, Visualizar, Cadastrar e Editar.</li>
                                <li>✅ <strong>Contas a Pagar (`Payable`):</strong> Listar, Visualizar, Cadastrar e Editar.</li>
                                <li>✅ <strong>Contas Bancárias (`FinancialAccount`):</strong> Listar, Visualizar e Editar.</li>
                                <li>✅ <strong>Categorias Financeiras (`FinancialType`):</strong> Listar, Visualizar, Cadastrar e Editar.</li>
                            </ul>

                            <div class="font-bold text-gray-800 dark:text-gray-200 pt-1">O que marcar na aba "Páginas":</div>
                            <ul class="space-y-1 text-gray-700 dark:text-gray-300">
                                <li>✅ <strong>DRE Gerencial</strong> (`view_dre_report`)</li>
                                <li>✅ <strong>Fluxo de Caixa Projetado</strong> (`view_cash_flow_forecast`)</li>
                                <li>✅ <strong>Relatório de Inadimplência</strong> (`view_overdue_installment_report`)</li>
                                <li>✅ <strong>Relatório de Comissões</strong> (`view_commission_report`)</li>
                            </ul>
                        </div>
                    </div>

                </div>
            </section>
        @endif

        {{-- ==================== SEÇÃO 3: HERANÇA AUTOMÁTICA (O QUE JÁ ESTÁ INTERLIGADO) ==================== --}}
        @php
            $sInterligacoes = [
                'categories' => ['all', 'interligacoes'],
                'terms' => ['heranca', 'lms', 'ouro moderno', 'automatica', 'interligacao', '403', 'onboarding', 'matricula'],
            ];
        @endphp
        @if ($this->isVisibleSection('interligacoes', $sInterligacoes))
            <section class="rounded-2xl bg-white p-6 shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700 space-y-4">
                <div class="flex items-center gap-3 border-b border-gray-100 pb-4 dark:border-gray-700">
                    <div class="rounded-xl bg-emerald-100 p-2.5 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400">
                        <x-heroicon-m-arrow-path-rounded-square class="h-6 w-6" />
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white">3. Herança Inteligente do Sistema (Sem necessidade de super_admin)</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Como o Aurosoft interliga permissões dependentes automaticamente para você.</p>
                    </div>
                </div>

                <div class="text-sm text-gray-700 dark:text-gray-300 space-y-3 leading-relaxed">
                    <p>
                        Para evitar que você tenha que marcar 10 caixinhas técnicas diferentes para um atendente apenas cadastrar um aluno que veio do LMS, o Aurosoft possui <strong>Herança Inteligente de Permissões</strong>:
                    </p>

                    <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/50 space-y-2">
                        <div class="font-bold text-emerald-900 dark:text-emerald-200 flex items-center gap-2">
                            <x-heroicon-m-check-badge class="w-5 h-5 text-emerald-600" />
                            Regra do Fluxo de Alunos do LMS sem Cadastro:
                        </div>
                        <p class="text-xs text-emerald-800 dark:text-emerald-300 leading-normal">
                            Quando o colaborador possui permissão para acessar a tela <strong>"Alunos no LMS sem cadastro"</strong> (<code>View:OuroModernoLmsStudents</code>), o sistema <strong>automaticamente e de forma segura</strong> autoriza as etapas filhas:
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 pt-1 text-xs">
                            <div class="bg-white dark:bg-gray-800 p-2.5 rounded-lg border border-emerald-200 dark:border-emerald-700 font-medium">
                                1. Cadastrar a Pessoa física e contatos
                            </div>
                            <div class="bg-white dark:bg-gray-800 p-2.5 rounded-lg border border-emerald-200 dark:border-emerald-700 font-medium">
                                2. Vincular o Responsável Financeiro
                            </div>
                            <div class="bg-white dark:bg-gray-800 p-2.5 rounded-lg border border-emerald-200 dark:border-emerald-700 font-medium">
                                3. Liberar o botão e criação do Contrato
                            </div>
                        </div>
                    </div>

                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        <em>Resultado prático:</em> Você nunca mais precisará conceder <code>super_admin</code> para a secretária conseguir matricular os alunos que sincronizam do LMS.
                    </p>
                </div>
            </section>
        @endif

        {{-- ==================== SEÇÃO 4: DICIONÁRIO DE PERMISSÕES ==================== --}}
        @php
            $sDicionario = [
                'categories' => ['all', 'dicionario'],
                'terms' => ['dicionario', 'tabela', 'view', 'create', 'update', 'delete', 'view_any', 'prefixos', 'termos'],
            ];
        @endphp
        @if ($this->isVisibleSection('dicionario', $sDicionario))
            <section class="rounded-2xl bg-white p-6 shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700 space-y-4">
                <div class="flex items-center gap-3 border-b border-gray-100 pb-4 dark:border-gray-700">
                    <div class="rounded-xl bg-blue-100 p-2.5 text-blue-600 dark:bg-blue-950 dark:text-blue-400">
                        <x-heroicon-m-book-open class="h-6 w-6" />
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white">4. Dicionário dos Prefixos de Ação</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Entenda exatamente o que cada ação autoriza no sistema.</p>
                    </div>
                </div>

                <div class="overflow-x-auto border border-gray-200 dark:border-gray-700 rounded-xl">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-gray-50 dark:bg-gray-900/60 text-gray-600 dark:text-gray-300 font-bold border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="p-3 w-40">Prefixo Técnico</th>
                                <th class="p-3 w-64">Nome no Aurosoft</th>
                                <th class="p-3">O que permite fazer na prática</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700 text-gray-700 dark:text-gray-300">
                            <tr>
                                <td class="p-3 font-mono text-primary-600 dark:text-primary-400 font-semibold">view_any</td>
                                <td class="p-3 font-bold">Listar e ver registros na tabela</td>
                                <td class="p-3 text-gray-600 dark:text-gray-400">Ver o menu lateral e acessar a listagem/tabela principal daquele recurso.</td>
                            </tr>
                            <tr>
                                <td class="p-3 font-mono text-primary-600 dark:text-primary-400 font-semibold">view</td>
                                <td class="p-3 font-bold">Visualizar ficha detalhada</td>
                                <td class="p-3 text-gray-600 dark:text-gray-400">Clicar em "Visualizar" para ler os dados completos de um registro sem poder alterar.</td>
                            </tr>
                            <tr>
                                <td class="p-3 font-mono text-emerald-600 dark:text-emerald-400 font-semibold">create</td>
                                <td class="p-3 font-bold">Cadastrar novo registro</td>
                                <td class="p-3 text-gray-600 dark:text-gray-400">Aparecer o botão "+ Novo" e salvar novos dados no banco de dados.</td>
                            </tr>
                            <tr>
                                <td class="p-3 font-mono text-amber-600 dark:text-amber-400 font-semibold">update</td>
                                <td class="p-3 font-bold">Editar e alterar dados</td>
                                <td class="p-3 text-gray-600 dark:text-gray-400">Abrir o formulário de edição e salvar alterações em registros existentes.</td>
                            </tr>
                            <tr>
                                <td class="p-3 font-mono text-rose-600 dark:text-rose-400 font-semibold">delete</td>
                                <td class="p-3 font-bold">Excluir registro</td>
                                <td class="p-3 text-gray-600 dark:text-gray-400">Exibir o botão de lixeira para apagar um registro individual.</td>
                            </tr>
                            <tr>
                                <td class="p-3 font-mono text-rose-700 dark:text-rose-300 font-semibold">delete_any</td>
                                <td class="p-3 font-bold">Excluir registros em lote</td>
                                <td class="p-3 text-gray-600 dark:text-gray-400">Permite marcar várias caixas na tabela e apagar 10, 50 ou 100 itens de uma só vez.</td>
                            </tr>
                            <tr>
                                <td class="p-3 font-mono text-blue-600 dark:text-blue-400 font-semibold">restore</td>
                                <td class="p-3 font-bold">Restaurar item excluído</td>
                                <td class="p-3 text-gray-600 dark:text-gray-400">Reativar registros que foram arquivados/excluídos via lixeira (Soft Delete).</td>
                            </tr>
                            <tr>
                                <td class="p-3 font-mono text-purple-600 dark:text-purple-400 font-semibold">reactivate</td>
                                <td class="p-3 font-bold">Reativar contrato</td>
                                <td class="p-3 text-gray-600 dark:text-gray-400">Reativar contratos de alunos que estavam trancados, concluídos ou cancelados.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        {{-- ==================== SEÇÃO 5: AÇÕES CRÍTICAS DE ALTA SEGURANÇA ==================== --}}
        @php
            $sSeguranca = [
                'categories' => ['all', 'seguranca'],
                'terms' => ['criticas', 'seguranca', 'risco', 'fraude', 'dinheiro', 'multa', 'desconto', 'backup', 'exclusao', 'waivefine', 'cancel', 'transfer', 'delete'],
            ];
        @endphp
        @if ($this->isVisibleSection('seguranca', $sSeguranca))
            <section class="rounded-2xl bg-white p-6 shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700 space-y-4">
                <div class="flex items-center gap-3 border-b border-gray-100 pb-4 dark:border-gray-700">
                    <div class="rounded-xl bg-rose-100 p-2.5 text-rose-600 dark:bg-rose-950 dark:text-rose-400">
                        <x-heroicon-m-exclamation-triangle class="h-6 w-6" />
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white">5. Permissões Críticas (Cuidado Máximo ao Atribuir)</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Ações sensíveis que envolvem dinheiro, exclusão irreversível de dados e segurança da escola.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                    <div class="p-4 rounded-xl border border-rose-200 dark:border-rose-900/60 bg-rose-50/30 dark:bg-rose-950/20 space-y-1.5">
                        <div class="font-bold text-rose-900 dark:text-rose-200 flex items-center gap-2">
                            <span>💸 Abonar / Alterar Juros e Multa (`WaiveFine:Installment`)</span>
                        </div>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                            Permite ao operador zerar os juros e multas de cobranças em atraso. <strong>Recomendação:</strong> Atribuir apenas para o gerente ou financeiro de confiança.
                        </p>
                    </div>

                    <div class="p-4 rounded-xl border border-rose-200 dark:border-rose-900/60 bg-rose-50/30 dark:bg-rose-950/20 space-y-1.5">
                        <div class="font-bold text-rose-900 dark:text-rose-200 flex items-center gap-2">
                            <span>🗑️ Excluir Parcela (`Cancel:Installment` / `Delete:Installment`)</span>
                        </div>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                            Permite remover um boleto ou mensalidade gerada. <strong>Recomendação:</strong> Deixar desmarcado para operadores de caixa comuns para evitar sumiço de cobranças.
                        </p>
                    </div>

                    <div class="p-4 rounded-xl border border-rose-200 dark:border-rose-900/60 bg-rose-50/30 dark:bg-rose-950/20 space-y-1.5">
                        <div class="font-bold text-rose-900 dark:text-rose-200 flex items-center gap-2">
                            <span>🏢 Transferência entre Escolas (`Transfer:SchoolData`)</span>
                        </div>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                            Move alunos, contratos e registros de uma unidade para outra filial da rede. <strong>Recomendação:</strong> Exclusivo para coordenação geral da rede.
                        </p>
                    </div>

                    <div class="p-4 rounded-xl border border-rose-200 dark:border-rose-900/60 bg-rose-50/30 dark:bg-rose-950/20 space-y-1.5">
                        <div class="font-bold text-rose-900 dark:text-rose-200 flex items-center gap-2">
                            <span>💾 Gestão de Backups (`view_backup_management`)</span>
                        </div>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                            Permite baixar cópias completas do banco de dados e restaurar pontos de restauração. <strong>Recomendação:</strong> Restrito à diretoria e suporte técnico.
                        </p>
                    </div>
                </div>
            </section>
        @endif

        {{-- ==================== SEÇÃO 6: PERGUNTAS FREQUENTES & RESOLUÇÃO DE DÚVIDAS ==================== --}}
        @php
            $sFaq = [
                'categories' => ['all', 'faq'],
                'terms' => ['faq', 'duvidas', 'problemas', 'bloqueio', '403', 'pergunta', 'resposta'],
            ];
        @endphp
        @if ($this->isVisibleSection('faq', $sFaq))
            <section class="rounded-2xl bg-white p-6 shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700 space-y-4">
                <div class="flex items-center gap-3 border-b border-gray-100 pb-4 dark:border-gray-700">
                    <div class="rounded-xl bg-purple-100 p-2.5 text-purple-600 dark:bg-purple-950 dark:text-purple-400">
                        <x-heroicon-m-question-mark-circle class="h-6 w-6" />
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white">6. Dúvidas Frequentes & Resolução Rápida</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">O que fazer quando algum funcionário relatar dificuldade de acesso.</p>
                    </div>
                </div>

                <div class="space-y-3 text-xs text-gray-700 dark:text-gray-300">
                    <details class="group rounded-xl border border-gray-200 dark:border-gray-700 p-4 open:bg-gray-50 dark:open:bg-gray-900/40 transition">
                        <summary class="font-bold text-gray-900 dark:text-white cursor-pointer list-none flex items-center justify-between">
                            <span>Um colaborador disse que clicou em um link e deu "403 Não autorizado". Como resolver?</span>
                            <x-heroicon-m-chevron-down class="w-4 h-4 text-gray-400 group-open:rotate-180 transition" />
                        </summary>
                        <p class="pt-3 text-gray-600 dark:text-gray-400 leading-relaxed">
                            Significa que o cargo daquele usuário não tem a permissão marcada para acessar aquela tela ou executar aquela ação. Vá em <strong>Administrativo → Papéis</strong>, clique em <strong>Editar</strong> no cargo dele, localize o módulo correspondente e marque a caixinha de <em>Visualizar</em> ou <em>Cadastrar</em>. Peça para o colaborador recarregar a página (F5).
                        </p>
                    </details>

                    <details class="group rounded-xl border border-gray-200 dark:border-gray-700 p-4 open:bg-gray-50 dark:open:bg-gray-900/40 transition">
                        <summary class="font-bold text-gray-900 dark:text-white cursor-pointer list-none flex items-center justify-between">
                            <span>Criei um funcionário novo, mas ele não consegue acessar nenhum menu. O que houve?</span>
                            <x-heroicon-m-chevron-down class="w-4 h-4 text-gray-400 group-open:rotate-180 transition" />
                        </summary>
                        <p class="pt-3 text-gray-600 dark:text-gray-400 leading-relaxed">
                            Verifique se você vinculou o papel correto ao usuário dele em <strong>Administrativo → Usuários</strong>. Um usuário cadastrado sem nenhum papel atribuído não possui permissão para ver nenhum menu da escola por segurança.
                        </p>
                    </details>

                    <details class="group rounded-xl border border-gray-200 dark:border-gray-700 p-4 open:bg-gray-50 dark:open:bg-gray-900/40 transition">
                        <summary class="font-bold text-gray-900 dark:text-white cursor-pointer list-none flex items-center justify-between">
                            <span>O colaborador pode ter mais de um cargo ao mesmo tempo?</span>
                            <x-heroicon-m-chevron-down class="w-4 h-4 text-gray-400 group-open:rotate-180 transition" />
                        </summary>
                        <p class="pt-3 text-gray-600 dark:text-gray-400 leading-relaxed">
                            <strong>Sim!</strong> O Aurosoft suporta múltiplos papéis para um mesmo usuário. Se uma pessoa atua como <em>Secretaria</em> de manhã e dá aulas como <em>Educador</em> à tarde, você pode marcar ambos os cargos no cadastro de usuário dela. O sistema somará as permissões de ambos automaticamente.
                        </p>
                    </details>
                </div>
            </section>
        @endif

    </div>
</x-filament-panels::page>

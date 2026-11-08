<x-filament-panels::page>
    <x-report-document-header title="Relatório e Gestão de Gamificação da Ouro Moderno" />

    <div class="space-y-6">
        <!-- Barra de Filtros e Ações Principais -->
        <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <label class="space-y-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                    <span>Buscar jogador</span>
                    <input wire:model="search" wire:keydown.enter="loadReport" placeholder="Nome ou ID do aluno" class="h-11 w-full rounded-xl border-0 bg-white px-3 shadow-sm ring-1 ring-gray-950/10 dark:bg-gray-950 dark:text-white dark:ring-white/10">
                </label>
                <label class="space-y-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                    <span>Ativação</span>
                    <select wire:model="status" class="h-11 w-full rounded-xl border-0 bg-white px-3 shadow-sm ring-1 ring-gray-950/10 dark:bg-gray-950 dark:text-white dark:ring-white/10">
                        <option value="todos">Todos</option>
                        <option value="ativo">Ativos</option>
                        <option value="inativo">Inativos</option>
                    </select>
                </label>
                <div class="flex items-end gap-2 flex-wrap">
                    <x-filament::button wire:click="loadReport" icon="heroicon-o-arrow-path">
                        Consultar LMS
                    </x-filament::button>
                    <x-filament::button wire:click="openRulesModal" color="warning" icon="heroicon-o-book-open">
                        Regras de Pontos
                    </x-filament::button>
                    @if (auth()->user()?->can('Export:OuroModernoGamificationReport') && $lastUpdated)
                        <x-filament::button wire:click="exportReport" color="gray" icon="heroicon-o-arrow-down-tray">
                            Exportar CSV
                        </x-filament::button>
                    @endif
                </div>
                @if ($lastUpdated)
                    <p class="self-end text-sm text-gray-500 dark:text-gray-400">
                        Atualizado em {{ $lastUpdated }} · {{ $pagination['total_records'] ?? 0 }} jogadores
                    </p>
                @endif
            </div>
        </section>

        <!-- Tabela de Jogadores -->
        <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800 flex justify-between items-center">
                <h2 class="font-semibold text-gray-950 dark:text-white flex items-center gap-2">
                    <span>🎮</span> Jogadores, Pontuações e Ações
                </h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-gray-600 dark:bg-gray-950 dark:text-gray-300">
                        <tr>
                            <th class="px-4 py-3">Aluno</th>
                            <th class="px-4 py-3">Pontos</th>
                            <th class="px-4 py-3">Moedas</th>
                            <th class="px-4 py-3">Conquistas</th>
                            <th class="px-4 py-3">Nível</th>
                            <th class="px-4 py-3">Situação</th>
                            <th class="px-4 py-3 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($players as $player)
                            @php
                                $playerId = (int) ($player['user_id'] ?? $player['id_aluno'] ?? 0);
                                $playerName = $player['user_name'] ?? 'Jogador '.$playerId;
                            @endphp
                            <tr class="text-gray-800 dark:text-gray-200 hover:bg-gray-50/50 dark:hover:bg-gray-800/50">
                                <td class="px-4 py-3 font-medium">
                                    <div class="font-semibold text-gray-900 dark:text-white">{{ $playerName }}</div>
                                    <div class="text-xs text-gray-500">ID LMS: {{ $playerId }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-1 font-bold text-amber-600 dark:text-amber-400">
                                        ⭐ {{ number_format((float) ($player['total_points'] ?? 0), 0, ',', '.') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-1 font-bold text-emerald-600 dark:text-emerald-400">
                                        🪙 {{ number_format((float) ($player['total_coins'] ?? 0), 0, ',', '.') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-1">
                                        🏆 {{ $player['total_achievements'] ?? '0' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-md bg-purple-50 px-2 py-1 text-xs font-medium text-purple-700 ring-1 ring-inset ring-purple-700/10 dark:bg-purple-900/30 dark:text-purple-300">
                                        {{ data_get($player, 'level.current_level', data_get($player, 'level.name', 'Iniciante')) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset {{ ($player['gamificacao_efetiva'] ?? false) || ($player['gamificacao_label'] ?? '') === 'Ativa' ? 'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-900/30 dark:text-green-300' : 'bg-gray-50 text-gray-600 ring-gray-500/10 dark:bg-gray-800 dark:text-gray-400' }}">
                                        {{ $player['gamificacao_label'] ?? (($player['gamificacao_efetiva'] ?? false) ? 'Ativa' : 'Inativa') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-end items-center gap-2">
                                        @if ($playerId > 0)
                                            <button type="button" wire:click="viewStudentProfile({{ $playerId }})" class="inline-flex items-center gap-1 rounded-lg bg-gray-100 px-2.5 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                                                <span>🔍</span> Perfil & Conquistas
                                            </button>
                                            <button type="button" wire:click="openAdjustmentModal({{ $playerId }}, '{{ addslashes($playerName) }}')" class="inline-flex items-center gap-1 rounded-lg bg-amber-50 px-2.5 py-1.5 text-xs font-semibold text-amber-700 hover:bg-amber-100 dark:bg-amber-900/30 dark:text-amber-300 dark:hover:bg-amber-900/50">
                                                <span>⚡</span> Lançar
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-10 text-center text-gray-500">
                                    Clique em “Consultar LMS” para carregar os jogadores.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($lastUpdated && (($pagination['total_pages'] ?? 1) > 1))
                <div class="flex items-center justify-between border-t border-gray-200 px-5 py-3 dark:border-gray-800">
                    <x-filament::button wire:click="goToPage({{ max(1, $playersPage - 1) }})" color="gray" size="sm" :disabled="! ($pagination['has_previous'] ?? false)">
                        Anterior
                    </x-filament::button>
                    <span class="text-sm text-gray-500">Página {{ $pagination['current_page'] }} de {{ $pagination['total_pages'] }}</span>
                    <x-filament::button wire:click="goToPage({{ $playersPage + 1 }})" color="gray" size="sm" :disabled="! ($pagination['has_next'] ?? false)">
                        Próxima
                    </x-filament::button>
                </div>
            @endif
        </section>

        <!-- Ranking dos Alunos -->
        <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                <h2 class="font-semibold text-gray-950 dark:text-white flex items-center gap-2">
                    <span>🏆</span> Ranking dos Melhores Alunos (Top 50)
                </h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-gray-600 dark:bg-gray-950 dark:text-gray-300">
                        <tr>
                            <th class="px-4 py-3">Posição</th>
                            <th class="px-4 py-3">Aluno</th>
                            <th class="px-4 py-3">Pontos</th>
                            <th class="px-4 py-3">Atividades em Sala</th>
                            <th class="px-4 py-3">Nível</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($ranking as $player)
                            @php
                                $pos = (int) ($player['ranking_position'] ?? $loop->iteration);
                                $medal = match ($pos) {
                                    1 => '🥇 1º Lugar',
                                    2 => '🥈 2º Lugar',
                                    3 => '🥉 3º Lugar',
                                    default => "{$pos}º",
                                };
                            @endphp
                            <tr class="text-gray-800 dark:text-gray-200">
                                <td class="px-4 py-3 font-bold text-amber-600 dark:text-amber-400">{{ $medal }}</td>
                                <td class="px-4 py-3 font-medium">{{ $player['user_name'] ?? 'Jogador '.$player['user_id'] }}</td>
                                <td class="px-4 py-3 font-bold">{{ number_format((float) ($player['total_points'] ?? 0), 0, ',', '.') }} pts</td>
                                <td class="px-4 py-3">{{ $player['sala_activities'] ?? '0' }}</td>
                                <td class="px-4 py-3">{{ data_get($player, 'level.name', '—') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-8 text-center text-gray-500">Ranking não carregado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <!-- Modal Perfil Completo & Conquistas do Jogador -->
    @if ($selectedStudentId && $selectedStudentProfile)
        <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-gray-900/60 p-4 backdrop-blur-sm">
            <div class="relative w-full max-w-3xl rounded-2xl bg-white p-6 shadow-2xl dark:bg-gray-900 border border-gray-100 dark:border-gray-800">
                <div class="flex items-center justify-between border-b border-gray-100 pb-4 dark:border-gray-800">
                    <div class="flex items-center gap-3">
                        <div class="h-12 w-12 rounded-full bg-gradient-to-tr from-amber-500 to-amber-300 flex items-center justify-center text-2xl text-white shadow-md">
                            🎯
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                {{ $selectedStudentProfile['user_name'] ?? $selectedStudentProfile['nome'] ?? 'Perfil do Aluno' }}
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">ID LMS: {{ $selectedStudentId }}</p>
                        </div>
                    </div>
                    <button type="button" wire:click="closeStudentProfile" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-800">
                        ✕
                    </button>
                </div>

                <!-- Stats do Aluno -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 my-4">
                    <div class="rounded-xl bg-amber-50 p-3 text-center dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900/50">
                        <div class="text-xs font-semibold text-amber-700 dark:text-amber-300 uppercase">Pontos</div>
                        <div class="text-2xl font-black text-amber-900 dark:text-amber-100">
                            {{ number_format((float) ($selectedStudentProfile['total_points'] ?? $selectedStudentProfile['pontos'] ?? 0), 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="rounded-xl bg-emerald-50 p-3 text-center dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-900/50">
                        <div class="text-xs font-semibold text-emerald-700 dark:text-emerald-300 uppercase">Moedas</div>
                        <div class="text-2xl font-black text-emerald-900 dark:text-emerald-100">
                            {{ number_format((float) ($selectedStudentProfile['total_coins'] ?? $selectedStudentProfile['moedas'] ?? 0), 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="rounded-xl bg-purple-50 p-3 text-center dark:bg-purple-950/30 border border-purple-200 dark:border-purple-900/50">
                        <div class="text-xs font-semibold text-purple-700 dark:text-purple-300 uppercase">Nível Atual</div>
                        <div class="text-base font-black text-purple-900 dark:text-purple-100 mt-1">
                            {{ data_get($selectedStudentProfile, 'level.name', data_get($selectedStudentProfile, 'nivel.nome', 'Iniciante')) }}
                        </div>
                    </div>
                    <div class="rounded-xl bg-blue-50 p-3 text-center dark:bg-blue-950/30 border border-blue-200 dark:border-blue-900/50">
                        <div class="text-xs font-semibold text-blue-700 dark:text-blue-300 uppercase">Posição Ranking</div>
                        <div class="text-2xl font-black text-blue-900 dark:text-blue-100">
                            #{{ $selectedStudentProfile['ranking_position'] ?? $selectedStudentProfile['ranking'] ?? '—' }}
                        </div>
                    </div>
                </div>

                <!-- Conquistas Desbloqueadas -->
                <div class="mt-4">
                    <h4 class="text-sm font-bold text-gray-900 dark:text-white mb-2 flex items-center gap-1.5">
                        <span>🏆</span> Conquistas & Medalhas Desbloqueadas
                    </h4>
                    @php
                        $achievements = $selectedStudentProfile['achievements'] ?? $selectedStudentProfile['conquistas'] ?? [];
                    @endphp
                    @if (!empty($achievements))
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-48 overflow-y-auto pr-1">
                            @foreach ($achievements as $ach)
                                <div class="flex items-start gap-2.5 rounded-xl border border-gray-200 p-2.5 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/50">
                                    <div class="text-2xl">
                                        {{ $ach['icone'] ?? $ach['icon'] ?? '🏅' }}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="font-bold text-xs text-gray-900 dark:text-white truncate">
                                            {{ $ach['nome'] ?? $ach['name'] ?? 'Conquista' }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 line-clamp-2">
                                            {{ $ach['descricao'] ?? $ach['description'] ?? '' }}
                                        </div>
                                        @php
                                            $unlockedDate = data_get($ach, 'desbloqueada_em', data_get($ach, 'unlocked_at'));
                                        @endphp
                                        @if ($unlockedDate)
                                            <div class="text-[10px] text-gray-400 mt-0.5">
                                                Desbloqueado em: {{ $unlockedDate }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-xs text-gray-500 py-3 text-center bg-gray-50 rounded-xl dark:bg-gray-800">
                            Nenhuma conquista desbloqueada até o momento.
                        </p>
                    @endif
                </div>

                <!-- Histórico / Extrato Tabs -->
                <div class="mt-5 border-t border-gray-200 pt-4 dark:border-gray-800">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex gap-2">
                            <button type="button" wire:click="switchHistoryTab('points')" class="px-3 py-1 text-xs font-bold rounded-lg {{ $historyTab === 'points' ? 'bg-amber-500 text-white shadow' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' }}">
                                Extrato de Pontos
                            </button>
                            <button type="button" wire:click="switchHistoryTab('coins')" class="px-3 py-1 text-xs font-bold rounded-lg {{ $historyTab === 'coins' ? 'bg-emerald-500 text-white shadow' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' }}">
                                Extrato de Moedas
                            </button>
                        </div>
                        <span class="text-xs text-gray-400">Últimos lançamentos</span>
                    </div>

                    <div class="max-h-40 overflow-y-auto">
                        @php
                            $histRecords = $selectedStudentHistory['historico'] ?? $selectedStudentHistory['history'] ?? $selectedStudentHistory['items'] ?? [];
                        @endphp
                        @if (!empty($histRecords) && is_array($histRecords))
                            <table class="w-full text-left text-xs">
                                <thead class="bg-gray-50 dark:bg-gray-950 text-gray-500">
                                    <tr>
                                        <th class="px-3 py-1.5">Data</th>
                                        <th class="px-3 py-1.5">Descrição / Motivo</th>
                                        <th class="px-3 py-1.5 text-right">Valor</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    @foreach ($histRecords as $item)
                                        <tr>
                                            <td class="px-3 py-1.5 text-gray-500">{{ $item['data'] ?? $item['created_at'] ?? '—' }}</td>
                                            <td class="px-3 py-1.5 text-gray-800 dark:text-gray-200">{{ $item['descricao'] ?? $item['action_type'] ?? 'Lançamento' }}</td>
                                            <td class="px-3 py-1.5 text-right font-bold {{ (int) ($item['pontos'] ?? $item['moedas'] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                                {{ (int) ($item['pontos'] ?? $item['moedas'] ?? 0) >= 0 ? '+' : '' }}{{ $item['pontos'] ?? $item['moedas'] ?? 0 }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <p class="text-xs text-gray-400 text-center py-3">Sem histórico recente disponível.</p>
                        @endif
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <x-filament::button wire:click="closeStudentProfile" color="gray">
                        Fechar
                    </x-filament::button>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal Ajustar Pontos / Moedas Manualmente -->
    @if ($showAdjustmentModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-gray-900/60 p-4 backdrop-blur-sm">
            <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl dark:bg-gray-900 border border-gray-100 dark:border-gray-800">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-800">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">
                        ⚡ Lançamento Manual no Game
                    </h3>
                    <button type="button" wire:click="closeAdjustmentModal" class="text-gray-400 hover:text-gray-700">✕</button>
                </div>

                <p class="mt-2 text-xs text-gray-500">
                    Aluno: <strong class="text-gray-800 dark:text-gray-200">{{ $adjustStudentName }}</strong> (ID LMS: {{ $adjustStudentId }})
                </p>

                <div class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Tipo de Lançamento</label>
                        <select wire:model="adjustType" class="h-10 w-full rounded-xl border-0 bg-white px-3 text-sm shadow-sm ring-1 ring-gray-950/10 dark:bg-gray-950 dark:text-white dark:ring-white/10">
                            <option value="points_add">➕ Adicionar Pontos (+)</option>
                            <option value="points_remove">➖ Remover / Estornar Pontos (-)</option>
                            <option value="coins_add">🪙 Adicionar Moedas (+)</option>
                            <option value="coins_remove">🪙 Remover / Estornar Moedas (-)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Quantidade</label>
                        <input type="number" min="1" wire:model="adjustAmount" class="h-10 w-full rounded-xl border-0 bg-white px-3 text-sm shadow-sm ring-1 ring-gray-950/10 dark:bg-gray-950 dark:text-white dark:ring-white/10">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Motivo / Justificativa</label>
                        <input type="text" wire:model="adjustDescription" placeholder="Ex: Participação excelente em sala de aula" class="h-10 w-full rounded-xl border-0 bg-white px-3 text-sm shadow-sm ring-1 ring-gray-950/10 dark:bg-gray-950 dark:text-white dark:ring-white/10">
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <x-filament::button wire:click="closeAdjustmentModal" color="gray" size="sm">
                        Cancelar
                    </x-filament::button>
                    <x-filament::button wire:click="submitAdjustment" color="primary" size="sm">
                        Confirmar Lançamento
                    </x-filament::button>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal Regras de Pontos do Game -->
    @if ($showRulesModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-gray-900/60 p-4 backdrop-blur-sm">
            <div class="relative w-full max-w-2xl rounded-2xl bg-white p-6 shadow-2xl dark:bg-gray-900 border border-gray-100 dark:border-gray-800">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-800">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <span>📖</span> Regras de Pontuação & Ações do Game
                    </h3>
                    <button type="button" wire:click="closeRulesModal" class="text-gray-400 hover:text-gray-700">✕</button>
                </div>

                <p class="mt-2 text-xs text-gray-500">
                    Mapa de pontuação automática distribuída pelas atividades e ações realizadas na plataforma interativa da Ouro Moderno.
                </p>

                <div class="mt-4 max-h-80 overflow-y-auto">
                    @php
                        $rules = $gameActions['pontos_por_acao'] ?? $gameActions['acoes'] ?? $gameActions ?? [];
                    @endphp
                    @if (!empty($rules) && is_array($rules))
                        <table class="w-full text-left text-xs">
                            <thead class="bg-gray-50 dark:bg-gray-950 text-gray-600">
                                <tr>
                                    <th class="px-3 py-2">Ação / Atividade</th>
                                    <th class="px-3 py-2 text-right">Pontos Atribuídos</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @foreach ($rules as $actionName => $points)
                                    <tr>
                                        <td class="px-3 py-2 font-medium text-gray-800 dark:text-gray-200">
                                            {{ is_string($actionName) ? ucwords(str_replace(['_', '-'], ' ', $actionName)) : ($points['nome'] ?? $points['descricao'] ?? 'Ação') }}
                                        </td>
                                        <td class="px-3 py-2 text-right font-bold text-amber-600 dark:text-amber-400">
                                            +{{ is_scalar($points) ? $points : ($points['pontos'] ?? '—') }} pts
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p class="text-xs text-gray-500 text-center py-6">Nenhuma regra detalhada retornada pela API.</p>
                    @endif
                </div>

                <div class="mt-6 flex justify-end">
                    <x-filament::button wire:click="closeRulesModal" color="gray">
                        Fechar
                    </x-filament::button>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>

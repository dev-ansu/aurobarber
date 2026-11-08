<div class="space-y-4">
    <!-- Cabeçalho do Extrato -->
    <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/60">
        <div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Saldo Atual Disponível</div>
            <div class="text-2xl font-extrabold {{ (float) $record->current_balance >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                R$ {{ number_format($record->current_balance, 2, ',', '.') }}
            </div>
        </div>
        <div class="text-right text-xs text-gray-500 dark:text-gray-400">
            <div>
                Tipo: <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $record->type === 'vault' ? 'Cofre Físico' : ($record->type === 'bank' ? 'Conta Bancária' : 'Outro') }}</span>
            </div>
            @if($record->bank_name)
                <div class="mt-0.5">
                    {{ $record->bank_name }} | Ag: {{ $record->agency_number ?: '—' }} | CC: {{ $record->account_number ?: '—' }}
                </div>
            @endif
        </div>
    </div>

    <!-- Tabela de Movimentações -->
    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
        <table class="w-full text-left text-xs">
            <thead class="border-b border-gray-200 bg-gray-100/75 text-gray-600 dark:border-gray-700 dark:bg-gray-800/90 dark:text-gray-300">
                <tr>
                    <th class="px-3 py-2.5 font-semibold">Data / Hora</th>
                    <th class="px-3 py-2.5 font-semibold">Tipo</th>
                    <th class="px-3 py-2.5 font-semibold">Descrição / Detalhe</th>
                    <th class="px-3 py-2.5 text-right font-semibold">Valor</th>
                    <th class="px-3 py-2.5 text-right font-semibold">Saldo Após</th>
                    <th class="px-3 py-2.5 font-semibold">Operador</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @php
                    $transactions = $record->accountTransactions()->with(['user', 'relatedAccount'])->limit(100)->get();
                @endphp
                @forelse($transactions as $t)
                    @php
                        $isInflow = in_array($t->type, ['deposit', 'transfer_in']);
                        $isOutflow = in_array($t->type, ['withdrawal', 'transfer_out']);
                    @endphp
                    <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-800/40">
                        <td class="whitespace-nowrap px-3 py-2 text-gray-600 dark:text-gray-300">
                            {{ $t->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-2">
                            @if($t->type === 'deposit')
                                <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                                    Depósito
                                </span>
                            @elseif($t->type === 'transfer_in')
                                <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-bold bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300">
                                    Transf. Entrada
                                </span>
                            @elseif($t->type === 'transfer_out')
                                <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300">
                                    Transf. Saída
                                </span>
                            @elseif($t->type === 'withdrawal')
                                <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300">
                                    Retirada
                                </span>
                            @else
                                <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-bold bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300">
                                    Ajuste
                                </span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-gray-800 dark:text-gray-200">
                            <div>{{ $t->description }}</div>
                            @if($t->relatedAccount)
                                <div class="text-[10px] text-gray-500 dark:text-gray-400">
                                    Conta envolvida: {{ $t->relatedAccount->name }}
                                </div>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-3 py-2 text-right font-bold {{ $isInflow ? 'text-emerald-600 dark:text-emerald-400' : ($isOutflow ? 'text-rose-600 dark:text-rose-400' : 'text-gray-900 dark:text-white') }}">
                            {{ $isInflow ? '+ ' : ($isOutflow ? '- ' : '') }}R$ {{ number_format($t->amount, 2, ',', '.') }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-2 text-right font-extrabold text-gray-900 dark:text-white">
                            R$ {{ number_format($t->balance_after, 2, ',', '.') }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-2 text-gray-500 dark:text-gray-400">
                            {{ $t->user?->name ?: 'Sistema' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-3 py-8 text-center text-gray-400 dark:text-gray-500">
                            Nenhuma movimentação registrada nesta conta ainda.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@php
    $messages = $ticket ? $ticket->messages()->orderBy('created_at', 'asc')->get() : collect();
@endphp

<div class="space-y-4">
    <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-800 pb-2">
        <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
            Linha do Tempo da Conversa ({{ $messages->count() }} {{ $messages->count() === 1 ? 'mensagem' : 'mensagens' }})
        </span>
        <span class="text-[11px] font-mono text-gray-400">
            Protocolo: {{ $ticket?->protocol }}
        </span>
    </div>

    @if($messages->isEmpty())
        <div class="rounded-xl border border-dashed border-gray-300 dark:border-gray-800 p-6 text-center text-xs text-gray-500 dark:text-gray-400">
            Nenhuma mensagem complementar no histórico. Apenas a descrição inicial foi registrada.
        </div>
    @else
        <div class="space-y-3 max-h-[420px] overflow-y-auto pr-2 divide-y divide-gray-100 dark:divide-gray-800/40">
            @foreach($messages as $msg)
                @php
                    $isStudent = $msg->author_type === 'student';
                    $isStaff = in_array($msg->author_type, ['staff', 'user'], true);
                    $isMaster = $msg->author_type === 'master';
                    $isSystem = $msg->author_type === 'system';

                    $badgeBg = match(true) {
                        $isStudent => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300 border border-amber-300 dark:border-amber-700/50',
                        $isStaff => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 border border-blue-300 dark:border-blue-700/50',
                        $isMaster => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300 border border-purple-300 dark:border-purple-700/50',
                        default => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300 border border-gray-300 dark:border-gray-700',
                    };

                    $roleLabel = match(true) {
                        $isStudent => '🎓 Aluno(a)',
                        $isStaff => '🏫 Equipe da Escola',
                        $isMaster => '🛡️ Suporte Matriz (SaaS)',
                        default => '🤖 Sistema',
                    };

                    $cardBg = match(true) {
                        $isStudent => 'bg-amber-50/70 dark:bg-amber-950/20 border-amber-200/80 dark:border-amber-900/30',
                        $isStaff => 'bg-blue-50/70 dark:bg-blue-950/20 border-blue-200/80 dark:border-blue-900/30',
                        $isMaster => 'bg-purple-50/70 dark:bg-purple-950/20 border-purple-200/80 dark:border-purple-900/30',
                        default => 'bg-gray-50 dark:bg-gray-900/40 border-gray-200 dark:border-gray-800',
                    };
                @endphp

                <div class="pt-3 first:pt-0">
                    <div class="rounded-xl border p-4 shadow-sm {{ $cardBg }}">
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-2 pb-2 border-b border-gray-200/60 dark:border-gray-800/60">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold tracking-wide {{ $badgeBg }}">
                                    {{ $roleLabel }}
                                </span>
                                <span class="text-xs font-extrabold text-gray-900 dark:text-gray-100">
                                    {{ $msg->author_name ?: ($isStudent ? 'Aluno' : 'Colaborador') }}
                                </span>
                            </div>
                            <span class="text-[11px] font-mono text-gray-500 dark:text-gray-400">
                                {{ $msg->created_at?->format('d/m/Y \à\s H:i') }}
                            </span>
                        </div>

                        <div class="text-xs leading-relaxed text-gray-800 dark:text-gray-200 whitespace-pre-wrap font-normal">
                            {{ $msg->message }}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

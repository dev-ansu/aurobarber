<x-filament-panels::page>
    <div
        x-data="{
            ctxMenu: { open: false, x: 0, y: 0, messageId: null, text: '', isRead: false },
            copiedToast: false,
            openMsgMenu(event, messageId, text, isRead = false) {
                const menuWidth = 270;
                const menuHeight = 230;
                const posX = Math.min(event.clientX, window.innerWidth - menuWidth - 12);
                const posY = Math.min(event.clientY, window.innerHeight - menuHeight - 12);
                this.ctxMenu = {
                    open: true,
                    x: Math.max(8, posX),
                    y: Math.max(8, posY),
                    messageId: messageId,
                    text: text || '',
                    isRead: !!isRead,
                };
            },
            closeMsgMenu() {
                this.ctxMenu.open = false;
            },
            async copyMessageText() {
                const content = this.ctxMenu.text || '';
                this.closeMsgMenu();
                if (!content) return;
                try {
                    await navigator.clipboard.writeText(content);
                    this.copiedToast = true;
                    setTimeout(() => { this.copiedToast = false; }, 2200);
                } catch (e) {}
            }
        }"
        @keydown.escape.window="closeMsgMenu()"
        @scroll.window="closeMsgMenu()"
        wire:poll.8s="refreshChat"
        class="space-y-5"
    >
        {{-- Topo: Status da Rede, Criptografia AES-256 e Botão Comunicado Formal --}}
        <div class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center gap-3">
                <div class="relative flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-400">
                    <x-filament::icon icon="heroicon-o-chat-bubble-left-right" class="h-6 w-6" />
                    <span class="absolute -right-1 -top-1 flex h-3.5 w-3.5">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex h-3.5 w-3.5 rounded-full bg-emerald-500"></span>
                    </span>
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-base font-bold text-gray-900 dark:text-white">
                            {{ $currentSchool?->getFilamentName() ?? 'Rede Geral' }}
                        </h2>
                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300">
                            🟢 {{ $onlineCount }} online agora
                        </span>
                        @if($unreadCount > 0)
                            <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-bold text-red-700 dark:bg-red-900/50 dark:text-red-300">
                                {{ $unreadCount }} não lida(s)
                            </span>
                        @endif
                    </div>
                    <p class="mt-0.5 flex flex-wrap items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                        <span>🔒 <strong>Criptografia AES-256 ativa.</strong></span>
                        <span class="text-indigo-600 dark:text-indigo-400">💡 Dica: clique com o <strong>botão direito</strong> em qualquer mensagem para transformá-la em <strong>Compromisso</strong> ou <strong>To-Do</strong>.</span>
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    wire:click="openCompose('general')"
                    class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-500"
                >
                    <x-filament::icon icon="heroicon-m-megaphone" class="h-4 w-4" />
                    Novo Comunicado Oficial / Protocolo
                </button>
            </div>
        </div>

        {{-- Modal de Comunicado Oficial / Protocolo --}}
        @if($showComposeModal)
            <div class="rounded-xl border-2 border-primary-500/40 bg-white p-6 shadow-md dark:bg-gray-800">
                <div class="mb-4 flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-700">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">
                            ✉️ Emitir Comunicado Oficial ou Mensagem Protocolada
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            🔒 Conteúdo protegido com criptografia AES-256 e notificado automaticamente no sininho do topo.
                        </p>
                    </div>
                    <button
                        type="button"
                        wire:click="$set('showComposeModal', false)"
                        class="text-sm font-medium text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                    >
                        ✕ Fechar
                    </button>
                </div>

                <form wire:submit="sendMessage" class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">
                                Unidade de Destino
                            </label>
                            <select
                                wire:model="recipientSchoolId"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                            >
                                <option value="">📢 Todas as Escolas da Rede (Comunicado Geral)</option>
                                @foreach($otherSchools as $school)
                                    <option value="{{ $school->id }}">🏫 {{ $school->getFilamentName() }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">
                                Categoria
                            </label>
                            <select
                                wire:model="composeCategory"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                            >
                                @foreach($categories as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">
                                Vincular Aluno / Contrato (Opcional)
                            </label>
                            <select
                                wire:model="composeContractId"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                            >
                                <option value="">— Nenhum contrato específico —</option>
                                @foreach($recentContracts as $contract)
                                    <option value="{{ $contract->id }}">
                                        #{{ $contract->contract_number }} — {{ $contract->student?->name ?? 'Aluno' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">
                            Assunto
                        </label>
                        <input
                            type="text"
                            wire:model="composeSubject"
                            placeholder="Ex: Solicitação de histórico / Dúvida pedagógica / Comunicado de feriado"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                        />
                        @error('composeSubject')
                            <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">
                            Mensagem
                        </label>
                        <textarea
                            wire:model="composeBody"
                            rows="4"
                            placeholder="Escreva os detalhes..."
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                        ></textarea>
                        @error('composeBody')
                            <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button
                            type="button"
                            wire:click="$set('showComposeModal', false)"
                            class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500"
                        >
                            <x-filament::icon icon="heroicon-m-paper-airplane" class="h-4 w-4" />
                            Enviar Agora
                        </button>
                    </div>
                </form>
            </div>
        @endif

        {{-- Interface Principal Estilo WhatsApp Web --}}
        <div class="grid grid-cols-1 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 lg:grid-cols-12" style="min-height: 620px;">
            {{-- Coluna Esquerda: Navegação de Contatos Online, Escolas e Transferências --}}
            <div class="flex flex-col border-b border-gray-200 dark:border-gray-700 lg:col-span-4 lg:border-b-0 lg:border-r">
                {{-- Abas da Sidebar --}}
                <div class="grid grid-cols-3 gap-1 border-b border-gray-200 bg-gray-50 p-2 dark:border-gray-700 dark:bg-gray-900/50">
                    <button
                        type="button"
                        wire:click="setSidebarTab('team')"
                        class="rounded-lg px-2 py-2 text-xs font-bold transition {{ $sidebarTab === 'team' ? 'bg-emerald-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-200/60 dark:text-gray-300 dark:hover:bg-gray-800' }}"
                    >
                        💬 Equipe ({{ $onlineCount }})
                    </button>
                    <button
                        type="button"
                        wire:click="setSidebarTab('channels')"
                        class="rounded-lg px-2 py-2 text-xs font-bold transition {{ $sidebarTab === 'channels' ? 'bg-emerald-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-200/60 dark:text-gray-300 dark:hover:bg-gray-800' }}"
                    >
                        🏫 Escolas
                    </button>
                    <button
                        type="button"
                        wire:click="setSidebarTab('threads')"
                        class="rounded-lg px-2 py-2 text-xs font-bold transition {{ $sidebarTab === 'threads' ? 'bg-emerald-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-200/60 dark:text-gray-300 dark:hover:bg-gray-800' }}"
                    >
                        🔄 Protocolos
                    </button>
                </div>

                {{-- Conteúdo da Sidebar conforme a Aba --}}
                <div class="flex-1 overflow-y-auto p-3 space-y-2" style="max-height: 550px;">
                    @if($sidebarTab === 'team')
                        <div class="px-1 pb-1 text-[11px] font-semibold uppercase tracking-wider text-gray-400">
                            Colaboradores da Rede (Clique para conversar)
                        </div>
                        @foreach($teamUsers as $member)
                            @php
                                $isSelectedUser = $chatMode === 'direct_user' && $selectedChatUserId === $member['id'];
                            @endphp
                            <div
                                wire:click="openDirectChatWithUser({{ $member['id'] }})"
                                class="flex cursor-pointer items-center justify-between gap-3 rounded-xl border p-3 transition {{ $isSelectedUser ? 'border-emerald-500 bg-emerald-50/50 dark:border-emerald-500 dark:bg-emerald-950/30' : 'border-gray-100 bg-white hover:bg-gray-50 dark:border-gray-700/60 dark:bg-gray-800 dark:hover:bg-gray-700/40' }}"
                            >
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="relative shrink-0">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700 dark:bg-indigo-900/60 dark:text-indigo-300">
                                            {{ $member['initials'] }}
                                        </div>
                                        <span
                                            class="absolute bottom-0 right-0 h-3 w-3 rounded-full ring-2 ring-white dark:ring-gray-800 {{ $member['is_online'] ? 'bg-emerald-500' : 'bg-gray-300 dark:bg-gray-600' }}"
                                            title="{{ $member['last_seen'] }}"
                                        ></span>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5">
                                            <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">
                                                {{ $member['name'] }}
                                            </p>
                                            @if($member['is_me'])
                                                <span class="rounded bg-gray-100 px-1.5 py-0.5 text-[10px] font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">Você</span>
                                            @endif
                                        </div>
                                        <p class="truncate text-xs text-gray-500 dark:text-gray-400">
                                            🏫 {{ $member['schools'] }} • {{ $member['role'] }}
                                        </p>
                                        <p class="text-[11px] {{ $member['is_online'] ? 'font-semibold text-emerald-600 dark:text-emerald-400' : 'text-gray-400' }}">
                                            {{ $member['last_seen'] }}
                                        </p>
                                    </div>
                                </div>

                                @if($member['unread_count'] > 0)
                                    <span class="inline-flex h-5 min-w-[20px] items-center justify-center rounded-full bg-emerald-600 px-1.5 text-[11px] font-bold text-white">
                                        {{ $member['unread_count'] }}
                                    </span>
                                @endif
                            </div>
                        @endforeach

                    @elseif($sidebarTab === 'channels')
                        <div class="px-1 pb-1 text-[11px] font-semibold uppercase tracking-wider text-gray-400">
                            Salas das Unidades Escolares
                        </div>

                        {{-- Mural Geral da Rede --}}
                        <div
                            wire:click="openSchoolChannel(null)"
                            class="flex cursor-pointer items-center gap-3 rounded-xl border p-3 transition {{ $chatMode === 'school_channel' && $selectedChannelSchoolId === null ? 'border-emerald-500 bg-emerald-50/50 dark:border-emerald-500 dark:bg-emerald-950/30' : 'border-gray-100 bg-white hover:bg-gray-50 dark:border-gray-700/60 dark:bg-gray-800' }}"
                        >
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-100 text-lg dark:bg-amber-900/50">
                                📢
                            </div>
                            <div>
                                <p class="text-sm font-bold text-gray-900 dark:text-white">Mural Geral da Rede</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Mensagens visíveis por todas as unidades</p>
                            </div>
                        </div>

                        @foreach($otherSchools as $school)
                            <div
                                wire:click="openSchoolChannel({{ $school->id }})"
                                class="flex cursor-pointer items-center gap-3 rounded-xl border p-3 transition {{ $chatMode === 'school_channel' && $selectedChannelSchoolId === $school->id ? 'border-emerald-500 bg-emerald-50/50 dark:border-emerald-500 dark:bg-emerald-950/30' : 'border-gray-100 bg-white hover:bg-gray-50 dark:border-gray-700/60 dark:bg-gray-800' }}"
                            >
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-lg dark:bg-indigo-900/50">
                                    🏫
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $school->getFilamentName() }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Canal direto com a secretaria da unidade</p>
                                </div>
                            </div>
                        @endforeach

                    @else
                        {{-- Filtros de Protocolos / Transferências --}}
                        <div class="mb-2 flex flex-wrap gap-1">
                            <button type="button" wire:click="setBox('inbox')" class="rounded px-2 py-1 text-[11px] font-semibold {{ $activeBox === 'inbox' ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' }}">Entrada</button>
                            <button type="button" wire:click="setBox('sent')" class="rounded px-2 py-1 text-[11px] font-semibold {{ $activeBox === 'sent' ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' }}">Enviadas</button>
                            <button type="button" wire:click="setBox('transfers')" class="rounded px-2 py-1 text-[11px] font-semibold {{ $activeBox === 'transfers' ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' }}">🔄 Transferências</button>
                        </div>

                        @forelse($messages as $msg)
                            @php
                                $isSelected = $chatMode === 'thread' && $selectedMessage?->id === $msg->id;
                                $isUnread = $msg->read_at === null && ((int) $msg->recipient_school_id === (int) $currentSchool?->id || (int) $msg->recipient_user_id === (int) auth()->id());
                            @endphp
                            <div
                                wire:click="selectMessage({{ $msg->id }})"
                                @contextmenu.prevent="openMsgMenu($event, {{ $msg->id }}, @js($msg->subject . "\n\n" . $msg->body), {{ $msg->read_at ? 'true' : 'false' }})"
                                title="Clique com o botão direito para agendar compromisso ou criar tarefa"
                                class="cursor-pointer rounded-xl border p-3 transition {{ $isSelected ? 'border-primary-500 bg-primary-50/40 dark:border-primary-500 dark:bg-primary-950/30' : 'border-gray-100 bg-white hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800' }}"
                            >
                                <div class="flex items-center justify-between gap-2">
                                    <span class="rounded bg-gray-100 px-1.5 py-0.5 text-[10px] font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                        {{ $msg->categoryLabel() }}
                                    </span>
                                    <span class="text-[11px] text-gray-400">{{ $msg->created_at?->format('d/m H:i') }}</span>
                                </div>
                                <h4 class="mt-1.5 text-xs {{ $isUnread ? 'font-bold text-gray-900 dark:text-white' : 'font-semibold text-gray-700 dark:text-gray-200' }}">
                                    {{ $msg->subject }}
                                </h4>
                                <p class="mt-0.5 line-clamp-1 text-[11px] text-gray-500 dark:text-gray-400">
                                    {{ $msg->body }}
                                </p>
                            </div>
                        @empty
                            <p class="py-8 text-center text-xs text-gray-400">Nenhum protocolo ou transferência nesta caixa.</p>
                        @endforelse
                    @endif
                </div>
            </div>

            {{-- Coluna Direita: Janela de Conversa Estilo WhatsApp ou Leitura de Protocolo --}}
            <div class="flex flex-col lg:col-span-8">
                @if($chatMode === 'direct_user' && $selectedChatUser)
                    {{-- Header do Chat 1-a-1 Estilo WhatsApp --}}
                    <div class="flex items-center justify-between border-b border-gray-200 bg-gray-50 px-6 py-3.5 dark:border-gray-700 dark:bg-gray-900/60">
                        <div class="flex items-center gap-3">
                            <div class="relative">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-200">
                                    {{ $selectedChatUser['initials'] }}
                                </div>
                                <span class="absolute bottom-0 right-0 h-2.5 w-2.5 rounded-full ring-2 ring-white dark:ring-gray-900 {{ $selectedChatUser['is_online'] ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                                    {{ $selectedChatUser['name'] }}
                                </h3>
                                <p class="text-xs {{ $selectedChatUser['is_online'] ? 'font-semibold text-emerald-600 dark:text-emerald-400' : 'text-gray-500' }}">
                                    {{ $selectedChatUser['last_seen'] }} • 🏫 {{ $selectedChatUser['schools'] }}
                                </p>
                            </div>
                        </div>
                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-medium text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300">
                            🔒 Criptografado (AES-256)
                        </span>
                    </div>

                    {{-- Balões de Mensagem Estilo WhatsApp --}}
                    <div class="flex-1 space-y-3 overflow-y-auto bg-gray-100/70 p-6 dark:bg-gray-950/40" style="max-height: 460px;">
                        <div class="mx-auto max-w-md rounded-lg bg-amber-50/90 px-3 py-1.5 text-center text-[11px] text-amber-800 shadow-xs dark:bg-amber-950/40 dark:text-amber-300">
                            🔒 As mensagens desta conversa são protegidas com criptografia AES-256.
                        </div>

                        @forelse($directChatMessages as $chatMsg)
                            @php
                                $isMine = (int) $chatMsg->sender_user_id === (int) auth()->id();
                            @endphp
                            <div class="group flex {{ $isMine ? 'justify-end' : 'justify-start' }}">
                                <div
                                    @contextmenu.prevent="openMsgMenu($event, {{ $chatMsg->id }}, @js($chatMsg->body), {{ $chatMsg->read_at ? 'true' : 'false' }})"
                                    class="relative max-w-[75%] rounded-2xl px-4 py-2.5 shadow-xs {{ $isMine ? 'rounded-br-none bg-emerald-600 text-white' : 'rounded-bl-none bg-white text-gray-900 dark:bg-gray-800 dark:text-gray-100' }}"
                                >
                                    <div class="flex items-start justify-between gap-2">
                                        <p class="whitespace-pre-line text-sm">{{ $chatMsg->body }}</p>
                                        <button
                                            type="button"
                                            @click.stop="openMsgMenu($event, {{ $chatMsg->id }}, @js($chatMsg->body), {{ $chatMsg->read_at ? 'true' : 'false' }})"
                                            title="Mais ações (Compromisso / To-Do)"
                                            class="shrink-0 rounded p-0.5 opacity-0 transition group-hover:opacity-100 {{ $isMine ? 'text-emerald-100 hover:bg-emerald-700/60 hover:text-white' : 'text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-700 dark:hover:text-gray-200' }}"
                                        >
                                            ⋮
                                        </button>
                                    </div>
                                    <div class="mt-1 flex items-center justify-end gap-1 text-[10px] {{ $isMine ? 'text-emerald-100' : 'text-gray-400' }}">
                                        <span>{{ $chatMsg->created_at?->format('d/m H:i') }}</span>
                                        @if($isMine)
                                            <span title="{{ $chatMsg->read_at ? 'Lida em ' . $chatMsg->read_at->format('d/m H:i') : 'Enviada' }}">
                                                {{ $chatMsg->read_at ? '✓✓' : '✓' }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="py-16 text-center text-xs text-gray-400">
                                Nenhuma mensagem ainda com {{ $selectedChatUser['name'] }}. Envie um "Olá" abaixo!
                            </div>
                        @endforelse
                    </div>

                    {{-- Barra Inferior de Digitação --}}
                    <form wire:submit="sendDirectMessage" class="flex items-center gap-3 border-t border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                        <input
                            type="text"
                            wire:model="directMessageText"
                            placeholder="Digite uma mensagem criptografada para {{ $selectedChatUser['name'] }}..."
                            class="flex-1 rounded-full border border-gray-300 bg-gray-50 px-4 py-2.5 text-sm text-gray-900 focus:border-emerald-500 focus:bg-white focus:outline-none dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                        />
                        <button
                            type="submit"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-emerald-600 text-white shadow-sm transition hover:bg-emerald-500"
                            title="Enviar"
                        >
                            <x-filament::icon icon="heroicon-m-paper-airplane" class="h-5 w-5" />
                        </button>
                    </form>

                @elseif($chatMode === 'school_channel')
                    {{-- Header do Canal da Escola / Mural da Rede --}}
                    <div class="flex items-center justify-between border-b border-gray-200 bg-gray-50 px-6 py-3.5 dark:border-gray-700 dark:bg-gray-900/60">
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                                {{ $selectedChannelSchool ? '🏫 Canal: ' . $selectedChannelSchool->getFilamentName() : '📢 Mural Geral de Todas as Escolas da Rede' }}
                            </h3>
                            <p class="text-xs text-gray-500">
                                Conversa rápida entre unidades • 🔒 Criptografia AES-256
                            </p>
                        </div>
                    </div>

                    <div class="flex-1 space-y-3 overflow-y-auto bg-gray-100/70 p-6 dark:bg-gray-950/40" style="max-height: 460px;">
                        @forelse($channelMessages as $cMsg)
                            @php
                                $isMine = (int) $cMsg->sender_user_id === (int) auth()->id();
                            @endphp
                            <div class="group flex {{ $isMine ? 'justify-end' : 'justify-start' }}">
                                <div
                                    @contextmenu.prevent="openMsgMenu($event, {{ $cMsg->id }}, @js($cMsg->body), {{ $cMsg->read_at ? 'true' : 'false' }})"
                                    class="relative max-w-[75%] rounded-2xl px-4 py-2.5 shadow-xs {{ $isMine ? 'rounded-br-none bg-emerald-600 text-white' : 'rounded-bl-none bg-white text-gray-900 dark:bg-gray-800 dark:text-gray-100' }}"
                                >
                                    <div class="mb-1 flex items-center justify-between gap-2 text-[11px] font-bold {{ $isMine ? 'text-emerald-100' : 'text-indigo-600 dark:text-indigo-400' }}">
                                        <span>{{ $cMsg->sender?->getFilamentName() ?? 'Equipe' }} ({{ $cMsg->senderSchool?->getFilamentName() ?? 'Rede' }})</span>
                                        <button
                                            type="button"
                                            @click.stop="openMsgMenu($event, {{ $cMsg->id }}, @js($cMsg->body), {{ $cMsg->read_at ? 'true' : 'false' }})"
                                            title="Mais ações (Compromisso / To-Do)"
                                            class="shrink-0 rounded px-1 opacity-0 transition group-hover:opacity-100 {{ $isMine ? 'text-emerald-100 hover:bg-emerald-700/60 hover:text-white' : 'text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-700 dark:hover:text-gray-200' }}"
                                        >
                                            ⋮
                                        </button>
                                    </div>
                                    <p class="whitespace-pre-line text-sm">{{ $cMsg->body }}</p>
                                    <div class="mt-1 text-right text-[10px] {{ $isMine ? 'text-emerald-100' : 'text-gray-400' }}">
                                        {{ $cMsg->created_at?->format('d/m H:i') }}
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="py-16 text-center text-xs text-gray-400">
                                Nenhuma mensagem neste canal ainda.
                            </div>
                        @endforelse
                    </div>

                    <form wire:submit="sendDirectMessage" class="flex items-center gap-3 border-t border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                        <input
                            type="text"
                            wire:model="directMessageText"
                            placeholder="Escreva uma mensagem para o canal..."
                            class="flex-1 rounded-full border border-gray-300 bg-gray-50 px-4 py-2.5 text-sm text-gray-900 focus:border-emerald-500 focus:bg-white focus:outline-none dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                        />
                        <button
                            type="submit"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-emerald-600 text-white shadow-sm transition hover:bg-emerald-500"
                        >
                            <x-filament::icon icon="heroicon-m-paper-airplane" class="h-5 w-5" />
                        </button>
                    </form>

                @elseif($selectedMessage)
                    {{-- Visualização de Protocolo / Transferência / Thread --}}
                    <div
                        class="flex-1 p-6"
                        @contextmenu.prevent="openMsgMenu($event, {{ $selectedMessage->id }}, @js($selectedMessage->subject . "\n\n" . $selectedMessage->body), {{ $selectedMessage->read_at ? 'true' : 'false' }})"
                    >
                        <div class="border-b border-gray-200 pb-4 dark:border-gray-700">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="inline-flex items-center rounded-md bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                                    {{ $selectedMessage->categoryLabel() }}
                                </span>
                                <div class="flex flex-wrap items-center gap-2">
                                    <button
                                        type="button"
                                        wire:click="openAppointmentFromMessage({{ $selectedMessage->id }})"
                                        class="inline-flex items-center gap-1 rounded-md border border-gray-200 bg-white px-2.5 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                                    >
                                        📅 Virar Compromisso
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="openTaskFromMessage({{ $selectedMessage->id }})"
                                        class="inline-flex items-center gap-1 rounded-md border border-gray-200 bg-white px-2.5 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                                    >
                                        📋 Adicionar ao To-Do
                                    </button>
                                    <span class="text-xs text-gray-400">
                                        🔒 AES-256 • {{ $selectedMessage->created_at?->format('d/m/Y \à\s H:i') }}
                                    </span>
                                </div>
                            </div>

                            <h3 class="mt-3 text-lg font-bold text-gray-900 dark:text-white">
                                {{ $selectedMessage->subject }}
                            </h3>

                            <div class="mt-2 flex flex-wrap items-center gap-4 text-xs text-gray-600 dark:text-gray-300">
                                <div>
                                    <span class="font-semibold">De:</span>
                                    {{ $selectedMessage->senderSchool?->getFilamentName() ?? 'Diretoria da Rede' }}
                                    @if($selectedMessage->sender)
                                        ({{ $selectedMessage->sender->getFilamentName() }})
                                    @endif
                                </div>
                                <div>
                                    <span class="font-semibold">Para:</span>
                                    {{ $selectedMessage->recipientSchool?->getFilamentName() ?? '📢 Todas as Escolas da Rede' }}
                                </div>
                            </div>

                            @if($selectedMessage->contract)
                                <div class="mt-3 flex items-center justify-between rounded-lg border border-amber-200 bg-amber-50/70 px-3 py-2 text-xs text-amber-900 dark:border-amber-800/60 dark:bg-amber-950/30 dark:text-amber-200">
                                    <div>
                                        🎓 <strong>Aluno / Contrato Vinculado:</strong>
                                        #{{ $selectedMessage->contract->contract_number }} — {{ $selectedMessage->contract->student?->name ?? 'Aluno' }}
                                    </div>
                                    <a
                                        href="{{ url('/admin/' . ($selectedMessage->contract->school_id ?? $currentSchool?->id) . '/contracts/' . $selectedMessage->contract->id . '/edit') }}"
                                        class="font-bold underline hover:text-amber-700"
                                    >
                                        Abrir Contrato →
                                    </a>
                                </div>
                            @endif
                        </div>

                        <div class="prose prose-sm dark:prose-invert mt-4 max-w-none whitespace-pre-line text-sm text-gray-800 dark:text-gray-200">
                            {{ $selectedMessage->body }}
                        </div>

                        @if($selectedMessage->replies->isNotEmpty())
                            <div class="mt-6 space-y-3 border-t border-gray-200 pt-4 dark:border-gray-700">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Histórico de Respostas ({{ $selectedMessage->replies->count() }})
                                </h4>
                                @foreach($selectedMessage->replies as $reply)
                                    <div
                                        @contextmenu.prevent.stop="openMsgMenu($event, {{ $reply->id }}, @js($reply->body), {{ $reply->read_at ? 'true' : 'false' }})"
                                        class="group rounded-lg border border-gray-100 bg-gray-50 p-3.5 dark:border-gray-700 dark:bg-gray-900/50"
                                    >
                                        <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                                            <span class="font-bold text-gray-800 dark:text-gray-200">
                                                🏫 {{ $reply->senderSchool?->getFilamentName() ?? 'Rede' }}
                                                @if($reply->sender)
                                                    — {{ $reply->sender->getFilamentName() }}
                                                @endif
                                            </span>
                                            <div class="flex items-center gap-2">
                                                <span>{{ $reply->created_at?->format('d/m/Y H:i') }}</span>
                                                <button
                                                    type="button"
                                                    @click.stop="openMsgMenu($event, {{ $reply->id }}, @js($reply->body), {{ $reply->read_at ? 'true' : 'false' }})"
                                                    title="Mais ações"
                                                    class="rounded px-1 text-gray-400 opacity-0 transition hover:bg-gray-200 hover:text-gray-700 group-hover:opacity-100 dark:hover:bg-gray-700 dark:hover:text-gray-200"
                                                >
                                                    ⋮
                                                </button>
                                            </div>
                                        </div>
                                        <div class="mt-2 whitespace-pre-line text-sm text-gray-700 dark:text-gray-300">
                                            {{ $reply->body }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <form wire:submit="sendReply" class="mt-6 border-t border-gray-200 pt-4 dark:border-gray-700">
                            <label class="mb-1.5 block text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">
                                Responder neste protocolo
                            </label>
                            <textarea
                                wire:model="replyBody"
                                rows="3"
                                placeholder="Digite sua resposta criptografada..."
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                            ></textarea>
                            <div class="mt-2 flex justify-end">
                                <button
                                    type="submit"
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-primary-500"
                                >
                                    <x-filament::icon icon="heroicon-m-paper-airplane" class="h-4 w-4" />
                                    Enviar Resposta
                                </button>
                            </div>
                        </form>
                    </div>
                @else
                    <div class="flex flex-1 items-center justify-center p-8 text-center">
                        <div>
                            <x-filament::icon icon="heroicon-o-chat-bubble-left-right" class="mx-auto h-12 w-12 text-emerald-500" />
                            <h3 class="mt-3 text-sm font-bold text-gray-800 dark:text-gray-200">
                                Chat Criptografado da Rede Aurosoft
                            </h3>
                            <p class="mt-1 max-w-sm text-xs text-gray-500 dark:text-gray-400">
                                Clique em um colaborador ao lado (🟢 Online) para iniciar uma conversa direta estilo WhatsApp, escolha uma escola em "🏫 Escolas", ou acompanhe as transferências em "🔄 Protocolos".
                            </p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Menu de Contexto (Botão Direito na Mensagem) --}}
        <div
            x-cloak
            x-show="ctxMenu.open"
            x-transition.opacity.duration.100ms
            @click.outside="closeMsgMenu()"
            :style="`position: fixed; left: ${ctxMenu.x}px; top: ${ctxMenu.y}px; z-index: 9999;`"
            class="w-68 overflow-hidden rounded-xl border border-gray-200 bg-white py-1.5 shadow-xl ring-1 ring-black/5 dark:border-gray-700 dark:bg-gray-800"
        >
            <div class="border-b border-gray-100 px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:border-gray-700">
                Ações da Mensagem
            </div>

            <button
                type="button"
                @click="$wire.openAppointmentFromMessage(ctxMenu.messageId); closeMsgMenu();"
                class="flex w-full items-center gap-2.5 px-3.5 py-2 text-left text-xs font-medium text-gray-700 hover:bg-emerald-50 hover:text-emerald-700 dark:text-gray-200 dark:hover:bg-emerald-950/40 dark:hover:text-emerald-300"
            >
                <span>📅</span>
                <span>Cadastrar como Compromisso (Agenda)</span>
            </button>

            <button
                type="button"
                @click="$wire.openTaskFromMessage(ctxMenu.messageId); closeMsgMenu();"
                class="flex w-full items-center gap-2.5 px-3.5 py-2 text-left text-xs font-medium text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 dark:text-gray-200 dark:hover:bg-indigo-950/40 dark:hover:text-indigo-300"
            >
                <span>📋</span>
                <span>Adicionar ao Meu To-Do (Tarefas)</span>
            </button>

            <div class="my-1 border-t border-gray-100 dark:border-gray-700"></div>

            <button
                type="button"
                @click="$wire.quoteMessage(ctxMenu.messageId); closeMsgMenu();"
                class="flex w-full items-center gap-2.5 px-3.5 py-2 text-left text-xs text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700/60"
            >
                <span>💬</span>
                <span>Responder / Citar mensagem</span>
            </button>

            <button
                type="button"
                @click="$wire.toggleMessageRead(ctxMenu.messageId); closeMsgMenu();"
                class="flex w-full items-center gap-2.5 px-3.5 py-2 text-left text-xs text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700/60"
            >
                <span>✉️</span>
                <span x-text="ctxMenu.isRead ? 'Marcar como não lida' : 'Marcar como lida'"></span>
            </button>

            <button
                type="button"
                @click="copyMessageText()"
                class="flex w-full items-center gap-2.5 px-3.5 py-2 text-left text-xs text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700/60"
            >
                <span>📄</span>
                <span>Copiar texto da mensagem</span>
            </button>
        </div>

        {{-- Toast rápido de cópia --}}
        <div
            x-cloak
            x-show="copiedToast"
            x-transition
            class="fixed bottom-5 right-5 z-50 rounded-lg bg-gray-900 px-4 py-2 text-xs font-semibold text-white shadow-lg dark:bg-emerald-600"
        >
            ✓ Texto da mensagem copiado!
        </div>

        {{-- Modal: Cadastrar Mensagem como Compromisso (Minha Agenda) --}}
        @if($showAppointmentModal)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/60 p-4 backdrop-blur-xs">
                <div class="w-full max-w-xl rounded-2xl border border-gray-200 bg-white p-6 shadow-2xl dark:border-gray-700 dark:bg-gray-800">
                    <div class="mb-4 flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-700">
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">
                                📅 Cadastrar Mensagem como Compromisso
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Este item será salvo em <strong>Minha Agenda & Compromissos</strong> e gerará lembretes no sistema.
                            </p>
                        </div>
                        <button
                            type="button"
                            wire:click="$set('showAppointmentModal', false)"
                            class="text-sm font-semibold text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                        >
                            ✕
                        </button>
                    </div>

                    <form wire:submit="saveAppointmentFromMessage" class="space-y-4">
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">
                                Título do Compromisso
                            </label>
                            <input
                                type="text"
                                wire:model="apptTitle"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                            />
                            @error('apptTitle')
                                <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">
                                    Data
                                </label>
                                <input
                                    type="date"
                                    wire:model="apptDate"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                                />
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">
                                    Início
                                </label>
                                <input
                                    type="time"
                                    wire:model="apptStartTime"
                                    @disabled($apptAllDay)
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 disabled:opacity-50 dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                                />
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">
                                    Término
                                </label>
                                <input
                                    type="time"
                                    wire:model="apptEndTime"
                                    @disabled($apptAllDay)
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 disabled:opacity-50 dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">
                                    Tipo
                                </label>
                                <select
                                    wire:model="apptType"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                                >
                                    <option value="geral">Compromisso Geral</option>
                                    <option value="atendimento">Atendimento / Financeiro</option>
                                    <option value="reuniao_equipe">Reunião de Equipe</option>
                                    <option value="reuniao_pais">Reunião com Pais / Aluno</option>
                                    <option value="cancelamento">Entrevista de Cancelamento</option>
                                    <option value="evento_escola">Evento da Escola</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">
                                    Prioridade
                                </label>
                                <select
                                    wire:model="apptPriority"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                                >
                                    <option value="baixa">Baixa</option>
                                    <option value="media">Média</option>
                                    <option value="alta">Alta</option>
                                    <option value="urgente">Urgente</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">
                                    Visibilidade
                                </label>
                                <select
                                    wire:model="apptScope"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                                >
                                    <option value="current">🏫 Somente nesta unidade</option>
                                    <option value="global">🌐 Pessoal (Todas as unidades)</option>
                                </select>
                            </div>
                        </div>

                        <label class="inline-flex items-center gap-2 text-xs text-gray-700 dark:text-gray-300">
                            <input type="checkbox" wire:model.live="apptAllDay" class="rounded border-gray-300 text-primary-600" />
                            <span>Compromisso de dia inteiro</span>
                        </label>

                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">
                                Detalhes / Conteúdo da Mensagem
                            </label>
                            <textarea
                                wire:model="apptDescription"
                                rows="4"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                            ></textarea>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-2">
                            <button
                                type="button"
                                wire:click="$set('showAppointmentModal', false)"
                                class="rounded-lg border border-gray-300 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                            >
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                class="rounded-lg bg-emerald-600 px-5 py-2 text-xs font-bold text-white shadow-sm hover:bg-emerald-500"
                            >
                                Salvar na Minha Agenda
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        {{-- Modal: Adicionar Mensagem ao To-Do (Quadro de Tarefas) --}}
        @if($showTaskModal)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/60 p-4 backdrop-blur-xs">
                <div class="w-full max-w-xl rounded-2xl border border-gray-200 bg-white p-6 shadow-2xl dark:border-gray-700 dark:bg-gray-800">
                    <div class="mb-4 flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-700">
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">
                                📋 Adicionar Mensagem ao Meu To-Do (Quadro de Tarefas)
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Cria um cartão atribuído a você no Quadro de Tarefas com alerta na data de entrega.
                            </p>
                        </div>
                        <button
                            type="button"
                            wire:click="$set('showTaskModal', false)"
                            class="text-sm font-semibold text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                        >
                            ✕
                        </button>
                    </div>

                    <form wire:submit="saveTaskFromMessage" class="space-y-4">
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">
                                Título da Tarefa (To-Do)
                            </label>
                            <input
                                type="text"
                                wire:model="taskTitle"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                            />
                            @error('taskTitle')
                                <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">
                                    Quadro de Destino
                                </label>
                                <select
                                    wire:model.live="taskBoardId"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                                >
                                    @foreach($availableTaskBoards as $board)
                                        <option value="{{ $board->id }}">
                                            {{ $board->school_id ? '🏫 ' : '🌐 ' }}{{ $board->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">
                                    Coluna
                                </label>
                                <select
                                    wire:model="taskColumnId"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                                >
                                    @foreach($availableTaskColumns as $col)
                                        <option value="{{ $col->id }}">{{ $col->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">
                                    Prazo / Data para Realizar
                                </label>
                                <input
                                    type="date"
                                    wire:model="taskDueDate"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                                />
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">
                                    Prioridade
                                </label>
                                <select
                                    wire:model="taskPriority"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                                >
                                    <option value="low">Baixa</option>
                                    <option value="medium">Média</option>
                                    <option value="high">Alta</option>
                                    <option value="urgent">Urgente</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">
                                Descrição
                            </label>
                            <textarea
                                wire:model="taskDescription"
                                rows="4"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                            ></textarea>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-2">
                            <button
                                type="button"
                                wire:click="$set('showTaskModal', false)"
                                class="rounded-lg border border-gray-300 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                            >
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                class="rounded-lg bg-primary-600 px-5 py-2 text-xs font-bold text-white shadow-sm hover:bg-primary-500"
                            >
                                Salvar no Meu To-Do
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>

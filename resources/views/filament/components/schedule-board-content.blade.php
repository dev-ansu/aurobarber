<div
    x-data="{
        selectedRoom: @js($classroomId ? (string) $classroomId : ''),
        boards: @js($boards ?? ['all' => $board]),
        getCell(hourIndex, weekday) {
            const key = this.selectedRoom ? String(this.selectedRoom) : 'all';
            const board = this.boards[key] || this.boards['all'] || [];
            if (!board[hourIndex] || !board[hourIndex].cells || !board[hourIndex].cells[weekday]) {
                return { occupied: 0, total: 0, closed: true, ratio: 0 };
            }
            return board[hourIndex].cells[weekday];
        }
    }"
    class="space-y-6"
>
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="text-xl font-bold text-gray-950 dark:text-white">Quadro de horários</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Consulte a ocupação das salas por dia e horário.
            </p>
        </div>

        <div class="flex items-center gap-4">
            <select
                x-model="selectedRoom"
                wire:model.live="classroomId"
                aria-label="Filtrar por sala"
                class="fi-select-input w-full max-w-64 rounded-xl"
            >
                <option value="">Todas as salas</option>
                @foreach ($classrooms as $classroom)
                    <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="flex flex-wrap gap-3">
        <div class="flex items-center gap-2 rounded-full bg-success-500/10 px-3 py-2">
            <span class="h-2.5 w-2.5 rounded-full bg-success-500"></span>
            <span class="text-sm font-medium text-success-700 dark:text-success-300">Disponível</span>
        </div>
        <div class="flex items-center gap-2 rounded-full bg-warning-500/10 px-3 py-2">
            <span class="h-2.5 w-2.5 rounded-full bg-warning-500"></span>
            <span class="text-sm font-medium text-warning-700 dark:text-warning-300">Quase cheio</span>
        </div>
        <div class="flex items-center gap-2 rounded-full bg-danger-500/10 px-3 py-2">
            <span class="h-2.5 w-2.5 rounded-full bg-danger-500"></span>
            <span class="text-sm font-medium text-danger-700 dark:text-danger-300">Lotado</span>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-200 shadow-sm dark:border-gray-800">
        <div class="max-h-[65vh] overflow-auto">
            <table class="min-w-full border-separate border-spacing-0">
                <thead class="sticky top-0 z-20 bg-gray-100 dark:bg-gray-900">
                    <tr>
                        <th scope="col" class="sticky left-0 z-30 bg-gray-100 px-5 py-4 text-left text-sm font-bold uppercase tracking-wide text-gray-700 dark:bg-gray-900 dark:text-gray-200">
                            Horário
                        </th>
                        @foreach ([
                            1 => 'Segunda',
                            2 => 'Terça',
                            3 => 'Quarta',
                            4 => 'Quinta',
                            5 => 'Sexta',
                            6 => 'Sábado',
                            0 => 'Domingo',
                        ] as $weekday => $label)
                            <th scope="col" class="min-w-32 px-4 py-4 text-center text-sm font-bold uppercase tracking-wide text-gray-700 dark:text-gray-200">
                                {{ \Illuminate\Support\Str::substr($label, 0, 3) }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($board as $hourIndex => $row)
                        <tr wire:key="schedule-row-{{ $row['label'] }}" class="transition-colors odd:bg-white even:bg-gray-50 hover:bg-primary-50/40 dark:odd:bg-gray-900 dark:even:bg-gray-950 dark:hover:bg-primary-500/5">
                            <th scope="row" class="sticky left-0 z-10 whitespace-nowrap bg-inherit px-5 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $row['label'] }}
                            </th>
                            @foreach ([1, 2, 3, 4, 5, 6, 0] as $weekday)
                                @php
                                    $cell = $row['cells'][$weekday] ?? ['occupied' => 0, 'total' => 0, 'closed' => true, 'ratio' => 0];
                                    $closed = $cell['closed'];
                                    $ratio = $cell['ratio'] ?? ($cell['total'] > 0 ? (int) round(($cell['occupied'] / $cell['total']) * 100) : 0);
                                    $isFull = $cell['total'] > 0 && $cell['occupied'] >= $cell['total'];
                                    $isAlmost = ! $isFull && $ratio >= 75;
                                    $bar = $isFull ? 'bg-danger-500' : ($isAlmost ? 'bg-warning-500' : 'bg-success-500');
                                    $text = $isFull ? 'text-danger-700 dark:text-danger-400' : ($isAlmost ? 'text-warning-700 dark:text-warning-400' : 'text-success-700 dark:text-success-400');
                                    $card = $isFull ? 'bg-danger-50 dark:bg-danger-500/10' : ($isAlmost ? 'bg-warning-50 dark:bg-warning-500/10' : 'bg-success-50 dark:bg-success-500/10');
                                @endphp
                                <td wire:key="schedule-cell-{{ $row['label'] }}-{{ $weekday }}" class="min-w-32 p-2 align-middle">
                                    <div
                                        x-show="getCell({{ $hourIndex }}, {{ $weekday }}).closed"
                                        class="rounded-xl bg-gray-100 p-3 text-center text-sm font-medium text-gray-500 ring-1 ring-black/5 dark:bg-gray-800 dark:text-gray-400 dark:ring-white/5"
                                        @if (! $closed) style="display: none;" @endif
                                    >
                                        Fechado
                                    </div>
                                    <div
                                        x-show="! getCell({{ $hourIndex }}, {{ $weekday }}).closed"
                                        class="rounded-xl p-3 shadow-sm ring-1 ring-black/5 dark:ring-white/5 {{ $card }}"
                                        :class="{
                                            'bg-danger-50 dark:bg-danger-500/10': getCell({{ $hourIndex }}, {{ $weekday }}).total > 0 && getCell({{ $hourIndex }}, {{ $weekday }}).occupied >= getCell({{ $hourIndex }}, {{ $weekday }}).total,
                                            'bg-warning-50 dark:bg-warning-500/10': getCell({{ $hourIndex }}, {{ $weekday }}).total > 0 && getCell({{ $hourIndex }}, {{ $weekday }}).occupied < getCell({{ $hourIndex }}, {{ $weekday }}).total && getCell({{ $hourIndex }}, {{ $weekday }}).ratio >= 75,
                                            'bg-success-50 dark:bg-success-500/10': getCell({{ $hourIndex }}, {{ $weekday }}).total > 0 && getCell({{ $hourIndex }}, {{ $weekday }}).occupied < getCell({{ $hourIndex }}, {{ $weekday }}).total && getCell({{ $hourIndex }}, {{ $weekday }}).ratio < 75
                                        }"
                                        @if ($closed) style="display: none;" @endif
                                    >
                                        <div class="mb-2 flex items-center justify-between gap-2">
                                            <span
                                                class="text-xs font-semibold {{ $text }}"
                                                :class="{
                                                    'text-danger-700 dark:text-danger-400': getCell({{ $hourIndex }}, {{ $weekday }}).total > 0 && getCell({{ $hourIndex }}, {{ $weekday }}).occupied >= getCell({{ $hourIndex }}, {{ $weekday }}).total,
                                                    'text-warning-700 dark:text-warning-400': getCell({{ $hourIndex }}, {{ $weekday }}).total > 0 && getCell({{ $hourIndex }}, {{ $weekday }}).occupied < getCell({{ $hourIndex }}, {{ $weekday }}).total && getCell({{ $hourIndex }}, {{ $weekday }}).ratio >= 75,
                                                    'text-success-700 dark:text-success-400': getCell({{ $hourIndex }}, {{ $weekday }}).total > 0 && getCell({{ $hourIndex }}, {{ $weekday }}).occupied < getCell({{ $hourIndex }}, {{ $weekday }}).total && getCell({{ $hourIndex }}, {{ $weekday }}).ratio < 75
                                                }"
                                                x-text="getCell({{ $hourIndex }}, {{ $weekday }}).ratio + '%'"
                                            >{{ $ratio }}%</span>
                                            <span
                                                class="text-sm font-bold {{ $text }}"
                                                :class="{
                                                    'text-danger-700 dark:text-danger-400': getCell({{ $hourIndex }}, {{ $weekday }}).total > 0 && getCell({{ $hourIndex }}, {{ $weekday }}).occupied >= getCell({{ $hourIndex }}, {{ $weekday }}).total,
                                                    'text-warning-700 dark:text-warning-400': getCell({{ $hourIndex }}, {{ $weekday }}).total > 0 && getCell({{ $hourIndex }}, {{ $weekday }}).occupied < getCell({{ $hourIndex }}, {{ $weekday }}).total && getCell({{ $hourIndex }}, {{ $weekday }}).ratio >= 75,
                                                    'text-success-700 dark:text-success-400': getCell({{ $hourIndex }}, {{ $weekday }}).total > 0 && getCell({{ $hourIndex }}, {{ $weekday }}).occupied < getCell({{ $hourIndex }}, {{ $weekday }}).total && getCell({{ $hourIndex }}, {{ $weekday }}).ratio < 75
                                                }"
                                                x-text="getCell({{ $hourIndex }}, {{ $weekday }}).occupied + '/' + getCell({{ $hourIndex }}, {{ $weekday }}).total"
                                            >{{ $cell['occupied'] }}/{{ $cell['total'] }}</span>
                                        </div>
                                        <div class="h-2 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                            <div
                                                class="h-full rounded-full transition-all duration-300 {{ $bar }}"
                                                :class="{
                                                    'bg-danger-500': getCell({{ $hourIndex }}, {{ $weekday }}).total > 0 && getCell({{ $hourIndex }}, {{ $weekday }}).occupied >= getCell({{ $hourIndex }}, {{ $weekday }}).total,
                                                    'bg-warning-500': getCell({{ $hourIndex }}, {{ $weekday }}).total > 0 && getCell({{ $hourIndex }}, {{ $weekday }}).occupied < getCell({{ $hourIndex }}, {{ $weekday }}).total && getCell({{ $hourIndex }}, {{ $weekday }}).ratio >= 75,
                                                    'bg-success-500': getCell({{ $hourIndex }}, {{ $weekday }}).total > 0 && getCell({{ $hourIndex }}, {{ $weekday }}).occupied < getCell({{ $hourIndex }}, {{ $weekday }}).total && getCell({{ $hourIndex }}, {{ $weekday }}).ratio < 75
                                                }"
                                                :style="'width: ' + getCell({{ $hourIndex }}, {{ $weekday }}).ratio + '%'"
                                                style="width: {{ $ratio }}%"
                                            ></div>
                                        </div>
                                    </div>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

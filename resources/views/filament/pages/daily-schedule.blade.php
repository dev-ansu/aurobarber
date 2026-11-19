<x-filament-panels::page>
    @php
        $carbonDate = \Carbon\Carbon::parse($selectedDate)->locale('pt_BR');
        $dateFormatted = $carbonDate->translatedFormat('l, d \\d\\e F \\d\\e Y');
        $isToday = $selectedDate === now()->format('Y-m-d');
        $stats = $this->dayStats;
        $professionals = $this->professionals;
        $byBarber = $this->appointmentsByBarber;
        $isRestrictedBarber = auth()->user()?->isRestrictedBarber();
    @endphp

    <div class="space-y-6">
        <x-filament::section>
            <div class="flex flex-col gap-6">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <x-filament::icon-button
                            icon="heroicon-o-chevron-left"
                            color="gray"
                            label="Dia anterior"
                            wire:click="previousDay"
                        />
                        <x-filament::button
                            :color="$isToday ? 'warning' : 'gray'"
                            wire:click="setToday"
                        >
                            Hoje
                        </x-filament::button>
                        <x-filament::icon-button
                            icon="heroicon-o-chevron-right"
                            color="gray"
                            label="Próximo dia"
                            wire:click="nextDay"
                        />
                        <input
                            type="date"
                            wire:model.live="selectedDate"
                            aria-label="Selecionar data da agenda"
                            class="fi-input block rounded-lg border-gray-300 bg-white text-gray-950 shadow-sm outline-none transition duration-75 focus:border-primary-500 focus:ring-1 focus:ring-inset focus:ring-primary-500 disabled:text-gray-500 dark:border-white/10 dark:bg-white/5 dark:text-white dark:focus:border-primary-500 sm:text-sm"
                        />
                        <span class="text-sm font-medium capitalize text-gray-600 dark:text-gray-300">{{ $dateFormatted }}</span>
                    </div>

                    @if (! $isRestrictedBarber)
                        <x-filament::button
                            icon="heroicon-o-plus"
                            color="warning"
                            wire:click="mountAction('quickAppointment')"
                        >
                            Novo encaixe
                        </x-filament::button>
                    @endif
                </div>

                <div class="grid grid-cols-2 gap-3 border-t border-gray-200 pt-5 dark:border-white/10 md:grid-cols-4">
                    <div class="rounded-xl bg-gray-50 p-4 dark:bg-white/5">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Total agendado</p>
                        <p class="mt-1 text-xl font-semibold text-gray-950 dark:text-white">{{ $stats['total_count'] }} <span class="text-sm font-normal text-gray-500">clientes</span></p>
                    </div>
                    <div class="rounded-xl bg-gray-50 p-4 dark:bg-white/5">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Em atendimento</p>
                        <p class="mt-1 text-xl font-semibold text-warning-600 dark:text-warning-400">{{ $stats['in_progress_count'] }}</p>
                    </div>
                    <div class="rounded-xl bg-gray-50 p-4 dark:bg-white/5">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Concluídos</p>
                        <p class="mt-1 text-xl font-semibold text-success-600 dark:text-success-400">{{ $stats['completed_count'] }}</p>
                    </div>
                    <div class="rounded-xl bg-gray-50 p-4 dark:bg-white/5">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Faturamento previsto</p>
                        <p class="mt-1 text-xl font-semibold text-gray-950 dark:text-white">R$ {{ number_format($stats['total_revenue'], 2, ',', '.') }}</p>
                    </div>
                </div>
            </div>
        </x-filament::section>

        @if ($professionals->isEmpty())
            <x-filament::section>
                <div class="py-10 text-center">
                    <x-filament::icon icon="heroicon-o-calendar-days" class="mx-auto h-8 w-8 text-gray-400" />
                    <p class="mt-3 text-sm font-medium text-gray-600 dark:text-gray-300">
                        {{ $isRestrictedBarber ? 'Não foi encontrado um perfil profissional vinculado à sua conta.' : 'Nenhum barbeiro ativo cadastrado nesta unidade.' }}
                    </p>
                    @if (! $isRestrictedBarber)
                        <a
                            href="{{ url('/admin/' . filament()->getTenant()->slug . '/professionals/create') }}"
                            class="mt-3 inline-block text-sm font-semibold text-primary-600 hover:text-primary-500 dark:text-primary-400"
                        >
                            Cadastrar primeiro barbeiro
                        </a>
                    @endif
                </div>
            </x-filament::section>
        @else
            <div @class([
                'grid items-start gap-5',
                'grid-cols-1' => $isRestrictedBarber,
                'grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4' => ! $isRestrictedBarber,
            ])>
                @foreach ($professionals as $pro)
                    @php
                        $appts = $byBarber[$pro->id] ?? collect();
                        $barberRevenue = $appts->whereNotIn('status', ['cancelled', 'no_show'])->sum('service_price');
                    @endphp

                    <x-filament::section>
                        <x-slot name="heading">{{ $pro->display_name }}</x-slot>
                        <x-slot name="description">
                            {{ $appts->count() }} atendimento(s) · R$ {{ number_format($barberRevenue, 2, ',', '.') }}
                        </x-slot>
                        <x-slot name="afterHeader">
                            @if (! $isRestrictedBarber)
                                <x-filament::icon-button
                                    icon="heroicon-o-plus"
                                    color="warning"
                                    label="Encaixar cliente para {{ $pro->display_name }}"
                                    wire:click="mountAction('quickAppointment', @js(['professionalId' => $pro->id]))"
                                />
                            @endif
                        </x-slot>

                        <div class="space-y-3">
                            @forelse ($appts as $appt)
                                <article class="space-y-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-950/40">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="font-semibold text-gray-950 dark:text-white">
                                            {{ $appt->scheduled_at->format('H:i') }}
                                            <span class="font-normal text-gray-500 dark:text-gray-400">até {{ $appt->ends_at->format('H:i') }}</span>
                                        </div>
                                        <x-filament::badge :color="$appt->status_color">
                                            {{ $appt->status_label }}
                                        </x-filament::badge>
                                    </div>

                                    <div class="flex items-center justify-between gap-3">
                                        <p class="truncate font-semibold text-gray-800 dark:text-gray-100">{{ $appt->client_name }}</p>
                                        @if ($appt->client_phone)
                                            @if (app(\App\Services\SensitiveClientDataAccess::class)->canView())
                                                @php
                                                    app(\App\Services\SensitiveClientDataAccess::class)->record($appt, ['client_phone']);
                                                    $cleanPhone = preg_replace('/\\D/', '', $appt->client_phone);
                                                    $phone55 = str_starts_with($cleanPhone, '55') ? $cleanPhone : '55' . $cleanPhone;
                                                @endphp
                                                <a
                                                    href="https://api.whatsapp.com/send?phone={{ $phone55 }}"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    class="text-sm font-medium text-success-600 hover:text-success-500 dark:text-success-400"
                                                    title="Conversar no WhatsApp"
                                                >
                                                    WhatsApp
                                                </a>
                                            @else
                                                <span class="text-xs text-gray-500">{{ \App\Services\SensitiveClientData::maskPhone($appt->client_phone) }}</span>
                                            @endif
                                        @endif
                                    </div>

                                    <div class="flex items-center justify-between gap-3 border-t border-gray-100 pt-3 text-sm dark:border-white/10">
                                        <span class="truncate text-gray-500 dark:text-gray-400" title="{{ $appt->services_summary }}">{{ $appt->services_summary }}</span>
                                        <span class="shrink-0 font-semibold text-gray-800 dark:text-gray-100">R$ {{ number_format($appt->service_price, 2, ',', '.') }}</span>
                                    </div>

                                    @if (! in_array($appt->status, ['completed', 'cancelled', 'no_show'], true))
                                        <div class="flex flex-wrap gap-2 border-t border-gray-100 pt-3 dark:border-white/10">
                                            @if ($appt->status !== 'in_progress')
                                                <x-filament::button size="xs" color="info" wire:click="updateStatus({{ $appt->id }}, 'in_progress')">
                                                    Atender
                                                </x-filament::button>
                                            @else
                                                <x-filament::badge color="info">Em atendimento</x-filament::badge>
                                            @endif
                                            <x-filament::button size="xs" color="success" icon="heroicon-o-check" wire:click="mountAction('completeAppointment', @js(['appointmentId' => $appt->id]))">
                                                Concluir
                                            </x-filament::button>
                                            <x-filament::button size="xs" color="gray" wire:click="updateStatus({{ $appt->id }}, 'no_show')">
                                                Faltou
                                            </x-filament::button>
                                            <x-filament::button size="xs" color="gray" outlined wire:click="mountAction('editAppointment', @js(['appointmentId' => $appt->id]))">
                                                Editar / reagendar
                                            </x-filament::button>
                                            <x-filament::button size="xs" color="danger" outlined wire:click="mountAction('cancelAppointment', @js(['appointmentId' => $appt->id]))">
                                                Cancelar
                                            </x-filament::button>
                                        </div>
                                    @endif
                                </article>
                            @empty
                                <div class="rounded-xl border border-dashed border-gray-300 px-4 py-8 text-center dark:border-white/15">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Sem agendamentos para este barbeiro.</p>
                                    @if (! $isRestrictedBarber)
                                        <x-filament::button
                                            class="mt-3"
                                            size="sm"
                                            color="warning"
                                            icon="heroicon-o-plus"
                                            wire:click="mountAction('quickAppointment', @js(['professionalId' => $pro->id]))"
                                        >
                                            Encaixar cliente
                                        </x-filament::button>
                                    @endif
                                </div>
                            @endforelse
                        </div>
                    </x-filament::section>
                @endforeach
            </div>
        @endif
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>

<?php

namespace App\Filament\Pages;

use App\Models\Appointment;
use App\Models\Barbershop;
use App\Models\Product;
use App\Services\CheckoutService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

class DailySchedule extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-view-columns';

    protected static string|\UnitEnum|null $navigationGroup = 'Atendimento';

    protected static ?string $navigationLabel = 'Agenda Diária';

    protected static ?string $title = 'Agenda Diária da Equipe';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.daily-schedule';

    public function getTitle(): string|Htmlable
    {
        return auth()->user()?->isRestrictedBarber()
            ? 'Minha Agenda Diária'
            : 'Agenda Diária da Equipe';
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return 'full';
    }

    public string $selectedDate = '';

    public function mount(): void
    {
        $this->selectedDate = now()->format('Y-m-d');
    }

    public function setDate(string $date): void
    {
        $this->selectedDate = $date;
    }

    public function previousDay(): void
    {
        $this->selectedDate = Carbon::parse($this->selectedDate)->subDay()->format('Y-m-d');
    }

    public function nextDay(): void
    {
        $this->selectedDate = Carbon::parse($this->selectedDate)->addDay()->format('Y-m-d');
    }

    public function setToday(): void
    {
        $this->selectedDate = now()->format('Y-m-d');
    }

    public function getProfessionalsProperty(): Collection
    {
        $tenant = filament()->getTenant();
        if (! $tenant) {
            return collect();
        }

        if (auth()->user()?->isRestrictedBarber()) {
            $proId = auth()->user()->resolveProfessionalId($tenant->id) ?? 0;

            return $tenant->professionals()
                ->whereKey($proId)
                ->get();
        }

        return $tenant->professionals()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get();
    }

    public function getServicesProperty(): Collection
    {
        $tenant = filament()->getTenant();
        if (! $tenant) {
            return collect();
        }

        return $tenant->services()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get();
    }

    public function getDailyAppointmentsProperty(): Collection
    {
        $tenant = filament()->getTenant();
        if (! $tenant) {
            return collect();
        }

        $query = Appointment::where('barbershop_id', $tenant->id)
            ->whereDate('scheduled_at', $this->selectedDate)
            ->with(['professional', 'service', 'services'])
            ->orderBy('scheduled_at');

        if (auth()->user()?->isRestrictedBarber()) {
            $proId = auth()->user()->resolveProfessionalId($tenant->id) ?? 0;
            $query->where('professional_id', $proId);
        }

        return $query->get();
    }

    public function getAppointmentsByBarberProperty(): array
    {
        $grouped = [];
        $appointments = $this->dailyAppointments;

        foreach ($this->professionals as $prof) {
            $grouped[$prof->id] = $appointments->where('professional_id', $prof->id)->values();
        }

        return $grouped;
    }

    public function getDayStatsProperty(): array
    {
        $appts = $this->dailyAppointments;

        return [
            'total_count' => $appts->count(),
            'completed_count' => $appts->where('status', 'completed')->count(),
            'in_progress_count' => $appts->where('status', 'in_progress')->count(),
            'total_revenue' => $appts->whereNotIn('status', ['cancelled', 'no_show'])->sum('service_price'),
            'realized_revenue' => $appts->where('status', 'completed')->sum('service_price'),
        ];
    }

    public function updateStatus(int $appointmentId, string $status): void
    {
        $appt = $this->findAccessibleAppointment($appointmentId);
        if (! $appt) {
            return;
        }

        if (in_array($appt->status, ['completed', 'cancelled', 'no_show'], true)) {
            Notification::make()
                ->title('Este agendamento já foi encerrado ou marcado como falta.')
                ->warning()
                ->send();

            return;
        }

        $appt->status = $status;

        if ($status === 'completed' && $appt->payment_status === 'pending') {
            $appt->payment_status = 'paid';
        }

        $appt->save();

        Notification::make()
            ->title('Status atualizado para: '.$appt->status_label)
            ->success()
            ->send();
    }

    public function completeAppointmentAction(): Action
    {
        return Action::make('completeAppointment')
            ->modalHeading('Concluir Corte & Registrar Pagamento')
            ->modalIcon('heroicon-o-check-badge')
            ->modalSubmitActionLabel('Confirmar Recebimento & Concluir')
            ->modalWidth(Width::Large)
            ->schema([
                Placeholder::make('client_summary')
                    ->label('Atendimento')
                    ->content(fn ($get) => new HtmlString(
                        '<div class="space-y-1 rounded-xl border border-gray-200 bg-gray-50 p-3.5 text-sm leading-relaxed text-gray-700 dark:border-white/10 dark:bg-white/5 dark:text-gray-300">'
                        .'<div><strong class="text-gray-500 dark:text-gray-400">Cliente:</strong> <span class="font-semibold text-gray-950 dark:text-white">'.e($get('client_name') ?: '—').'</span></div>'
                        .'<div><strong class="text-gray-500 dark:text-gray-400">Serviço(s):</strong> <span class="font-semibold text-primary-600 dark:text-primary-400">'.e($get('services_summary') ?: '—').'</span></div>'
                        .'</div>'
                    ))
                    ->columnSpanFull(),

                TextInput::make('service_price')
                    ->label('Valor do Corte / Serviços (R$)')
                    ->prefix('R$')
                    ->numeric()
                    ->required()
                    ->live(onBlur: true)
                    ->columnSpanFull(),

                Select::make('payment_method')
                    ->label('Forma de Pagamento Principal')
                    ->options([
                        'pix' => '📱 PIX',
                        'dinheiro' => '💵 Dinheiro',
                        'debito' => '💳 Cartão Débito',
                        'credito' => '💳 Cartão Crédito',
                    ])
                    ->default('pix')
                    ->required()
                    ->columnSpanFull(),

                Section::make('Pagamento Dividido (Opcional)')
                    ->description('Preencha os valores usados nas outras formas. O restante será computado na forma principal.')
                    ->collapsible()
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('additional_pix_amount')
                            ->label('PIX adicional (R$)')
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                        TextInput::make('additional_dinheiro_amount')
                            ->label('Dinheiro adicional (R$)')
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                        TextInput::make('additional_debito_amount')
                            ->label('Cartão de débito adicional (R$)')
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                        TextInput::make('additional_credito_amount')
                            ->label('Cartão de crédito adicional (R$)')
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                    ])
                    ->columnSpanFull(),

                Select::make('consumed_product_ids')
                    ->label('🧴 Vender Produtos ao Cliente (Opcional - Gera Comissão)')
                    ->options(function () {
                        $tenant = filament()->getTenant();
                        if (! $tenant) {
                            return [];
                        }

                        return Product::where('barbershop_id', $tenant->id)
                            ->where('is_active', true)
                            ->where('stock_quantity', '>', 0)
                            ->orderBy('name')
                            ->get()
                            ->mapWithKeys(fn ($p) => [
                                $p->id => "{$p->name} — R$ ".number_format((float) $p->price, 2, ',', '.').' ('.($p->stock_quantity > 0 ? "{$p->stock_quantity} un. em estoque" : 'sem estoque').')',
                            ])
                            ->all();
                    })
                    ->multiple()
                    ->live()
                    ->searchable()
                    ->dynamicOptions(false)
                    ->placeholder('Nenhum produto selecionado (opcional)')
                    ->helperText('O produto é um PLUS opcional. Se o cliente realizou apenas o corte/serviço, basta deixar este campo vazio.')
                    ->columnSpanFull(),

                Placeholder::make('checkout_total')
                    ->label('Total do atendimento')
                    ->content(function (Get $get): string {
                        $tenant = filament()->getTenant();
                        $productIds = collect((array) $get('consumed_product_ids'))
                            ->map(fn ($id): int => (int) $id)
                            ->filter()
                            ->unique();
                        $productsTotal = $tenant
                            ? Product::query()->where('barbershop_id', $tenant->id)->whereIn('id', $productIds)->sum('price')
                            : 0;

                        return 'R$ '.number_format((float) $get('service_price') + (float) $productsTotal, 2, ',', '.');
                    })
                    ->columnSpanFull(),
            ])
            ->mountUsing(function (Schema $schema, Action $action): void {
                $arguments = $action->getArguments();
                $appointmentId = (int) ($arguments['appointmentId'] ?? 0);
                if (! $appointmentId) {
                    return;
                }

                $appt = $this->findAccessibleAppointment($appointmentId);
                if (! $appt) {
                    $action->cancel();

                    return;
                }

                if (in_array($appt->status, ['completed', 'cancelled', 'no_show'], true)) {
                    Notification::make()
                        ->title('Este agendamento não pode ser concluído pois já foi encerrado ou marcado como falta.')
                        ->warning()
                        ->send();

                    $action->cancel();

                    return;
                }

                $schema->fill([
                    'appointment_id' => $appt->id,
                    'client_name' => $appt->client_name,
                    'services_summary' => $appt->services_summary,
                    'service_price' => (float) $appt->service_price,
                    'payment_method' => in_array($appt->payment_method, ['pix', 'dinheiro', 'debito', 'credito'])
                        ? $appt->payment_method
                        : 'pix',
                    'consumed_product_ids' => [],
                    'additional_payments' => [],
                ]);
            })
            ->action(function (array $data, Action $action): void {
                $arguments = $action->getArguments();
                $appointmentId = (int) ($arguments['appointmentId'] ?? $data['appointment_id'] ?? 0);
                if ($appointmentId <= 0) {
                    Notification::make()
                        ->title('Identificador do agendamento inválido.')
                        ->warning()
                        ->send();

                    return;
                }

                $appt = $this->findAccessibleAppointment($appointmentId);
                if (! $appt) {
                    return;
                }

                if (in_array($appt->status, ['completed', 'cancelled', 'no_show'], true)) {
                    Notification::make()
                        ->title('Este agendamento já foi encerrado ou marcado como falta.')
                        ->warning()
                        ->send();

                    return;
                }

                $grandTotal = app(CheckoutService::class)->completeAppointment(
                    appointment: $appt,
                    servicePrice: (float) ($data['service_price'] ?? $appt->service_price),
                    primaryPaymentMethod: (string) ($data['payment_method'] ?? 'pix'),
                    additionalAmounts: [
                        'pix' => $data['additional_pix_amount'] ?? 0,
                        'dinheiro' => $data['additional_dinheiro_amount'] ?? 0,
                        'debito' => $data['additional_debito_amount'] ?? 0,
                        'credito' => $data['additional_credito_amount'] ?? 0,
                    ],
                    productIds: (array) ($data['consumed_product_ids'] ?? []),
                );

                Notification::make()
                    ->title('Atendimento concluído e lançado no Caixa!')
                    ->body('Valor total recebido: R$ '.number_format($grandTotal, 2, ',', '.'))
                    ->success()
                    ->send();
            });
    }

    public function quickAppointmentAction(): Action
    {
        return Action::make('quickAppointment')
            ->modalHeading('Novo encaixe no balcão')
            ->modalIcon('heroicon-o-calendar-days')
            ->modalSubmitActionLabel('Salvar encaixe')
            ->modalWidth(Width::TwoExtraLarge)
            ->schema([
                Tabs::make('Encaixe')
                    ->tabs([
                        Tab::make('Atendimento')
                            ->icon('heroicon-o-scissors')
                            ->schema([
                                Select::make('professional_id')
                                    ->label('Barbeiro responsável')
                                    ->options(fn (): array => $this->professionals
                                        ->mapWithKeys(fn ($professional): array => [
                                            $professional->id => $professional->display_name,
                                        ])
                                        ->all())
                                    ->searchable()
                                    ->dynamicOptions(false)
                                    ->required()
                                    ->columnSpanFull(),

                                Select::make('service_ids')
                                    ->label('Serviço(s)')
                                    ->options(fn (): array => $this->services
                                        ->mapWithKeys(fn ($service): array => [
                                            $service->id => $service->name
                                                .' — R$ '.number_format((float) $service->price, 2, ',', '.')
                                                .' ('.$service->duration_minutes.' min)',
                                        ])
                                        ->all())
                                    ->multiple()
                                    ->searchable()
                                    ->dynamicOptions(false)
                                    ->live()
                                    ->required()
                                    ->minItems(1)
                                    ->placeholder('Selecione um ou mais serviços')
                                    ->helperText('Você pode selecionar mais de um serviço para o mesmo encaixe. O valor e a duração total serão somados automaticamente.')
                                    ->columnSpanFull(),

                                Placeholder::make('quick_services_summary')
                                    ->label('Resumo dos serviços selecionados')
                                    ->content(function (Get $get): HtmlString {
                                        $selectedIds = collect((array) $get('service_ids'))
                                            ->map(fn ($id): int => (int) $id)
                                            ->filter()
                                            ->unique()
                                            ->all();

                                        if (empty($selectedIds)) {
                                            return new HtmlString('<span class="text-xs text-gray-500 dark:text-gray-400">Selecione ao menos um serviço acima.</span>');
                                        }

                                        $selectedServices = $this->services->whereIn('id', $selectedIds);
                                        $totalPrice = (float) $selectedServices->sum('price');
                                        $totalDuration = (int) ($selectedServices->sum('duration_minutes') ?: 30);
                                        $count = $selectedServices->count();

                                        return new HtmlString(
                                            '<div class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm dark:border-white/10 dark:bg-white/5">'
                                            .'<span class="text-gray-600 dark:text-gray-300"><strong>'.$count.'</strong> '.($count === 1 ? 'serviço' : 'serviços').' • Duração total: <strong>'.$totalDuration.' min</strong></span>'
                                            .'<span class="font-bold text-primary-600 dark:text-primary-400">Total: R$ '.number_format($totalPrice, 2, ',', '.').'</span>'
                                            .'</div>'
                                        );
                                    })
                                    ->columnSpanFull(),

                                DateTimePicker::make('scheduled_at')
                                    ->label('Data e horário de início')
                                    ->seconds(false)
                                    ->required()
                                    ->columnSpanFull(),
                            ]),
                        Tab::make('Cliente')
                            ->icon('heroicon-o-user')
                            ->schema([
                                TextInput::make('client_name')
                                    ->label('Nome completo do cliente')
                                    ->required()
                                    ->minLength(2)
                                    ->maxLength(255)
                                    ->columnSpanFull(),

                                TextInput::make('client_phone')
                                    ->label('WhatsApp do cliente')
                                    ->tel()
                                    ->required()
                                    ->minLength(8)
                                    ->maxLength(20)
                                    ->placeholder('Ex: 85999999999')
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ])
            ->mountUsing(function (Schema $schema, Action $action): void {
                $arguments = $action->getArguments();
                $defaultProfessionalId = ! empty($arguments['professionalId'])
                    ? (int) $arguments['professionalId']
                    : $this->professionals->first()?->id;
                $defaultTime = $arguments['time'] ?? '14:00';
                $firstServiceId = $this->services->first()?->id;

                $schema->fill([
                    'professional_id' => $defaultProfessionalId,
                    'service_ids' => $firstServiceId ? [$firstServiceId] : [],
                    'scheduled_at' => Carbon::parse("{$this->selectedDate} {$defaultTime}"),
                ]);
            })
            ->action(function (array $data): void {
                $tenant = filament()->getTenant();
                if (! ($tenant instanceof Barbershop) || ! auth()->user()?->canAccessTenant($tenant)) {
                    Notification::make()
                        ->title('Acesso não autorizado para esta unidade.')
                        ->danger()
                        ->send();

                    return;
                }

                $serviceIds = collect((array) ($data['service_ids'] ?? []))
                    ->map(fn ($id): int => (int) $id)
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                $services = $tenant->services()
                    ->where('is_active', true)
                    ->whereIn('id', $serviceIds)
                    ->orderBy('display_order')
                    ->get();

                if ($services->isEmpty()) {
                    Notification::make()
                        ->title('Selecione pelo menos um serviço válido e ativo.')
                        ->warning()
                        ->send();

                    return;
                }

                $professional = $tenant->professionals()
                    ->where('is_active', true)
                    ->find($data['professional_id']);

                if (! $professional) {
                    Notification::make()
                        ->title('Barbeiro selecionado não encontrado ou inativo.')
                        ->warning()
                        ->send();

                    return;
                }

                if (
                    auth()->user()?->isRestrictedBarber()
                    && (int) $professional->getKey() !== (int) (auth()->user()?->resolveProfessionalId($tenant->id) ?? 0)
                ) {
                    Notification::make()
                        ->title('Barbeiros só podem agendar encaixes na sua própria agenda.')
                        ->danger()
                        ->send();

                    return;
                }

                $primaryService = $services->first();
                $totalPrice = (float) $services->sum('price');
                $totalDuration = (int) ($services->sum('duration_minutes') ?: 30);

                $scheduledAt = Carbon::parse($data['scheduled_at']);
                $commissionRate = $professional->commission_rate ?: 50.00;
                $commissionAmount = round(($totalPrice * $commissionRate) / 100, 2);

                $appointment = Appointment::create([
                    'barbershop_id' => $tenant->id,
                    'professional_id' => $professional->id,
                    'service_id' => $primaryService->id,
                    'client_name' => trim($data['client_name']),
                    'client_phone' => trim($data['client_phone']),
                    'scheduled_at' => $scheduledAt,
                    'ends_at' => $scheduledAt->copy()->addMinutes($totalDuration),
                    'status' => 'confirmed',
                    'service_price' => $totalPrice,
                    'commission_rate' => $commissionRate,
                    'commission_amount' => $commissionAmount,
                    'payment_status' => 'pending',
                    'notes' => 'Encaixe rápido realizado via painel',
                ]);

                $syncData = [];
                foreach ($services as $srv) {
                    $syncData[$srv->id] = [
                        'price' => $srv->price,
                        'duration_minutes' => $srv->duration_minutes,
                    ];
                }
                $appointment->services()->sync($syncData);

                Notification::make()
                    ->title('Encaixe agendado com sucesso para '.$professional->display_name)
                    ->body($services->pluck('name')->join(' + ').' • R$ '.number_format($totalPrice, 2, ',', '.'))
                    ->success()
                    ->send();
            });
    }

    public function editAppointmentAction(): Action
    {
        return Action::make('editAppointment')
            ->modalHeading('Editar / Reagendar Atendimento')
            ->modalIcon('heroicon-o-pencil-square')
            ->modalSubmitActionLabel('Salvar Alterações')
            ->modalWidth(Width::TwoExtraLarge)
            ->schema([
                Tabs::make('EditarAgendamento')
                    ->tabs([
                        Tab::make('Atendimento')
                            ->icon('heroicon-o-scissors')
                            ->schema([
                                Select::make('professional_id')
                                    ->label('Barbeiro responsável')
                                    ->options(fn (): array => $this->professionals
                                        ->mapWithKeys(fn ($professional): array => [
                                            $professional->id => $professional->display_name,
                                        ])
                                        ->all())
                                    ->searchable()
                                    ->dynamicOptions(false)
                                    ->required()
                                    ->columnSpanFull(),

                                Select::make('service_ids')
                                    ->label('Serviço(s)')
                                    ->options(fn (): array => $this->services
                                        ->mapWithKeys(fn ($service): array => [
                                            $service->id => $service->name
                                                .' — R$ '.number_format((float) $service->price, 2, ',', '.')
                                                .' ('.$service->duration_minutes.' min)',
                                        ])
                                        ->all())
                                    ->multiple()
                                    ->searchable()
                                    ->dynamicOptions(false)
                                    ->live()
                                    ->required()
                                    ->minItems(1)
                                    ->columnSpanFull(),

                                Placeholder::make('edit_services_summary')
                                    ->label('Resumo dos serviços selecionados')
                                    ->content(function (Get $get): HtmlString {
                                        $selectedIds = collect((array) $get('service_ids'))
                                            ->map(fn ($id): int => (int) $id)
                                            ->filter()
                                            ->unique()
                                            ->all();

                                        if (empty($selectedIds)) {
                                            return new HtmlString('<span class="text-xs text-gray-500 dark:text-gray-400">Selecione ao menos um serviço acima.</span>');
                                        }

                                        $selectedServices = $this->services->whereIn('id', $selectedIds);
                                        $totalPrice = (float) $selectedServices->sum('price');
                                        $totalDuration = (int) ($selectedServices->sum('duration_minutes') ?: 30);
                                        $count = $selectedServices->count();

                                        return new HtmlString(
                                            '<div class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm dark:border-white/10 dark:bg-white/5">'
                                            .'<span class="text-gray-600 dark:text-gray-300"><strong>'.$count.'</strong> '.($count === 1 ? 'serviço' : 'serviços').' • Duração total: <strong>'.$totalDuration.' min</strong></span>'
                                            .'<span class="font-bold text-primary-600 dark:text-primary-400">Total: R$ '.number_format($totalPrice, 2, ',', '.').'</span>'
                                            .'</div>'
                                        );
                                    })
                                    ->columnSpanFull(),

                                DateTimePicker::make('scheduled_at')
                                    ->label('Data e horário de início')
                                    ->seconds(false)
                                    ->required()
                                    ->columnSpanFull(),
                            ]),
                        Tab::make('Cliente')
                            ->icon('heroicon-o-user')
                            ->schema([
                                TextInput::make('client_name')
                                    ->label('Nome completo do cliente')
                                    ->required()
                                    ->minLength(2)
                                    ->maxLength(255)
                                    ->columnSpanFull(),

                                TextInput::make('client_phone')
                                    ->label('WhatsApp do cliente')
                                    ->tel()
                                    ->maxLength(20)
                                    ->placeholder('Ex: 85999999999')
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ])
            ->mountUsing(function (Schema $schema, Action $action): void {
                $arguments = $action->getArguments();
                $appointmentId = (int) ($arguments['appointmentId'] ?? 0);
                if (! $appointmentId) {
                    return;
                }

                $appt = $this->findAccessibleAppointment($appointmentId);
                if (! $appt) {
                    $action->cancel();

                    return;
                }

                if (in_array($appt->status, ['completed', 'cancelled', 'no_show'], true)) {
                    Notification::make()
                        ->title('Este agendamento já foi encerrado e não pode ser editado.')
                        ->warning()
                        ->send();

                    $action->cancel();

                    return;
                }

                $appt->loadMissing('services');
                $currentServiceIds = $appt->services->pluck('id')->all();
                if (empty($currentServiceIds) && $appt->service_id) {
                    $currentServiceIds = [$appt->service_id];
                }

                $schema->fill([
                    'appointment_id' => $appt->id,
                    'professional_id' => $appt->professional_id,
                    'service_ids' => $currentServiceIds,
                    'scheduled_at' => $appt->scheduled_at,
                    'client_name' => $appt->client_name,
                    'client_phone' => $appt->client_phone,
                ]);
            })
            ->action(function (array $data, Action $action): void {
                $arguments = $action->getArguments();
                $appointmentId = (int) ($arguments['appointmentId'] ?? $data['appointment_id'] ?? 0);
                if ($appointmentId <= 0) {
                    return;
                }

                $appt = $this->findAccessibleAppointment($appointmentId);
                if (! $appt) {
                    return;
                }

                if (in_array($appt->status, ['completed', 'cancelled', 'no_show'], true)) {
                    return;
                }

                $tenant = filament()->getTenant();
                if (! ($tenant instanceof Barbershop)) {
                    return;
                }

                $serviceIds = collect((array) ($data['service_ids'] ?? []))
                    ->map(fn ($id): int => (int) $id)
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                $services = $tenant->services()
                    ->whereIn('id', $serviceIds)
                    ->orderBy('display_order')
                    ->get();

                if ($services->isEmpty()) {
                    Notification::make()->title('Selecione pelo menos um serviço válido.')->warning()->send();

                    return;
                }

                $professional = $tenant->professionals()->where('is_active', true)->find($data['professional_id']);
                if (! $professional) {
                    Notification::make()->title('Barbeiro não encontrado.')->warning()->send();

                    return;
                }

                $primaryService = $services->first();
                $totalPrice = (float) $services->sum('price');
                $totalDuration = (int) ($services->sum('duration_minutes') ?: 30);

                $scheduledAt = Carbon::parse($data['scheduled_at']);
                $commissionRate = $professional->commission_rate ?: 50.00;
                $commissionAmount = round(($totalPrice * $commissionRate) / 100, 2);

                $appt->update([
                    'professional_id' => $professional->id,
                    'service_id' => $primaryService->id,
                    'client_name' => trim($data['client_name']),
                    'client_phone' => isset($data['client_phone']) ? trim((string) $data['client_phone']) : $appt->client_phone,
                    'scheduled_at' => $scheduledAt,
                    'ends_at' => $scheduledAt->copy()->addMinutes($totalDuration),
                    'service_price' => $totalPrice,
                    'commission_rate' => $commissionRate,
                    'commission_amount' => $commissionAmount,
                ]);

                $syncData = [];
                foreach ($services as $srv) {
                    $syncData[$srv->id] = [
                        'price' => $srv->price,
                        'duration_minutes' => $srv->duration_minutes,
                    ];
                }
                $appt->services()->sync($syncData);

                Notification::make()
                    ->title('Agendamento atualizado com sucesso!')
                    ->success()
                    ->send();
            });
    }

    public function cancelAppointmentAction(): Action
    {
        return Action::make('cancelAppointment')
            ->requiresConfirmation()
            ->color('danger')
            ->modalHeading('Cancelar Agendamento?')
            ->modalDescription('Tem certeza que deseja cancelar este agendamento? O horário ficará livre novamente na agenda do barbeiro.')
            ->modalSubmitActionLabel('Sim, cancelar agendamento')
            ->action(function (Action $action): void {
                $arguments = $action->getArguments();
                $appointmentId = (int) ($arguments['appointmentId'] ?? 0);
                if ($appointmentId <= 0) {
                    return;
                }

                $appt = $this->findAccessibleAppointment($appointmentId);
                if (! $appt) {
                    return;
                }

                if (in_array($appt->status, ['completed', 'cancelled', 'no_show'], true)) {
                    return;
                }

                $appt->update([
                    'status' => 'cancelled',
                ]);

                Notification::make()
                    ->title('Agendamento cancelado.')
                    ->success()
                    ->send();
            });
    }

    private function findAccessibleAppointment(int $appointmentId): ?Appointment
    {
        $tenant = filament()->getTenant();
        $user = auth()->user();

        if (! ($tenant instanceof Barbershop) || ! $user?->canAccessTenant($tenant)) {
            Notification::make()
                ->title('Acesso não autorizado para esta unidade.')
                ->danger()
                ->send();

            return null;
        }

        $appointment = $tenant->appointments()
            ->when(
                $user->isRestrictedBarber(),
                fn ($query) => $query->where('professional_id', $user->resolveProfessionalId($tenant->id) ?? 0),
            )
            ->find($appointmentId);

        if (! $appointment) {
            Notification::make()
                ->title('Agendamento não encontrado ou não acessível.')
                ->warning()
                ->send();

            return null;
        }

        return $appointment;
    }
}

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
                        '<div style="font-size: 13px; line-height: 1.6; color: #cbd5e1; background: #0f172a; padding: 12px 16px; border-radius: 12px; border: 1px solid #1e293b;">'
                        .'<div><strong style="color: #94a3b8;">Cliente:</strong> <span style="color: #ffffff; font-weight: 700;">'.e($get('client_name') ?: '—').'</span></div>'
                        .'<div><strong style="color: #94a3b8;">Serviço(s):</strong> <span style="color: #fbbf24; font-weight: 700;">'.e($get('services_summary') ?: '—').'</span></div>'
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
                abort_unless($appointmentId > 0, 404);

                $appt = $this->findAccessibleAppointment($appointmentId);

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

                                Select::make('service_id')
                                    ->label('Serviço')
                                    ->options(fn (): array => $this->services
                                        ->mapWithKeys(fn ($service): array => [
                                            $service->id => $service->name
                                                .' — R$ '.number_format((float) $service->price, 2, ',', '.')
                                                .' ('.$service->duration_minutes.' min)',
                                        ])
                                        ->all())
                                    ->searchable()
                                    ->dynamicOptions(false)
                                    ->required()
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
                $defaultProfessionalId = $arguments['professionalId']
                    ?? $this->professionals->first()?->id;
                $defaultTime = $arguments['time'] ?? '14:00';

                $schema->fill([
                    'professional_id' => $defaultProfessionalId,
                    'service_id' => $this->services->first()?->id,
                    'scheduled_at' => Carbon::parse("{$this->selectedDate} {$defaultTime}"),
                ]);
            })
            ->action(function (array $data): void {
                $tenant = filament()->getTenant();
                abort_unless(
                    $tenant instanceof Barbershop && auth()->user()?->canAccessTenant($tenant),
                    403,
                );

                $service = $tenant->services()->findOrFail($data['service_id']);
                $professional = $tenant->professionals()
                    ->where('is_active', true)
                    ->findOrFail($data['professional_id']);

                if (
                    auth()->user()?->isRestrictedBarber()
                    && (int) $professional->getKey() !== (int) (auth()->user()?->resolveProfessionalId($tenant->id) ?? 0)
                ) {
                    abort(403);
                }

                $scheduledAt = Carbon::parse($data['scheduled_at']);
                $commissionRate = $professional->commission_rate ?: 50.00;
                $commissionAmount = round(((float) $service->price * $commissionRate) / 100, 2);

                $appointment = Appointment::create([
                    'barbershop_id' => $tenant->id,
                    'professional_id' => $professional->id,
                    'service_id' => $service->id,
                    'client_name' => trim($data['client_name']),
                    'client_phone' => trim($data['client_phone']),
                    'scheduled_at' => $scheduledAt,
                    'ends_at' => $scheduledAt->copy()->addMinutes($service->duration_minutes),
                    'status' => 'confirmed',
                    'service_price' => $service->price,
                    'commission_rate' => $commissionRate,
                    'commission_amount' => $commissionAmount,
                    'payment_status' => 'pending',
                    'notes' => 'Encaixe rápido realizado via painel',
                ]);

                $appointment->services()->sync([
                    $service->id => [
                        'price' => $service->price,
                        'duration_minutes' => $service->duration_minutes,
                    ],
                ]);

                Notification::make()
                    ->title('Encaixe agendado com sucesso para '.$professional->display_name)
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

                                Select::make('service_id')
                                    ->label('Serviço')
                                    ->options(fn (): array => $this->services
                                        ->mapWithKeys(fn ($service): array => [
                                            $service->id => $service->name
                                                .' — R$ '.number_format((float) $service->price, 2, ',', '.')
                                                .' ('.$service->duration_minutes.' min)',
                                        ])
                                        ->all())
                                    ->searchable()
                                    ->dynamicOptions(false)
                                    ->required()
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

                if (in_array($appt->status, ['completed', 'cancelled', 'no_show'], true)) {
                    Notification::make()
                        ->title('Este agendamento já foi encerrado e não pode ser editado.')
                        ->warning()
                        ->send();

                    $action->cancel();

                    return;
                }

                $schema->fill([
                    'appointment_id' => $appt->id,
                    'professional_id' => $appt->professional_id,
                    'service_id' => $appt->service_id,
                    'scheduled_at' => $appt->scheduled_at,
                    'client_name' => $appt->client_name,
                    'client_phone' => $appt->client_phone,
                ]);
            })
            ->action(function (array $data, Action $action): void {
                $arguments = $action->getArguments();
                $appointmentId = (int) ($arguments['appointmentId'] ?? $data['appointment_id'] ?? 0);
                abort_unless($appointmentId > 0, 404);

                $appt = $this->findAccessibleAppointment($appointmentId);
                if (in_array($appt->status, ['completed', 'cancelled', 'no_show'], true)) {
                    return;
                }

                $tenant = filament()->getTenant();
                $service = $tenant->services()->findOrFail($data['service_id']);
                $professional = $tenant->professionals()->where('is_active', true)->findOrFail($data['professional_id']);
                $scheduledAt = Carbon::parse($data['scheduled_at']);
                $commissionRate = $professional->commission_rate ?: 50.00;
                $commissionAmount = round(((float) $service->price * $commissionRate) / 100, 2);

                $appt->update([
                    'professional_id' => $professional->id,
                    'service_id' => $service->id,
                    'client_name' => trim($data['client_name']),
                    'client_phone' => isset($data['client_phone']) ? trim((string) $data['client_phone']) : $appt->client_phone,
                    'scheduled_at' => $scheduledAt,
                    'ends_at' => $scheduledAt->copy()->addMinutes($service->duration_minutes),
                    'service_price' => $service->price,
                    'commission_rate' => $commissionRate,
                    'commission_amount' => $commissionAmount,
                ]);

                $appt->services()->sync([
                    $service->id => [
                        'price' => $service->price,
                        'duration_minutes' => $service->duration_minutes,
                    ],
                ]);

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
                abort_unless($appointmentId > 0, 404);

                $appt = $this->findAccessibleAppointment($appointmentId);
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

    private function findAccessibleAppointment(int $appointmentId): Appointment
    {
        $tenant = filament()->getTenant();
        $user = auth()->user();

        abort_unless(
            $tenant instanceof Barbershop && $user?->canAccessTenant($tenant),
            403,
        );

        return $tenant->appointments()
            ->when(
                $user->isRestrictedBarber(),
                fn ($query) => $query->where('professional_id', $user->resolveProfessionalId($tenant->id) ?? 0),
            )
            ->findOrFail($appointmentId);
    }
}

<?php

namespace App\Filament\Pages;

use App\Models\Appointment;
use App\Models\Barbershop;
use App\Models\CashMovement;
use App\Models\Product;
use App\Models\ProductSale;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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

    // Estado do Modal de Conclusão e Recebimento
    public bool $checkoutModalOpen = false;

    public ?int $checkoutAppointmentId = null;

    public string $checkoutPaymentMethod = 'pix'; // pix, dinheiro, debito, credito

    public float $checkoutAmount = 0.00;

    public ?string $checkoutClientName = '';

    public ?string $checkoutServicesSummary = '';

    /** @var array<int, array{product_id: int, name: string, price: float, commission_rate: float, quantity: int}> */
    public array $checkoutProducts = [];

    public ?int $selectedProductIdToAdd = null;

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

        $query = $tenant->professionals()
            ->where('is_active', true)
            ->orderBy('display_order');

        if (auth()->user()?->isRestrictedBarber()) {
            $proId = auth()->user()->resolveProfessionalId($tenant->id) ?? 0;
            $query->where('id', $proId);
        }

        return $query->get();
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

    public function openCheckoutModal(int $appointmentId): void
    {
        $appt = $this->findAccessibleAppointment($appointmentId);
        $this->checkoutAppointmentId = $appt->id;
        $this->checkoutAmount = (float) $appt->service_price;
        $this->checkoutPaymentMethod = $appt->payment_method ?: 'pix';
        $this->checkoutClientName = $appt->client_name;
        $this->checkoutServicesSummary = $appt->services_summary;
        $this->checkoutProducts = [];
        $this->selectedProductIdToAdd = null;
        $this->checkoutModalOpen = true;
    }

    public function addProductToCheckout(): void
    {
        if (! $this->selectedProductIdToAdd) {
            return;
        }

        $tenant = filament()->getTenant();
        if (! $tenant) {
            return;
        }

        $product = Product::where('barbershop_id', $tenant->id)
            ->where('is_active', true)
            ->find($this->selectedProductIdToAdd);

        if (! $product || $product->stock_quantity <= 0) {
            Notification::make()->title('Produto indisponível ou sem estoque.')->warning()->send();
            return;
        }

        foreach ($this->checkoutProducts as $idx => $item) {
            if ($item['product_id'] === $product->id) {
                if ($item['quantity'] + 1 > $product->stock_quantity) {
                    Notification::make()->title("Estoque máximo disponível: {$product->stock_quantity}")->warning()->send();
                    return;
                }
                $this->checkoutProducts[$idx]['quantity']++;
                $this->selectedProductIdToAdd = null;
                return;
            }
        }

        $this->checkoutProducts[] = [
            'product_id' => $product->id,
            'name' => $product->name,
            'price' => (float) $product->price,
            'commission_rate' => (float) $product->commission_rate,
            'quantity' => 1,
        ];

        $this->selectedProductIdToAdd = null;
    }

    public function removeProductFromCheckout(int $index): void
    {
        if (isset($this->checkoutProducts[$index])) {
            unset($this->checkoutProducts[$index]);
            $this->checkoutProducts = array_values($this->checkoutProducts);
        }
    }

    public function getAvailableProductsProperty(): Collection
    {
        $tenant = filament()->getTenant();
        if (! $tenant) {
            return collect();
        }

        return Product::where('barbershop_id', $tenant->id)
            ->where('is_active', true)
            ->where('stock_quantity', '>', 0)
            ->orderBy('name')
            ->get();
    }

    public function getCheckoutProductsTotalProperty(): float
    {
        $total = 0.0;
        foreach ($this->checkoutProducts as $item) {
            $total += (float) $item['price'] * (int) $item['quantity'];
        }
        return $total;
    }

    public function getCheckoutGrandTotalProperty(): float
    {
        return (float) $this->checkoutAmount + $this->checkoutProductsTotal;
    }

    public function confirmCheckout(): void
    {
        if (! $this->checkoutAppointmentId) {
            return;
        }

        $appt = $this->findAccessibleAppointment($this->checkoutAppointmentId);
        $tenant = filament()->getTenant();

        $productsTotal = 0.0;
        $productsCommissionTotal = 0.0;

        foreach ($this->checkoutProducts as $item) {
            $product = Product::where('barbershop_id', $tenant?->id)->find($item['product_id']);
            if ($product) {
                $qty = max(1, (int) $item['quantity']);
                $lineTotal = (float) $product->price * $qty;
                $productsTotal += $lineTotal;

                // Taxa de comissão do produto (fallback para a taxa do profissional)
                $commRate = (float) $product->commission_rate;
                if ($commRate <= 0 && $appt->professional) {
                    $commRate = (float) ($appt->professional->commission_rate ?? 0);
                }
                $commAmount = round(($lineTotal * $commRate) / 100, 2);
                $productsCommissionTotal += $commAmount;

                $appt->products()->attach($product->id, [
                    'quantity' => $qty,
                    'unit_price' => $product->price,
                    'total_price' => $lineTotal,
                ]);

                $product->decrement('stock_quantity', $qty);

                ProductSale::create([
                    'barbershop_id' => $appt->barbershop_id,
                    'product_id' => $product->id,
                    'professional_id' => $appt->professional_id,
                    'appointment_id' => $appt->id,
                    'quantity' => $qty,
                    'unit_price' => $product->price,
                    'total_price' => $lineTotal,
                    'cost_price' => $product->cost_price,
                    'commission_rate' => $commRate,
                    'commission_amount' => $commAmount,
                    'commission_status' => 'pending',
                    'payment_method' => $this->checkoutPaymentMethod,
                    'client_name' => $appt->client_name,
                    'notes' => "Produto vendido no atendimento #{$appt->id}",
                ]);
            }
        }

        $serviceCommAmount = round(((float) $this->checkoutAmount * (float) $appt->commission_rate) / 100, 2);
        $totalCommission = $serviceCommAmount + $productsCommissionTotal;
        $grandTotal = (float) $this->checkoutAmount + $productsTotal;

        $appt->status = 'completed';
        $appt->payment_status = 'paid';
        $appt->payment_method = $this->checkoutPaymentMethod;
        $appt->service_price = $this->checkoutAmount;
        $appt->products_total = (float) $appt->products_total + $productsTotal;
        $appt->products_commission_amount = (float) $appt->products_commission_amount + $productsCommissionTotal;
        $appt->commission_amount = $totalCommission;
        $appt->commission_status = 'pending';
        $appt->paid_at = now();
        $appt->save();

        CashMovement::create([
            'barbershop_id' => $appt->barbershop_id,
            'user_id' => auth()->id(),
            'type' => 'inflow',
            'description' => "Corte/Serviço: {$appt->client_name}" . ($productsTotal > 0 ? " (+ produtos: R$ " . number_format($productsTotal, 2, ',', '.') . ")" : ''),
            'amount' => $grandTotal,
            'date' => now()->toDateString(),
        ]);

        $this->checkoutModalOpen = false;
        $this->checkoutProducts = [];

        $msg = "Recebido via {$appt->payment_method} no valor total de R$ " . number_format($grandTotal, 2, ',', '.');
        if ($productsCommissionTotal > 0) {
            $msg .= " (Comissão de R$ " . number_format($productsCommissionTotal, 2, ',', '.') . " em produtos computada para o barbeiro).";
        }

        Notification::make()
            ->title('Atendimento concluído e lançado no Caixa!')
            ->body($msg)
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
                        .'<div><strong style="color: #94a3b8;">Cliente:</strong> <span style="color: #ffffff; font-weight: 700;">' . e($get('client_name') ?: '—') . '</span></div>'
                        .'<div><strong style="color: #94a3b8;">Serviço(s):</strong> <span style="color: #fbbf24; font-weight: 700;">' . e($get('services_summary') ?: '—') . '</span></div>'
                        .'</div>'
                    ))
                    ->columnSpanFull(),

                TextInput::make('service_price')
                    ->label('Valor do Corte / Serviços (R$)')
                    ->prefix('R$')
                    ->numeric()
                    ->required()
                    ->columnSpanFull(),

                Select::make('payment_method')
                    ->label('Forma de Pagamento')
                    ->options([
                        'pix' => '📱 PIX',
                        'dinheiro' => '💵 Dinheiro',
                        'debito' => '💳 Cartão Débito',
                        'credito' => '💳 Cartão Crédito',
                    ])
                    ->default('pix')
                    ->required()
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
                            ->orderBy('name')
                            ->get()
                            ->mapWithKeys(fn ($p) => [
                                $p->id => "{$p->name} — R$ " . number_format((float) $p->price, 2, ',', '.') . ' (' . ($p->stock_quantity > 0 ? "{$p->stock_quantity} un. em estoque" : 'sem estoque') . ')'
                            ])
                            ->all();
                    })
                    ->multiple()
                    ->searchable()
                    ->dynamicOptions(false)
                    ->placeholder('Nenhum produto selecionado (opcional)')
                    ->helperText('O produto é um PLUS opcional. Se o cliente realizou apenas o corte/serviço, basta deixar este campo vazio.')
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

                $tenant = filament()->getTenant();

                DB::transaction(function () use ($appt, $tenant, $data) {
                    $productsTotal = 0.0;
                    $productsCommissionTotal = 0.0;

                    $productIds = (array) ($data['consumed_product_ids'] ?? []);

                    foreach ($productIds as $productId) {
                        $productId = (int) $productId;
                        if ($productId <= 0) {
                            continue;
                        }

                        $product = Product::where('barbershop_id', $tenant?->id)
                            ->lockForUpdate()
                            ->find($productId);

                        if (! $product) {
                            continue;
                        }

                        $lineTotal = (float) $product->price;
                        $productsTotal += $lineTotal;

                        $commRate = (float) $product->commission_rate;
                        if ($commRate <= 0 && $appt->professional) {
                            $commRate = (float) ($appt->professional->commission_rate ?? 0);
                        }
                        $commAmount = round(($lineTotal * $commRate) / 100, 2);
                        $productsCommissionTotal += $commAmount;

                        $appt->products()->attach($product->id, [
                            'quantity' => 1,
                            'unit_price' => $product->price,
                            'total_price' => $lineTotal,
                        ]);

                        $product->update([
                            'stock_quantity' => max(0, (int) $product->stock_quantity - 1),
                        ]);

                        ProductSale::create([
                            'barbershop_id' => $appt->barbershop_id,
                            'product_id' => $product->id,
                            'professional_id' => $appt->professional_id,
                            'appointment_id' => $appt->id,
                            'quantity' => 1,
                            'unit_price' => $product->price,
                            'total_price' => $lineTotal,
                            'cost_price' => $product->cost_price,
                            'commission_rate' => $commRate,
                            'commission_amount' => $commAmount,
                            'commission_status' => 'pending',
                            'payment_method' => $data['payment_method'] ?? 'pix',
                            'client_name' => $appt->client_name,
                            'notes' => "Produto vendido no atendimento #{$appt->id}",
                        ]);
                    }

                    $servicePrice = (float) ($data['service_price'] ?? $appt->service_price);
                    $commRate = (float) ($appt->professional?->commission_rate ?: $appt->commission_rate ?: 50.0);
                    $serviceCommAmount = round(($servicePrice * $commRate) / 100, 2);
                    $totalCommission = $serviceCommAmount + $productsCommissionTotal;
                    $grandTotal = $servicePrice + $productsTotal;

                    $appt->status = 'completed';
                    $appt->payment_status = 'paid';
                    $appt->payment_method = $data['payment_method'] ?? 'pix';
                    $appt->service_price = $servicePrice;
                    $appt->products_total = (float) $appt->products_total + $productsTotal;
                    $appt->products_commission_amount = (float) $appt->products_commission_amount + $productsCommissionTotal;
                    $appt->commission_amount = $totalCommission;
                    $appt->commission_status = 'pending';
                    $appt->paid_at = now();
                    $appt->save();

                    CashMovement::create([
                        'barbershop_id' => $appt->barbershop_id,
                        'user_id' => auth()->id(),
                        'type' => 'inflow',
                        'description' => "Corte/Serviço: {$appt->client_name}" . ($productsTotal > 0 ? " (+ produtos: R$ " . number_format($productsTotal, 2, ',', '.') . ")" : ''),
                        'amount' => $grandTotal,
                        'date' => now()->toDateString(),
                    ]);

                    $msg = "Recebido via {$appt->payment_method} no valor total de R$ " . number_format($grandTotal, 2, ',', '.');
                    if ($productsCommissionTotal > 0) {
                        $msg .= " (Comissão de R$ " . number_format($productsCommissionTotal, 2, ',', '.') . " em produtos computada para o barbeiro).";
                    }

                    Notification::make()
                        ->title('Atendimento concluído e lançado no Caixa!')
                        ->body($msg)
                        ->success()
                        ->send();
                });
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

<?php

namespace App\Filament\Pages;

use App\Filament\Exports\PaymentMethodSummaryExporter;
use App\Services\PaymentMethodReceiptReport as PaymentMethodReceiptReportService;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Pages\Page;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use UnitEnum;

class PaymentMethodReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $title = 'Relatório de Recebimentos por Forma de Pagamento';

    protected static string|UnitEnum|null $navigationGroup = 'Relatórios';

    protected static ?string $navigationParentItem = 'Financeiro';

    protected static ?string $navigationLabel = 'Recebimentos por forma';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.payment-method-report';

    #[Url]
    public string $periodStart = '';

    #[Url]
    public string $periodEnd = '';

    public function mount(): void
    {
        $this->periodStart = $this->periodStart ?: now()->startOfMonth()->toDateString();
        $this->periodEnd = $this->periodEnd ?: now()->endOfMonth()->toDateString();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['periodStart', 'periodEnd'], true) && $this->periodStart !== '' && $this->periodEnd !== '') {
            $this->validate([
                'periodStart' => ['required', 'date_format:Y-m-d'],
                'periodEnd' => ['required', 'date_format:Y-m-d', 'after_or_equal:periodStart'],
            ]);

            $this->resetTable();
        }
    }

    public static function canAccess(): bool
    {
        return Auth::user()?->getAllPermissions()->contains('name', 'View:PaymentMethodReport') ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => app(PaymentMethodReceiptReportService::class)->query(...$this->periodBounds()))
            ->columns([
                TextColumn::make('name')
                    ->label('Forma de recebimento')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('receipt_count')
                    ->label('Quantidade de recebimentos')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('amount_total')
                    ->label('Valor recebido')
                    ->money('BRL')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Forma ativa')
                    ->boolean(),
            ])
            ->headerActions([
                ExportAction::make()
                    ->label('Exportar relatório')
                    ->exporter(PaymentMethodSummaryExporter::class)
                    ->formats([ExportFormat::Csv, ExportFormat::Xlsx])
                    ->authorize(fn (): bool => Auth::user()?->getAllPermissions()->contains('name', 'Export:PaymentMethodReport') ?? false)
                    ->visible(fn (): bool => Auth::user()?->getAllPermissions()->contains('name', 'Export:PaymentMethodReport') ?? false),
            ])
            ->defaultSort('amount_total', 'desc')
            ->emptyStateHeading('Nenhum recebimento encontrado')
            ->emptyStateDescription('Não há recebimentos registrados no período escolhido.');
    }

    #[Computed]
    public function summary(): array
    {
        $rows = app(PaymentMethodReceiptReportService::class)->query(...$this->periodBounds())->get();
        $totalCount = (int) $rows->sum('receipt_count');
        $totalAmount = (float) $rows->sum('amount_total');
        $mostUsed = $rows->sortByDesc('receipt_count')->first();

        return [
            'count' => $totalCount,
            'amount' => $totalAmount,
            'most_used' => $mostUsed?->name,
            'most_used_count' => (int) ($mostUsed?->receipt_count ?? 0),
            'most_used_share' => $totalCount > 0 ? round(((int) ($mostUsed?->receipt_count ?? 0) / $totalCount) * 100, 1) : 0,
        ];
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function periodBounds(): array
    {
        $valid = Validator::make([
            'periodStart' => $this->periodStart,
            'periodEnd' => $this->periodEnd,
        ], [
            'periodStart' => ['required', 'date_format:Y-m-d'],
            'periodEnd' => ['required', 'date_format:Y-m-d', 'after_or_equal:periodStart'],
        ])->passes();

        if (! $valid) {
            return [now()->startOfMonth(), now()->endOfMonth()];
        }

        return [Carbon::parse($this->periodStart)->startOfDay(), Carbon::parse($this->periodEnd)->endOfDay()];
    }
}

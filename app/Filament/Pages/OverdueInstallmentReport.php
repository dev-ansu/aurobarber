<?php

namespace App\Filament\Pages;

use App\Filament\Exports\OverdueInstallmentExporter;
use App\Models\Installment;
use App\Services\FinancialCalculationService;
use App\Services\PixService;
use Filament\Actions\Action;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class OverdueInstallmentReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $title = 'Relatório de Inadimplência e Mensalidades Vencidas';

    protected static string|UnitEnum|null $navigationGroup = 'Relatórios';

    protected static ?string $navigationParentItem = 'Financeiro';

    protected static ?string $navigationLabel = 'Inadimplência';

    protected static ?string $modelLabel = 'Relatório de inadimplência';

    protected static ?string $pluralModelLabel = 'Relatórios de inadimplência';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.overdue-installment-report';

    public static function canAccess(): bool
    {
        return Auth::user()?->getAllPermissions()->contains('name', 'View:OverdueInstallmentReport') ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => self::baseQuery())
            ->columns([
                TextColumn::make('contract.contract_number')
                    ->label('Contrato')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('contract.student.name')
                    ->label('Aluno')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('financialType.name')
                    ->label('Tipo financeiro')
                    ->placeholder('—'),
                TextColumn::make('due_date')
                    ->label('Vencimento')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('days_overdue')
                    ->label('Dias em atraso')
                    ->state(fn (Installment $record): int => (int) $record->due_date->diffInDays(today()))
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('due_date', $direction === 'asc' ? 'desc' : 'asc')),
                TextColumn::make('aging_band')
                    ->label('Faixa')
                    ->state(fn (Installment $record): string => self::agingBand((int) $record->due_date->diffInDays(today())))
                    ->badge()
                    ->color(fn (Installment $record): string => match (true) {
                        $record->due_date->diffInDays(today()) <= 7 => 'warning',
                        $record->due_date->diffInDays(today()) <= 30 => 'orange',
                        default => 'danger',
                    }),
                TextColumn::make('outstanding_amount')
                    ->label('Saldo em aberto')
                    ->state(fn (Installment $record): float => self::outstandingAmount($record))
                    ->money('BRL')
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderByRaw('(amount - discount + fine - COALESCE(amount_paid, 0)) '.($direction === 'asc' ? 'asc' : 'desc'))),
            ])
            ->filters([
                Filter::make('due_date_range')
                    ->label('Período de vencimento')
                    ->schema([
                        DatePicker::make('from')->label('De'),
                        DatePicker::make('until')->label('Até'),
                    ])
                    ->columns(2)
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('due_date', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('due_date', '<=', $date))),
                SelectFilter::make('aging_band')
                    ->label('Faixa de atraso')
                    ->options([
                        '1_7' => '1 a 7 dias',
                        '8_30' => '8 a 30 dias',
                        '31_60' => '31 a 60 dias',
                        '61_90' => '61 a 90 dias',
                        'over_90' => 'Mais de 90 dias',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => self::applyAgingBand($query, $data['value'] ?? null)),
                SelectFilter::make('financial_type_id')
                    ->label('Tipo financeiro')
                    ->relationship('financialType', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->headerActions([
                ExportAction::make()
                    ->label('Exportar relatório')
                    ->exporter(OverdueInstallmentExporter::class)
                    ->formats([ExportFormat::Csv, ExportFormat::Xlsx])
                    ->authorize(fn (): bool => Auth::user()?->getAllPermissions()->contains('name', 'Export:OverdueInstallmentReport') ?? false)
                    ->visible(fn (): bool => Auth::user()?->getAllPermissions()->contains('name', 'Export:OverdueInstallmentReport') ?? false),
            ])
            ->recordActions([
                Action::make('whatsappCharge')
                    ->label('WhatsApp')
                    ->icon('heroicon-m-chat-bubble-left-ellipsis')
                    ->color('success')
                    ->tooltip('Cobrar via WhatsApp com código Pix')
                    ->visible(fn (Installment $record): bool => auth()->user()?->can('chargeWhatsApp', $record) ?? false)
                    ->modalHeading(fn (Installment $record): string => "Cobrança via WhatsApp — Contrato #{$record->contract?->contract_number}")
                    ->modalDescription('Confirme o número e a mensagem que será aberta no seu WhatsApp.')
                    ->modalSubmitActionLabel('Abrir WhatsApp')
                    ->form([
                        TextInput::make('phone')
                            ->label('Telefone do Responsável / Aluno')
                            ->placeholder('Ex: 11999998888')
                            ->required()
                            ->default(fn (Installment $record): string => app(FinancialCalculationService::class)->resolveContactPhone($record) ?? '')
                            ->helperText('Número formatado com DDD. Ao confirmar, o WhatsApp Web abrirá para este contato.'),

                        Textarea::make('message')
                            ->label('Mensagem da Cobrança')
                            ->rows(8)
                            ->required()
                            ->default(function (Installment $record): string {
                                $studentName = $record->contract?->student?->name ?? 'Aluno(a)';
                                $contractNumber = $record->contract?->contract_number ?? $record->contract_id;
                                $num = $record->installment_number ?: '1';
                                $total = $record->total_installments ?: '1';
                                $dueDate = $record->due_date ? Carbon::parse($record->due_date)->format('d/m/Y') : '-';
                                $amount = number_format((float) ($record->amount_paid > 0 ? $record->amount_paid : $record->amount), 2, ',', '.');

                                $school = $record->school
                                    ?? $record->contract?->school
                                    ?? (class_exists(\Filament\Facades\Filament::class) ? \Filament\Facades\Filament::getTenant() : null)
                                    ?? School::getActiveSchool()
                                    ?? School::query()->first();
                                $schoolName = $school?->trade_name ?: ($school?->legal_name ?: 'nossa escola');

                                $pixData = app(PixService::class)->generateForInstallment($record, $school);
                                $pixCode = $pixData['payload'] ?? $pixData['copy_paste'] ?? '';

                                $msg = "Olá, {$studentName}! Tudo bem?\n\n";
                                $msg .= "Passando para lembrar sobre a parcela {$num}/{$total} do seu curso na {$schoolName} (Contrato #{$contractNumber}), com vencimento em {$dueDate}.\n";
                                $msg .= "Valor: R$ {$amount}\n\n";

                                if ($pixCode) {
                                    $msg .= "Chave Pix Copia e Cola para pagamento:\n{$pixCode}\n\n";
                                }

                                $msg .= "Se você já realizou o pagamento, por favor desconsidere esta mensagem. Tenha um ótimo dia!";

                                return $msg;
                            }),
                    ])
                    ->action(function (Installment $record, array $data, $livewire): void {
                        $phone = app(FinancialCalculationService::class)->formatInternationalPhone((string) $data['phone']);
                        $text = rawurlencode($data['message']);
                        $waUrl = "https://wa.me/{$phone}?text={$text}";

                        $livewire->js("window.open('{$waUrl}', '_blank');");

                        Notification::make()
                            ->title('WhatsApp Aberto!')
                            ->body('A tela de conversa com o responsável foi iniciada no seu navegador.')
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('due_date')
            ->striped()
            ->emptyStateHeading('Nenhuma parcela vencida encontrada')
            ->emptyStateDescription('As parcelas pendentes e parcialmente pagas aparecerão aqui.');
    }

    public static function baseQuery(): Builder
    {
        return Installment::query()
            ->forSchool()
            ->with(['contract.student', 'financialType'])
            ->whereIn('status', ['pending', 'partial'])
            ->whereDate('due_date', '<', today())
            ->whereRaw('(amount - discount + fine - COALESCE(amount_paid, 0)) > 0');
    }

    public static function applyAgingBand(Builder $query, ?string $band): Builder
    {
        $today = today();

        return match ($band) {
            '1_7' => $query->whereBetween('due_date', [$today->copy()->subDays(7)->toDateString(), $today->copy()->subDay()->toDateString()]),
            '8_30' => $query->whereBetween('due_date', [$today->copy()->subDays(30)->toDateString(), $today->copy()->subDays(8)->toDateString()]),
            '31_60' => $query->whereBetween('due_date', [$today->copy()->subDays(60)->toDateString(), $today->copy()->subDays(31)->toDateString()]),
            '61_90' => $query->whereBetween('due_date', [$today->copy()->subDays(90)->toDateString(), $today->copy()->subDays(61)->toDateString()]),
            'over_90' => $query->whereDate('due_date', '<', $today->copy()->subDays(90)->toDateString()),
            default => $query,
        };
    }

    public static function agingBand(int $days): string
    {
        return match (true) {
            $days <= 7 => '1 a 7 dias',
            $days <= 30 => '8 a 30 dias',
            $days <= 60 => '31 a 60 dias',
            $days <= 90 => '61 a 90 dias',
            default => 'Mais de 90 dias',
        };
    }

    public static function outstandingAmount(Installment $installment): float
    {
        return max(0, (float) $installment->amount - (float) $installment->discount + (float) $installment->fine - (float) $installment->amount_paid);
    }
}

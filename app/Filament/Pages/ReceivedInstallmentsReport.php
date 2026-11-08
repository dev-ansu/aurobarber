<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Exports\ReceivedInstallmentExporter;
use App\Models\Contract;
use App\Models\FinancialType;
use App\Models\Installment;
use App\Models\PaymentMethod;
use Filament\Actions\Action;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
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
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use UnitEnum;

class ReceivedInstallmentsReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $title = 'Contas Recebidas (Gestão de Matrículas e Notas Fiscais - ISS)';

    protected static string|UnitEnum|null $navigationGroup = 'Relatórios';

    protected static ?string $navigationParentItem = 'Financeiro';

    protected static ?string $navigationLabel = 'Contas Recebidas (ISS / NF)';

    protected static ?string $slug = 'contas-recebidas';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.received-installments-report';

    #[Url]
    public string $periodStart = '';

    #[Url]
    public string $periodEnd = '';

    public string $activePreset = 'current_month';

    public function mount(): void
    {
        $this->periodStart = $this->periodStart ?: now()->startOfMonth()->toDateString();
        $this->periodEnd = $this->periodEnd ?: now()->endOfMonth()->toDateString();
    }

    public static function canAccess(): bool
    {
        return Auth::user()?->hasRole('super_admin')
            || (Auth::user()?->getAllPermissions()->contains('name', 'View:ReceivedInstallmentsReport') ?? false)
            || (Auth::user()?->getAllPermissions()->contains('name', 'view_any_installment') ?? false)
            || (Auth::user()?->getAllPermissions()->contains('name', 'View:PaymentMethodReport') ?? false);
    }

    public function setPeriod(string $preset): void
    {
        $this->activePreset = $preset;

        match ($preset) {
            'current_month' => [
                $this->periodStart = now()->startOfMonth()->toDateString(),
                $this->periodEnd = now()->endOfMonth()->toDateString(),
            ],
            'previous_month' => [
                $this->periodStart = now()->startOfMonth()->subMonthNoOverflow()->startOfMonth()->toDateString(),
                $this->periodEnd = now()->startOfMonth()->subMonthNoOverflow()->endOfMonth()->toDateString(),
            ],
            'last_30_days' => [
                $this->periodStart = now()->subDays(30)->toDateString(),
                $this->periodEnd = now()->toDateString(),
            ],
            'current_year' => [
                $this->periodStart = now()->startOfYear()->toDateString(),
                $this->periodEnd = now()->endOfYear()->toDateString(),
            ],
            default => null,
        };

        $this->resetTable();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['periodStart', 'periodEnd'], true) && $this->periodStart !== '' && $this->periodEnd !== '') {
            $this->activePreset = 'custom';

            $this->validate([
                'periodStart' => ['required', 'date_format:Y-m-d'],
                'periodEnd' => ['required', 'date_format:Y-m-d', 'after_or_equal:periodStart'],
            ]);

            $this->resetTable();
        }
    }

    #[Computed]
    public function stats(): array
    {
        $baseQuery = $this->getBaseQuery();

        $totalRecebido = (float) (clone $baseQuery)->sum('amount_paid');
        $totalRegistros = (int) (clone $baseQuery)->count();

        // Matrículas / Entradas
        $totalMatriculas = (float) (clone $baseQuery)
            ->where(function (Builder $q) {
                $q->whereHas('financialType', function (Builder $fq) {
                    $fq->where('slug', 'like', '%matricula%')
                        ->orWhereRaw('LOWER(name) LIKE ?', ['%matr%'])
                        ->orWhere('slug', 'like', '%entrada%');
                })->orWhere(function (Builder $sq) {
                    $sq->where('installment_number', 1)
                        ->whereDoesntHave('financialType', fn (Builder $fq) => $fq->where('slug', 'mensalidade'));
                });
            })
            ->sum('amount_paid');

        // Mensalidades
        $totalMensalidades = (float) (clone $baseQuery)
            ->whereHas('financialType', function (Builder $fq) {
                $fq->where('slug', 'mensalidade')
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%mensalidade%'])
                    ->orWhere('slug', 'tuition');
            })
            ->sum('amount_paid');

        // Total de Alunos Distintos
        $alunosDistintos = (int) (clone $baseQuery)
            ->whereHas('contract')
            ->pluck('contract_id')
            ->unique()
            ->pipe(function ($contractIds) {
                if ($contractIds->isEmpty()) {
                    return 0;
                }

                return Contract::query()
                    ->whereIn('id', $contractIds)
                    ->distinct('student_id')
                    ->count('student_id');
            });

        return [
            'total_recebido' => $totalRecebido,
            'total_matriculas' => $totalMatriculas,
            'total_mensalidades' => $totalMensalidades,
            'total_registros' => $totalRegistros,
            'alunos_distintos' => $alunosDistintos,
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->getBaseQuery())
            ->columns([
                TextColumn::make('contract.contract_number')
                    ->label('Nº Contrato')
                    ->searchable()
                    ->sortable()
                    ->color('primary')
                    ->weight('bold')
                    ->url(fn (Installment $record): ?string => $record->contract_id ? route('filament.admin.resources.contracts.view', ['tenant' => $record->school_id ?? session('filament_tenant_id') ?? 1, 'record' => $record->contract_id]) : null)
                    ->openUrlInNewTab(),

                TextColumn::make('contract.student.name')
                    ->label('Aluno')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->description(fn (Installment $record): string => 'CPF: '.(self::formatCpf($record->contract?->student?->cpf) ?: 'Não informado')),

                TextColumn::make('tomador_resumo')
                    ->label('Responsável / Tomador Fiscal')
                    ->state(function (Installment $record): string {
                        $resp = $record->contract?->responsible;
                        if ($resp && $resp->person) {
                            $parentesco = $resp->relationship?->getLabel() ?? 'Responsável';

                            return "{$resp->person->name} ({$parentesco})";
                        }

                        return 'O Próprio Aluno';
                    })
                    ->description(function (Installment $record): string {
                        $resp = $record->contract?->responsible;
                        $cpf = $resp?->person?->cpf ?: $record->contract?->student?->cpf;

                        return 'CPF: '.(self::formatCpf($cpf) ?: 'Sem CPF cadastrado');
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        $searchLower = '%'.mb_strtolower($search).'%';

                        return $query->whereHas('contract.responsible.person', function (Builder $q) use ($searchLower, $search) {
                            $q->whereRaw('LOWER(name) LIKE ?', [$searchLower])
                                ->orWhere('cpf', 'like', "%{$search}%");
                        });
                    }),

                TextColumn::make('financialType.name')
                    ->label('Tipo de Cobrança')
                    ->badge()
                    ->color(function (Installment $record): string {
                        $slug = $record->financialType?->slug ?? '';
                        if (str_contains($slug, 'matricula') || str_contains($slug, 'entrada')) {
                            return 'info';
                        }
                        if (str_contains($slug, 'mensalidade')) {
                            return 'success';
                        }
                        if (str_contains($slug, 'material')) {
                            return 'warning';
                        }

                        return 'gray';
                    })
                    ->sortable(),

                TextColumn::make('installment_number')
                    ->label('Parcela')
                    ->formatStateUsing(function ($state, Installment $record): string {
                        if (blank($state)) {
                            return 'Entrada / Avulsa';
                        }

                        return "{$state} / ".($record->total_installments ?: '1');
                    })
                    ->sortable(),

                TextColumn::make('paid_at')
                    ->label('Data Pagamento')
                    ->date('d/m/Y')
                    ->sortable()
                    ->description(fn (Installment $record): string => $record->due_date ? 'Venc: '.$record->due_date->format('d/m/Y') : ''),

                TextColumn::make('amount_paid')
                    ->label('Valor Recebido')
                    ->money('BRL')
                    ->weight('bold')
                    ->color('success')
                    ->sortable(),

                TextColumn::make('paymentMethod.name')
                    ->label('Forma de Pagto')
                    ->badge()
                    ->placeholder('Não informada')
                    ->color('gray'),
            ])
            ->filters([
                SelectFilter::make('financial_type_id')
                    ->label('Tipo de Cobrança')
                    ->options(fn (): array => FinancialType::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->preload(),

                SelectFilter::make('payment_method_id')
                    ->label('Forma de Pagamento')
                    ->options(fn (): array => PaymentMethod::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->preload(),

                Filter::make('has_responsible')
                    ->label('Perfil do Tomador')
                    ->form([
                        Select::make('profile')
                            ->label('Tomador do Serviço')
                            ->options([
                                'with_responsible' => 'Alunos com Responsável Financeiro (Menores)',
                                'self_responsible' => 'Alunos que são o próprio tomador (Maiores)',
                            ])
                            ->placeholder('Todos os tomadores'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (($data['profile'] ?? null) === 'with_responsible') {
                            return $query->whereHas('contract', fn (Builder $cq) => $cq->whereNotNull('responsible_id'));
                        }
                        if (($data['profile'] ?? null) === 'self_responsible') {
                            return $query->whereHas('contract', fn (Builder $cq) => $cq->whereNull('responsible_id'));
                        }

                        return $query;
                    }),

                Filter::make('cpf_status')
                    ->label('Status do CPF')
                    ->form([
                        Select::make('cpf_filter')
                            ->label('Validação de CPF')
                            ->options([
                                'missing' => '⚠️ Alerta: Sem CPF cadastrado (Precisa para o ISS)',
                                'filled' => '✅ Com CPF cadastrado',
                            ])
                            ->placeholder('Todos'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (($data['cpf_filter'] ?? null) === 'missing') {
                            return $query->whereHas('contract', function (Builder $cq) {
                                $cq->where(function (Builder $sub) {
                                    $sub->whereHas('responsible.person', fn (Builder $pq) => $pq->whereNull('cpf')->orWhere('cpf', ''))
                                        ->orWhere(function (Builder $inner) {
                                            $inner->whereNull('responsible_id')
                                                ->whereHas('student', fn (Builder $sq) => $sq->whereNull('cpf')->orWhere('cpf', ''));
                                        });
                                });
                            });
                        }

                        if (($data['cpf_filter'] ?? null) === 'filled') {
                            return $query->whereHas('contract', function (Builder $cq) {
                                $cq->where(function (Builder $sub) {
                                    $sub->whereHas('responsible.person', fn (Builder $pq) => $pq->whereNotNull('cpf')->where('cpf', '!=', ''))
                                        ->orWhere(function (Builder $inner) {
                                            $inner->whereNull('responsible_id')
                                                ->whereHas('student', fn (Builder $sq) => $sq->whereNotNull('cpf')->where('cpf', '!=', ''));
                                        });
                                });
                            });
                        }

                        return $query;
                    }),
            ])
            ->actions([
                // Ação: Ficha Fiscal Completa para o ISS Fortaleza
                Action::make('fichaFiscal')
                    ->label('Ficha ISS')
                    ->icon('heroicon-o-identification')
                    ->color('info')
                    ->modalHeading(fn (Installment $record): string => 'Ficha de Emissão Fiscal (ISS Fortaleza) - Contrato #'.($record->contract?->contract_number ?? ''))
                    ->modalDescription('Dados completos do aluno, responsável financeiro e discriminação para preenchimento da Nota Fiscal de Serviços (NFS-e).')
                    ->modalContent(fn (Installment $record) => view('filament.pages.actions.installment-fiscal-sheet', [
                        'installment' => $record,
                        'contract' => $record->contract,
                        'student' => $record->contract?->student,
                        'responsible' => $record->contract?->responsible,
                        'downPayment' => self::calculateContractDownPayment($record->contract),
                        'totalPaid' => self::calculateContractTotalPaid($record->contract),
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fechar'),

                // Ação Rápida: Copiar Linha Fiscal
                Action::make('copiarDados')
                    ->label('Copiar Dados')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('gray')
                    ->action(function (Installment $record): void {
                        $resp = $record->contract?->responsible?->person;
                        $student = $record->contract?->student;
                        $tomador = $resp ?? $student;
                        $nomeTomador = $tomador?->name ?? 'Tomador';
                        $cpfTomador = self::formatCpf($tomador?->cpf) ?: 'Sem CPF';
                        $valor = number_format((float) ($record->amount_paid ?? $record->amount), 2, ',', '.');
                        $tipo = $record->financialType?->name ?? 'Serviços Educacionais';
                        $contrato = $record->contract?->contract_number ?? '';

                        $resumo = "Tomador Fiscal: {$nomeTomador} (CPF: {$cpfTomador})";
                        if ($resp && $student && $resp->id !== $student->id) {
                            $cpfAluno = self::formatCpf($student->cpf) ?: 'Sem CPF';
                            $resumo .= " | Aluno(a): {$student->name} (CPF: {$cpfAluno})";
                        }
                        $resumo .= " | Valor: R$ {$valor} | Discriminação: Serviços educacionais ({$tipo}) - Contrato #{$contrato}";

                        Notification::make()
                            ->title('Dados prontos para o ISS Fortaleza!')
                            ->body($resumo)
                            ->success()
                            ->send();
                    }),
            ])
            ->headerActions([
                ExportAction::make()
                    ->label('Exportar Excel / CSV')
                    ->exporter(ReceivedInstallmentExporter::class)
                    ->formats([ExportFormat::Xlsx, ExportFormat::Csv])
                    ->color('success')
                    ->icon('heroicon-o-arrow-down-tray'),

                Action::make('imprimirRelatorio')
                    ->label('Imprimir Relatório')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn (): string => route('admin.contas-recebidas.print', [
                        'start' => $this->periodStart,
                        'end' => $this->periodEnd,
                        'school_id' => session('filament_tenant_id') ?? 1,
                    ]))
                    ->openUrlInNewTab(),
            ])
            ->defaultSort('paid_at', 'desc')
            ->emptyStateHeading('Nenhuma conta recebida no período')
            ->emptyStateDescription('Ajuste os filtros de período para visualizar os recebimentos e notas a emitir.');
    }

    protected function getBaseQuery(): Builder
    {
        $start = Carbon::parse($this->periodStart)->startOfDay();
        $end = Carbon::parse($this->periodEnd)->endOfDay();

        return Installment::query()
            ->forSchool()
            ->where(function (Builder $q) {
                $q->where('installments.status', 'paid')
                    ->orWhere(function (Builder $sub) {
                        $sub->where('installments.status', 'partial')
                            ->whereNotNull('installments.amount_paid')
                            ->where('installments.amount_paid', '>', 0);
                    });
            })
            ->where(function (Builder $q) use ($start, $end) {
                // Filtra pela data em que o pagamento foi efetivamente baixado/recebido
                $q->whereBetween('installments.paid_at', [$start, $end])
                    ->orWhere(function (Builder $fallback) use ($start, $end) {
                        $fallback->whereNull('installments.paid_at')
                            ->whereBetween('installments.updated_at', [$start, $end]);
                    });
            })
            ->with([
                'contract.student.addresses.city.state',
                'contract.student.contacts',
                'contract.responsible.person.addresses.city.state',
                'contract.responsible.person.contacts',
                'financialType',
                'paymentMethod',
            ]);
    }

    public static function formatCpf(?string $cpf): string
    {
        if (blank($cpf)) {
            return '';
        }

        $clean = preg_replace('/\D/', '', (string) $cpf);

        if (strlen($clean) === 11) {
            return substr($clean, 0, 3).'.'.substr($clean, 3, 3).'.'.substr($clean, 6, 3).'-'.substr($clean, 9, 2);
        }

        if (strlen($clean) === 14) {
            return substr($clean, 0, 2).'.'.substr($clean, 2, 3).'.'.substr($clean, 5, 3).'/'.substr($clean, 8, 4).'-'.substr($clean, 12, 2);
        }

        return $cpf;
    }

    public static function calculateContractDownPayment(?Contract $contract): float
    {
        if (! $contract) {
            return 0.0;
        }

        return (float) $contract->installments()
            ->where('status', 'paid')
            ->where(function (Builder $q) {
                $q->whereHas('financialType', function (Builder $fq) {
                    $fq->where('slug', 'like', '%matricula%')
                        ->orWhereRaw('LOWER(name) LIKE ?', ['%matr%'])
                        ->orWhere('slug', 'like', '%entrada%');
                })->orWhere('installment_number', 1);
            })
            ->sum('amount_paid');
    }

    public static function calculateContractTotalPaid(?Contract $contract): float
    {
        if (! $contract) {
            return 0.0;
        }

        return (float) $contract->installments()
            ->where('status', 'paid')
            ->sum('amount_paid');
    }
}

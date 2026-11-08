<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Contract;
use App\Models\ContractHistory;
use App\Models\Installment;
use App\Models\User;
use App\Services\FinancialCalculationService;
use App\Services\PixService;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use UnitEnum;

class CollectionKanban extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Financeiro';

    protected static ?string $navigationLabel = 'Fila de Cobrança (Kanban)';

    protected static ?string $title = 'Fila e Régua de Cobrança da Secretaria';

    protected static ?string $slug = 'fila-de-cobranca';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-view-columns';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.collection-kanban';

    public array $cards = [];

    public string $search = '';

    // Modal de anotação de contato
    public ?int $selectedContractId = null;

    public string $followUpNote = '';

    public ?string $nextFollowUpDate = null;

    public bool $showFollowUpModal = false;

    // Modal de Acordo / Renegociação de Dívidas
    public bool $showAgreementModal = false;

    public ?int $agreementContractId = null;

    public ?string $agreementStudentName = null;

    public ?string $agreementContractNumber = null;

    public array $agreementPendingInstallments = [];

    public array $agreementSelectedInstallmentIds = [];

    public float $agreementDiscount = 0.0;

    public float $agreementDownPayment = 0.0;

    public bool $agreementDownPaymentPaid = false;

    public ?int $agreementPaymentMethodId = null;

    public int $agreementInstallmentsCount = 1;

    public string $agreementFirstDueDate = '';

    public string $agreementNotes = '';

    public function getMaxContentWidth(): ?string
    {
        return 'full';
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        return $user->hasRole('super_admin')
            || $user->can('View:CollectionKanban');
    }

    public function mount(): void
    {
        $this->loadCards();
    }

    public function stages(): array
    {
        return [
            'upcoming' => [
                'label' => 'Lembrete (Próx. 5 dias)',
                'color' => 'blue',
                'description' => 'Aviso amigável antes do vencimento',
            ],
            'delay_light' => [
                'label' => 'Atraso Leve (1 a 10 dias)',
                'color' => 'amber',
                'description' => '1º aviso de vencimento com Pix',
            ],
            'delay_moderate' => [
                'label' => 'Atraso Moderado (11 a 30 dias)',
                'color' => 'orange',
                'description' => '2º contato com juros/multa',
            ],
            'critical' => [
                'label' => 'Crítico (31+ dias)',
                'color' => 'rose',
                'description' => 'Candidatos à renegociação de dívida',
            ],
            'in_agreement' => [
                'label' => 'Em Acordo / Promessa',
                'color' => 'purple',
                'description' => 'Contratos com termo formalizado',
            ],
        ];
    }

    public function loadCards(): void
    {
        $schoolId = Filament::getTenant()?->id ?? session('filament_tenant_id');
        $today = Carbon::today();

        $installments = Installment::query()
            ->withoutGlobalScopes()
            ->forSchool($schoolId)
            ->with([
                'contract.student.contacts',
                'contract.responsible.person.contacts',
                'contract.debtAgreements',
                'contract.histories' => fn ($q) => $q->where('type', 'financial_follow_up')->latest('occurred_at')->limit(1),
            ])
            ->whereIn('status', ['pending', 'partial'])
            ->where(function ($q) use ($today) {
                // Atrasadas ou vencendo nos próximos 5 dias
                $q->whereDate('due_date', '<', $today)
                    ->orWhereBetween('due_date', [$today->toDateString(), $today->copy()->addDays(5)->toDateString()]);
            })
            ->orderBy('due_date')
            ->get();

        $calcService = app(FinancialCalculationService::class);
        $pixService = app(PixService::class);

        $grouped = [
            'upcoming' => [],
            'delay_light' => [],
            'delay_moderate' => [],
            'critical' => [],
            'in_agreement' => [],
        ];

        foreach ($installments as $inst) {
            $contract = $inst->contract;
            if (! $contract) {
                continue;
            }

            $dueDate = Carbon::parse($inst->due_date)->startOfDay();
            $daysDiff = (int) $dueDate->diffInDays($today, false); // positivo se vencido, negativo se futuro

            // Determinar estágio
            $hasActiveAgreement = $contract->debtAgreements()->where('status', 'active')->exists();

            if ($hasActiveAgreement) {
                $stage = 'in_agreement';
            } elseif ($daysDiff <= 0) {
                $stage = 'upcoming';
            } elseif ($daysDiff <= 10) {
                $stage = 'delay_light';
            } elseif ($daysDiff <= 30) {
                $stage = 'delay_moderate';
            } else {
                $stage = 'critical';
            }

            $phone = $calcService->resolveContactPhone($inst);
            $studentName = $contract->student?->name ?? 'Aluno';
            $respName = $contract->responsible?->person?->name;

            // Filtro de busca simples por texto
            if ($this->search !== '') {
                $searchLower = mb_strtolower($this->search);
                $matches = str_contains(mb_strtolower($studentName), $searchLower)
                    || str_contains((string) $contract->contract_number, $searchLower)
                    || ($respName && str_contains(mb_strtolower($respName), $searchLower));

                if (! $matches) {
                    continue;
                }
            }

            $amountDue = (float) ($inst->amount_paid > 0 ? ($inst->amount - $inst->amount_paid) : $inst->amount);
            $latestHistory = $contract->histories->first();

            $grouped[$stage][] = [
                'installment_id' => $inst->id,
                'contract_id' => $contract->id,
                'contract_number' => $contract->contract_number ?? (string) $contract->id,
                'student_name' => $studentName,
                'responsible_name' => $respName,
                'phone' => $phone,
                'installment_number' => $inst->installment_number,
                'total_installments' => $inst->total_installments,
                'due_date' => $dueDate->format('d/m/Y'),
                'days_diff' => $daysDiff,
                'amount' => $amountDue,
                'stage' => $stage,
                'latest_note' => $latestHistory?->description,
                'latest_note_date' => $latestHistory?->occurred_at?->format('d/m H:i'),
            ];
        }

        $this->cards = $grouped;
    }

    public function openWhatsApp(int $installmentId): void
    {
        $installment = Installment::find($installmentId);
        if (! $installment) {
            return;
        }

        $calcService = app(FinancialCalculationService::class);
        $pixService = app(PixService::class);

        $phone = $calcService->resolveContactPhone($installment);
        if (! $phone) {
            Notification::make()->title('Telefone não encontrado')->body('Este aluno não possui número de celular cadastrado.')->warning()->send();

            return;
        }

        $studentName = $installment->contract?->student?->name ?? 'Aluno(a)';
        $contractNumber = $installment->contract?->contract_number ?? $installment->contract_id;
        $num = $installment->installment_number ?: '1';
        $total = $installment->total_installments ?: '1';
        $dueDate = $installment->due_date ? Carbon::parse($installment->due_date)->format('d/m/Y') : '-';
        $amount = number_format((float) ($installment->amount_paid > 0 ? $installment->amount_paid : $installment->amount), 2, ',', '.');

        $school = $installment->school
            ?? $installment->contract?->school
            ?? (class_exists(\Filament\Facades\Filament::class) ? \Filament\Facades\Filament::getTenant() : null)
            ?? School::getActiveSchool()
            ?? School::query()->first();
        $schoolName = $school?->trade_name ?: ($school?->legal_name ?: 'nossa escola');

        $pixData = $pixService->generateForInstallment($installment, $school);
        $pixCode = $pixData['payload'] ?? $pixData['copy_paste'] ?? '';

        $today = Carbon::today();
        $due = Carbon::parse($installment->due_date)->startOfDay();
        $daysLate = (int) $due->diffInDays($today, false);

        if ($daysLate <= 0) {
            // Lembrete preventivo
            $msg = "Olá, {$studentName}! Tudo bem?\n\nPassando para lembrar que a sua parcela {$num}/{$total} na {$schoolName} (Contrato #{$contractNumber}) vence em breve, no dia {$dueDate}.\nValor: R$ {$amount}\n\n";
        } elseif ($daysLate <= 10) {
            // Atraso leve
            $msg = "Olá, {$studentName}! Notamos que a parcela {$num}/{$total} na {$schoolName} (Contrato #{$contractNumber}) venceu no dia {$dueDate} e ainda consta em aberto.\nValor: R$ {$amount}\n\n";
        } else {
            // Atraso moderado/crítico
            $msg = "Olá, {$studentName}! Tudo bem? Consta uma pendência da parcela {$num}/{$total} na {$schoolName} vencida em {$dueDate}.\nValor atual: R$ {$amount}\nPodemos conversar para facilitar o pagamento ou negociar uma condição especial?\n\n";
        }

        if ($pixCode) {
            $msg .= "Chave Pix Copia e Cola para pagamento:\n{$pixCode}\n\n";
        }
        $msg .= "Caso já tenha efetuado o pagamento, por favor desconsidere este aviso. Obrigado!";

        $cleanPhone = $calcService->formatInternationalPhone($phone);
        $encodedMsg = rawurlencode($msg);
        $waUrl = "https://wa.me/{$cleanPhone}?text={$encodedMsg}";

        $this->js("window.open('{$waUrl}', '_blank');");
    }

    public function openFollowUpModal(int $contractId): void
    {
        $this->selectedContractId = $contractId;
        $this->followUpNote = '';
        $this->nextFollowUpDate = null;
        $this->showFollowUpModal = true;
    }

    public function saveFollowUp(): void
    {
        if (! $this->selectedContractId || trim($this->followUpNote) === '') {
            $this->showFollowUpModal = false;

            return;
        }

        ContractHistory::create([
            'contract_id' => $this->selectedContractId,
            'user_id' => auth()->id(),
            'type' => 'financial_follow_up',
            'occurred_at' => now(),
            'next_follow_up_at' => $this->nextFollowUpDate ? Carbon::parse($this->nextFollowUpDate) : null,
            'description' => trim($this->followUpNote),
        ]);

        $this->showFollowUpModal = false;
        $this->followUpNote = '';
        $this->selectedContractId = null;

        $this->loadCards();

        Notification::make()
            ->title('Contato Registrado')
            ->body('O acompanhamento de cobrança foi salvo com sucesso no histórico do contrato.')
            ->success()
            ->send();
    }

    public function openAgreementModal(int $contractId): void
    {
        $contract = Contract::with(['student', 'installments' => function ($q) {
            $q->whereIn('status', ['pending', 'partial'])->orderBy('due_date');
        }])->findOrFail($contractId);

        $this->agreementContractId = $contract->id;
        $this->agreementStudentName = $contract->student?->name ?? 'Aluno';
        $this->agreementContractNumber = $contract->contract_number;

        $this->agreementPendingInstallments = $contract->installments->map(fn ($inst) => [
            'id' => $inst->id,
            'installment_number' => $inst->installment_number,
            'total_installments' => $inst->total_installments,
            'amount' => (float) $inst->amount,
            'due_date' => Carbon::parse($inst->due_date)->format('d/m/Y'),
        ])->all();

        $this->agreementSelectedInstallmentIds = array_column($this->agreementPendingInstallments, 'id');
        $this->agreementDiscount = 0.0;
        $this->agreementDownPayment = 0.0;
        $this->agreementDownPaymentPaid = false;
        $this->agreementPaymentMethodId = \App\Models\PaymentMethod::first()?->id;
        $this->agreementInstallmentsCount = 1;
        $this->agreementFirstDueDate = Carbon::today()->addMonth()->toDateString();
        $this->agreementNotes = 'Acordo de renegociação firmado pela régua de cobrança.';

        $this->showAgreementModal = true;
    }

    public function getAgreementTotalOriginalProperty(): float
    {
        $sum = 0.0;
        foreach ($this->agreementPendingInstallments as $inst) {
            if (in_array($inst['id'], $this->agreementSelectedInstallmentIds)) {
                $sum += (float) $inst['amount'];
            }
        }

        return $sum;
    }

    public function getAgreementTotalAgreedProperty(): float
    {
        return max(0.0, $this->agreementTotalOriginal - (float) $this->agreementDiscount);
    }

    public function getAgreementRemainingBalanceProperty(): float
    {
        return max(0.0, $this->agreementTotalAgreed - (float) $this->agreementDownPayment);
    }

    public function getAgreementInstallmentValueProperty(): float
    {
        $count = max(1, $this->agreementInstallmentsCount);

        return round($this->agreementRemainingBalance / $count, 2);
    }

    public function saveAgreement(): void
    {
        if (! $this->agreementContractId || empty($this->agreementSelectedInstallmentIds)) {
            Notification::make()
                ->title('Atenção')
                ->body('Selecione ao menos uma parcela para renegociar.')
                ->warning()
                ->send();

            return;
        }

        try {
            $contract = Contract::findOrFail($this->agreementContractId);
            $service = app(\App\Services\DebtAgreementService::class);

            $agreement = $service->createAgreement($contract, [
                'installment_ids' => $this->agreementSelectedInstallmentIds,
                'discount_amount' => (float) $this->agreementDiscount,
                'down_payment_amount' => (float) $this->agreementDownPayment,
                'down_payment_paid' => (bool) $this->agreementDownPaymentPaid,
                'payment_method_id' => $this->agreementPaymentMethodId,
                'installments_count' => (int) $this->agreementInstallmentsCount,
                'first_due_date' => $this->agreementFirstDueDate,
                'notes' => $this->agreementNotes,
            ], auth()->user());

            $this->showAgreementModal = false;
            $this->loadCards();

            $printUrl = route('contracts.termo-acordo', [$contract, $agreement]);

            Notification::make()
                ->title('Acordo Formalizado!')
                ->body("Acordo nº {$agreement->agreement_number} gerado com sucesso. As parcelas foram renegociadas.")
                ->success()
                ->actions([
                    \Filament\Actions\Action::make('printTermo')
                        ->label('Imprimir Termo de Acordo')
                        ->icon('heroicon-o-printer')
                        ->url($printUrl, shouldOpenInNewTab: true),
                ])
                ->send();
        } catch (\Throwable $e) {
            if (app()->environment('testing')) {
                throw $e;
            }
            \Illuminate\Support\Facades\Log::error('Erro ao formalizar acordo: '.$e->getMessage().' em '.$e->getFile().':'.$e->getLine());
            Notification::make()
                ->title('Erro ao Formalizar Acordo')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}


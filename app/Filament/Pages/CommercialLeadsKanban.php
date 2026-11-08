<?php

namespace App\Filament\Pages;

use App\Enums\ContactTypeEnum;
use App\Enums\GenderEnum;
use App\Enums\RelationshipEnum;
use App\Filament\Resources\Contracts\Pages\CreateContracts;
use App\Models\Collaborator;
use App\Models\CommercialLead;
use App\Models\CommercialLossReason;
use App\Models\Contact;
use App\Models\Person;
use App\Models\Responsible;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use UnitEnum;

class CommercialLeadsKanban extends Page
{
    protected static bool $shouldRegisterNavigation = true;

    protected static string|UnitEnum|null $navigationGroup = 'Comercial';

    protected static ?string $navigationLabel = 'Funil de vendas (Kanban)';

    protected static ?string $title = 'Funil de vendas e captação de alunos';

    protected static ?string $slug = 'commercial-leads-kanban';

    protected static ?int $navigationSort = 2;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-view-columns';

    protected string $view = 'filament.pages.commercial-leads-kanban';

    public string $collaboratorId = '';

    public string $search = '';

    public string $period = '30_days';

    /** @var array<string, int> */
    public array $perStageLimits = [];

    public bool $showLossModal = false;

    public ?int $lossLeadId = null;

    public ?int $lossReasonId = null;

    public string $lossNotes = '';

    public bool $showEnrollModal = false;

    public ?int $enrollLeadId = null;

    public string $enrollLeadName = '';

    public string $enrollResponsibleMode = 'student';

    public string $enrollResponsibleName = '';

    public string $enrollResponsibleRelationship = 'legal_guardian';

    public string $enrollResponsiblePhone = '';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && ($user->hasRole('super_admin') || $user->can('View:CommercialLeadsKanban') || $user->can('ViewAny:CommercialLead'));
    }

    public function collaborators(): array
    {
        return Collaborator::query()
            ->with('person')
            ->get()
            ->mapWithKeys(fn (Collaborator $c): array => [
                $c->id => $c->person?->name ?? 'Colaborador sem nome',
            ])
            ->all();
    }

    public function stages(): array
    {
        return [
            'new' => [
                'label' => 'Novo contato',
                'color' => 'gray',
                'border' => 'border-gray-300 dark:border-gray-700',
                'bg' => 'bg-gray-50 dark:bg-gray-800/50',
                'badge' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
            ],
            'contacted' => [
                'label' => 'Em contato',
                'color' => 'info',
                'border' => 'border-sky-300 dark:border-sky-800',
                'bg' => 'bg-sky-50/50 dark:bg-sky-950/20',
                'badge' => 'bg-sky-100 text-sky-800 dark:bg-sky-900/50 dark:text-sky-300',
            ],
            'visit_scheduled' => [
                'label' => 'Visita agendada',
                'color' => 'purple',
                'border' => 'border-purple-300 dark:border-purple-800',
                'bg' => 'bg-purple-50/50 dark:bg-purple-950/20',
                'badge' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-300',
            ],
            'visited' => [
                'label' => 'Visita realizada',
                'color' => 'indigo',
                'border' => 'border-indigo-300 dark:border-indigo-800',
                'bg' => 'bg-indigo-50/50 dark:bg-indigo-950/20',
                'badge' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/50 dark:text-indigo-300',
            ],
            'proposal' => [
                'label' => 'Proposta / Negociação',
                'color' => 'warning',
                'border' => 'border-amber-300 dark:border-amber-800',
                'bg' => 'bg-amber-50/50 dark:bg-amber-950/20',
                'badge' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300',
            ],
            'enrolled' => [
                'label' => 'Matriculado / Contrato',
                'color' => 'success',
                'border' => 'border-emerald-300 dark:border-emerald-800',
                'bg' => 'bg-emerald-50/50 dark:bg-emerald-950/20',
                'badge' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300',
            ],
            'lost' => [
                'label' => 'Perdido / Desistência',
                'color' => 'danger',
                'border' => 'border-rose-300 dark:border-rose-800',
                'bg' => 'bg-rose-50/50 dark:bg-rose-950/20',
                'badge' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/50 dark:text-rose-300',
            ],
        ];
    }

    public function lossReasons(): array
    {
        $tenantId = Filament::getTenant()?->id;

        return CommercialLossReason::query()
            ->when($tenantId, fn ($q) => $q->where('school_id', $tenantId))
            ->where('active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public function loadMore(string $stageKey): void
    {
        $current = $this->perStageLimits[$stageKey] ?? 25;
        $this->perStageLimits[$stageKey] = $current + 25;
    }

    /**
     * @return array<string, int>
     */
    public function stageCounts(): array
    {
        $counts = [];
        foreach (array_keys($this->stages()) as $stage) {
            $query = CommercialLead::query()
                ->where('status', $stage)
                ->when(filled($this->collaboratorId), fn (Builder $q) => $q->where('collaborator_id', (int) $this->collaboratorId));

            if (filled($this->search)) {
                $term = trim($this->search);
                $query->where(fn (Builder $sub) => $sub
                    ->whereLike('name', "%{$term}%")
                    ->orWhereLike('phone', "%{$term}%")
                    ->orWhereLike('email', "%{$term}%")
                );
            } else {
                $query = $this->applyPeriodFilter($query, $stage);
            }

            $counts[$stage] = $query->count();
        }

        return $counts;
    }

    /**
     * @return Collection<string, Collection<int, CommercialLead>>
     */
    public function leadsByStage(): Collection
    {
        $grouped = collect();

        foreach (array_keys($this->stages()) as $stage) {
            $limit = $this->perStageLimits[$stage] ?? 25;

            $query = CommercialLead::query()
                ->where('status', $stage)
                ->with(['formation', 'collaborator.person', 'city', 'lossReason'])
                ->when(filled($this->collaboratorId), fn (Builder $q) => $q->where('collaborator_id', (int) $this->collaboratorId));

            if (filled($this->search)) {
                $term = trim($this->search);
                $query->where(fn (Builder $sub) => $sub
                    ->whereLike('name', "%{$term}%")
                    ->orWhereLike('phone', "%{$term}%")
                    ->orWhereLike('email', "%{$term}%")
                );
            } else {
                $query = $this->applyPeriodFilter($query, $stage);
            }

            $items = $query->latest('updated_at')
                ->limit($limit)
                ->get();

            $grouped->put($stage, $items);
        }

        return $grouped;
    }

    protected function applyPeriodFilter(Builder $query, string $stage): Builder
    {
        return match ($this->period) {
            '30_days' => $query->where('updated_at', '>=', now()->subDays(30)),
            '60_days' => $query->where('updated_at', '>=', now()->subDays(60)),
            'this_month' => $query->where('updated_at', '>=', now()->startOfMonth()),
            'active_only' => in_array($stage, ['enrolled', 'lost'], true) 
                ? $query->where('updated_at', '>=', now()->subDays(7)) 
                : $query,
            default => $query,
        };
    }

    public function updateLeadStatus(int $leadId, string $newStatus): void
    {
        $lead = CommercialLead::query()->find($leadId);

        if (! $lead) {
            return;
        }

        $stages = array_keys($this->stages());
        if (! in_array($newStatus, $stages, true)) {
            return;
        }

        if ($newStatus === 'lost') {
            $this->openLossModal($leadId);

            return;
        }

        $lead->status = $newStatus;
        if ($newStatus === 'enrolled' && $lead->converted_at === null) {
            $lead->converted_at = now();
        }

        $lead->save();

        $stageName = $this->stages()[$newStatus]['label'] ?? $newStatus;

        Notification::make()
            ->title('Lead movimentado!')
            ->body("{$lead->name} foi transferido(a) para a etapa: {$stageName}.")
            ->success()
            ->send();
    }

    public function openLossModal(int $leadId): void
    {
        $this->lossLeadId = $leadId;
        $this->lossReasonId = null;
        $this->lossNotes = '';
        $this->showLossModal = true;
    }

    public function closeLossModal(): void
    {
        $this->showLossModal = false;
        $this->lossLeadId = null;
        $this->lossReasonId = null;
        $this->lossNotes = '';
    }

    public function confirmLoss(): void
    {
        $this->validate([
            'lossReasonId' => ['required', 'integer'],
        ], [
            'lossReasonId.required' => 'Selecione o motivo da perda do interessado.',
        ]);

        if (! $this->lossLeadId) {
            return;
        }

        $lead = CommercialLead::query()->find($this->lossLeadId);
        if ($lead) {
            $lead->status = 'lost';
            $lead->commercial_loss_reason_id = $this->lossReasonId;
            $lead->loss_notes = filled($this->lossNotes) ? trim($this->lossNotes) : null;
            $lead->lost_at = now();
            $lead->save();

            Notification::make()
                ->title('Lead marcado como perdido')
                ->body("{$lead->name} foi movido para Desistência/Perdido com motivo registrado.")
                ->warning()
                ->send();
        }

        $this->closeLossModal();
    }

    public function relationships(): array
    {
        return RelationshipEnum::cases();
    }

    public function openEnrollModal(int $leadId): void
    {
        $lead = CommercialLead::query()->find($leadId);
        if (! $lead) {
            return;
        }

        $this->enrollLeadId = $lead->id;
        $this->enrollLeadName = $lead->name;
        $this->enrollResponsibleMode = filled($lead->responsible_name) ? 'other' : 'student';
        $this->enrollResponsibleName = $lead->responsible_name ?? '';
        $this->enrollResponsibleRelationship = $lead->responsible_relationship?->value ?? $lead->responsible_relationship ?? RelationshipEnum::Legal_guardian->value;
        $this->enrollResponsiblePhone = $lead->responsible_phone ?? '';
        $this->showEnrollModal = true;
    }

    public function closeEnrollModal(): void
    {
        $this->showEnrollModal = false;
        $this->enrollLeadId = null;
        $this->enrollLeadName = '';
        $this->enrollResponsibleMode = 'student';
        $this->enrollResponsibleName = '';
        $this->enrollResponsibleRelationship = 'legal_guardian';
        $this->enrollResponsiblePhone = '';
    }

    public function enrollLead(int $leadId)
    {
        return $this->openEnrollModal($leadId);
    }

    public function confirmEnroll()
    {
        if (! $this->enrollLeadId) {
            return null;
        }

        $lead = CommercialLead::query()->find($this->enrollLeadId);
        if (! $lead) {
            return null;
        }

        if ($this->enrollResponsibleMode === 'other') {
            $this->validate([
                'enrollResponsibleName' => ['required', 'string', 'max:255'],
                'enrollResponsibleRelationship' => ['required', 'string'],
            ], [
                'enrollResponsibleName.required' => 'Informe o nome do responsável.',
                'enrollResponsibleRelationship.required' => 'Selecione o grau de parentesco.',
            ]);
        }

        $tenantId = Filament::getTenant()?->id ?? $lead->school_id;

        // 1. Aluno
        $studentPerson = $lead->person;
        if (! $studentPerson) {
            $studentPerson = Person::create([
                'name' => $lead->name,
                'email' => $lead->email,
                'birth_date' => $lead->birth_date,
                'gender' => $lead->gender,
            ]);

            if (filled($lead->phone)) {
                Contact::create([
                    'person_id' => $studentPerson->id,
                    'type' => ContactTypeEnum::Celular,
                    'value' => $lead->phone,
                    'is_primary' => true,
                ]);
            }

            $lead->forceFill(['person_id' => $studentPerson->id])->saveQuietly();
        } else {
            if (! $studentPerson->gender && $lead->gender) {
                $studentPerson->update(['gender' => $lead->gender]);
            }
        }

        if ($tenantId) {
            $studentPerson->schools()->syncWithoutDetaching([$tenantId]);
        }

        // 2. Responsável Financeiro
        if ($this->enrollResponsibleMode === 'other' && filled($this->enrollResponsibleName)) {
            $respPerson = Person::create([
                'name' => trim($this->enrollResponsibleName),
            ]);

            if (filled($this->enrollResponsiblePhone)) {
                Contact::create([
                    'person_id' => $respPerson->id,
                    'type' => ContactTypeEnum::Celular,
                    'value' => trim($this->enrollResponsiblePhone),
                    'is_primary' => true,
                ]);
            }

            if ($tenantId) {
                $respPerson->schools()->syncWithoutDetaching([$tenantId]);
            }

            $relEnum = RelationshipEnum::tryFrom($this->enrollResponsibleRelationship) ?? RelationshipEnum::Legal_guardian;

            $responsible = Responsible::create([
                'person_id' => $respPerson->id,
                'financial_responsible' => true,
                'relationship' => $relEnum,
            ]);
        } else {
            $responsible = Responsible::firstOrCreate(
                [
                    'person_id' => $studentPerson->id,
                    'financial_responsible' => true,
                ],
                [
                    'relationship' => RelationshipEnum::Legal_guardian,
                ]
            );
        }

        $this->closeEnrollModal();

        return redirect()->to(CreateContracts::getUrl([
            'student_id' => $studentPerson->id,
            'responsible_id' => $responsible->id,
            'commercial_lead_id' => $lead->id,
            'collaborator_id' => $lead->collaborator_id,
        ]));
    }
}

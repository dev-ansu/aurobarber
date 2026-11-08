<?php

namespace App\Filament\Resources\People\Pages;

use App\Enums\ContactTypeEnum;
use App\Enums\RelationshipEnum;
use App\Filament\Resources\Collaborators\CollaboratorResource;
use App\Filament\Resources\Contracts\ContractsResource;
use App\Filament\Resources\People\PersonResource;
use App\Models\Person;
use App\Models\Responsible;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;

class EditPerson extends EditRecord
{
    protected static string $resource = PersonResource::class;

    protected function resolveRecord(int|string $key): \Illuminate\Database\Eloquent\Model
    {
        $tenant = Filament::getTenant();

        // Se a pessoa existe no banco (mesmo sem vínculo prévio de escola ou criada em outro fluxo),
        // garante o auto-vínculo com a escola/tenant atual para evitar 404 na restrição de escopo.
        $person = Person::withoutGlobalScopes()->find($key);

        if ($person && $tenant) {
            $person->schools()->syncWithoutDetaching([$tenant->getKey()]);
        }

        if (! $person) {
            Notification::make()
                ->title('Pessoa não localizada')
                ->body('O registro desta pessoa não existe mais ou foi excluído. Retornando para a lista do LMS...')
                ->warning()
                ->persistent()
                ->send();

            $this->redirect(route('filament.admin.pages.ouro-moderno-lms-students', array_filter([
                'tenant' => $tenant?->getKey(),
            ])));
        }

        return parent::resolveRecord($key);
    }

    public function mount(int|string $record): void
    {
        parent::mount($record);

        if (request()->query('flow') === 'lms_enrollment') {
            Notification::make()
                ->title('Aluno importado da Ouro Moderno!')
                ->body('Revise os dados pessoais e utilize as abas abaixo para cadastrar Contatos (WhatsApp), Documentos e Endereços. Quando concluir, clique no botão "Criar Contrato" no topo.')
                ->info()
                ->persistent()
                ->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', [
            'record' => $this->record,
        ]);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $responsible = $this->record->responsibles()->where('financial_responsible', true)->first()
            ?? $this->record->responsibles()->first();

        if ($responsible) {
            $data['is_responsible'] = (bool) $responsible->financial_responsible;
            $data['responsible_relationship'] = $responsible->relationship?->value ?? $responsible->relationship;
        } else {
            $data['is_responsible'] = false;
            $data['responsible_relationship'] = null;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $isResponsible = (bool) ($this->data['is_responsible'] ?? false);
        $relationship = $this->data['responsible_relationship'] ?? null;

        $responsible = $this->record->responsibles()->where('financial_responsible', true)->first()
            ?? $this->record->responsibles()->first();

        if ($isResponsible) {
            if ($responsible) {
                $responsible->update([
                    'financial_responsible' => true,
                    'relationship' => $relationship ?: ($responsible->relationship ?? RelationshipEnum::Legal_guardian),
                ]);
            } else {
                Responsible::create([
                    'person_id' => $this->record->id,
                    'financial_responsible' => true,
                    'relationship' => $relationship ?: RelationshipEnum::Legal_guardian,
                ]);
            }
        } else {
            if ($responsible) {
                if ($responsible->contracts()->exists()) {
                    $responsible->update(['financial_responsible' => false]);
                } else {
                    $responsible->delete();
                }
            }
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createContract')
                ->label('Criar Contrato')
                ->icon('heroicon-o-document-plus')
                ->color('primary')
                ->visible(fn (): bool => ContractsResource::canCreate() || (request()->query('flow') === 'lms_enrollment' && method_exists(auth()->user(), 'canManageLmsOnboarding') && auth()->user()->canManageLmsOnboarding()))
                ->modalHeading('Definir Responsável Financeiro e Iniciar Contrato')
                ->modalDescription(fn (): string => "Defina quem responderá financeiramente pelo contrato de {$this->record->name}.")
                ->modalSubmitActionLabel('Prosseguir para o Contrato')
                ->fillForm(function (): array {
                    $currentResp = $this->record->responsibles()->where('financial_responsible', true)->first();

                    return [
                        'responsible_person_id' => $currentResp?->person_id ?? $this->record->id,
                        'relationship' => $currentResp?->relationship?->value ?? RelationshipEnum::Legal_guardian->value,
                    ];
                })
                ->form([
                    Select::make('responsible_person_id')
                        ->label('Pessoa que será o Responsável Financeiro')
                        ->helperText('Por padrão o próprio aluno está selecionado. Você pode pesquisar qualquer outra pessoa já cadastrada na escola.')
                        ->options(function (): array {
                            $tenantId = Filament::getTenant()?->id;

                            $options = [
                                $this->record->id => "👤 {$this->record->name} (O próprio aluno)" . ($this->record->cpf ? " - CPF: {$this->record->cpf}" : ''),
                            ];

                            $others = Person::query()
                                ->withoutGlobalScopes()
                                ->where('id', '!=', $this->record->id)
                                ->when($tenantId, fn ($q) => $q->whereHas('schools', fn ($sq) => $sq->whereKey($tenantId)))
                                ->orderBy('name')
                                ->get();

                            foreach ($others as $p) {
                                $options[$p->id] = "{$p->name}" . ($p->cpf ? " - CPF: {$p->cpf}" : '');
                            }

                            return $options;
                        })
                        ->searchable()
                        ->required(),

                    Select::make('relationship')
                        ->label('Grau de Parentesco')
                        ->options(RelationshipEnum::class)
                        ->default(RelationshipEnum::Legal_guardian->value)
                        ->native(false)
                        ->required(),

                    Section::make('Ou Cadastrar Nova Pessoa como Responsável')
                        ->schema([
                            TextInput::make('new_person_name')
                                ->label('Nome Completo da Nova Pessoa')
                                ->placeholder('Preencha apenas se for cadastrar uma nova pessoa...'),
                            TextInput::make('new_person_cpf')
                                ->label('CPF')
                                ->mask('999.999.999-99'),
                            TextInput::make('new_person_phone')
                                ->label('Telefone / WhatsApp')
                                ->tel(),
                            TextInput::make('new_person_email')
                                ->label('E-mail')
                                ->email(),
                        ])
                        ->columns(2),
                ])
                ->extraModalFooterActions([
                    Action::make('fullPersonForm')
                        ->label('Abrir Cadastro Completo de Pessoa')
                        ->color('gray')
                        ->icon('heroicon-o-user-plus')
                        ->url(fn (): string => route('filament.admin.resources.people.create', [
                            'for_student_id' => $this->record->id,
                        ])),
                ])
                ->action(function (array $data): void {
                    $tenant = Filament::getTenant();
                    $rawRel = $data['relationship'] ?? null;
                    $relationship = $rawRel instanceof RelationshipEnum
                        ? $rawRel
                        : (is_string($rawRel) || is_int($rawRel) ? RelationshipEnum::tryFrom($rawRel) : null)
                            ?? RelationshipEnum::Legal_guardian;

                    $targetPerson = null;

                    // 1. Se informou nome nos campos de Nova Pessoa:
                    if (filled($data['new_person_name'] ?? null)) {
                        $rawCpf = $data['new_person_cpf'] ?? null;
                        $cleanCpf = filled($rawCpf) ? preg_replace('/\D+/', '', (string) $rawCpf) : null;
                        $email = filled($data['new_person_email'] ?? null) ? trim((string) $data['new_person_email']) : null;

                        if ($cleanCpf) {
                            $targetPerson = Person::withoutGlobalScopes()
                                ->whereCpf($cleanCpf ?: $rawCpf)
                                ->first();
                        }
                        if (! $targetPerson && $email) {
                            $targetPerson = Person::withoutGlobalScopes()
                                ->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])
                                ->first();
                        }

                        if (! $targetPerson) {
                            $targetPerson = Person::create([
                                'name' => trim($data['new_person_name']),
                                'cpf' => $cleanCpf ?: $rawCpf,
                                'email' => $email,
                            ]);
                        }

                        if (filled($data['new_person_phone'] ?? null)) {
                            $targetPerson->contacts()->firstOrCreate(
                                ['type' => ContactTypeEnum::WhatsApp],
                                ['value' => trim($data['new_person_phone']), 'description' => 'Contato cadastrado no fluxo de matrícula']
                            );
                        }
                    } elseif (! empty($data['responsible_person_id'])) {
                        // 2. Se selecionou no dropdown (próprio aluno ou outra pessoa já cadastrada):
                        $targetPerson = Person::withoutGlobalScopes()->find($data['responsible_person_id']);
                    }

                    if (! $targetPerson) {
                        $targetPerson = $this->record;
                    }

                    // Garante que a pessoa responsável esteja vinculada à unidade escolar atual
                    if ($tenant) {
                        $targetPerson->schools()->syncWithoutDetaching([$tenant->id]);
                    }

                    // Vincula na tabela responsibles
                    $responsible = Responsible::firstOrCreate(
                        [
                            'person_id' => $targetPerson->id,
                            'financial_responsible' => true,
                        ],
                        [
                            'relationship' => $relationship,
                        ]
                    );

                    if ($responsible->relationship !== $relationship) {
                        $responsible->update(['relationship' => $relationship]);
                    }

                    $createUrl = route('filament.admin.resources.contracts.create', array_filter([
                        'tenant' => $tenant?->slug ?? $tenant?->id,
                        'student_id' => $this->record->id,
                        'responsible_id' => $responsible->id,
                    ]));

                    $this->redirect($createUrl);
                }),

            Action::make('createCollaborator')
                ->label('Criar Colaborador')
                ->icon('heroicon-o-briefcase')
                ->color('info')
                ->visible(fn (): bool => CollaboratorResource::canCreate())
                ->url(fn (): string => route('filament.admin.resources.collaborators.create', [
                    'person_id' => $this->record->id,
                ])),

            ViewAction::make(),
            DeleteAction::make()
                ->before(function (DeleteAction $action): void {
                    /** @var Person $record */
                    $record = $this->getRecord();
                    if ($reason = $record->getDeletionBlockReason()) {
                        Notification::make()
                            ->danger()
                            ->title('Não foi possível excluir')
                            ->body($reason)
                            ->persistent()
                            ->send();

                        $action->halt();
                    }
                }),
        ];
    }
}

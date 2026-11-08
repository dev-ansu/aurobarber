<?php

namespace App\Filament\Resources\People\Pages;

use App\Enums\RelationshipEnum;
use App\Filament\Resources\People\PersonResource;
use App\Models\Person;
use App\Models\Responsible;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePerson extends CreateRecord
{
    protected static string $resource = PersonResource::class;

    public ?string $forStudentId = null;

    public ?string $relationshipParam = null;

    protected function handleRecordCreation(array $data): Model
    {
        $tenant = Filament::getTenant();
        $rawCpf = $data['cpf'] ?? null;
        $cleanCpf = filled($rawCpf) ? preg_replace('/[^0-9]/', '', (string) $rawCpf) : null;
        $cleanEmail = filled($data['email'] ?? null) ? trim((string) $data['email']) : null;

        $existing = null;
        if ($cleanCpf) {
            $existing = Person::query()
                ->whereCpf($cleanCpf ?: $rawCpf)
                ->first();
        }
        if (! $existing && $cleanEmail) {
            $existing = Person::query()->where('email', $cleanEmail)->first();
        }

        if ($existing) {
            $fieldsToUpdate = array_filter([
                'name' => $data['name'] ?? null,
                'birth_date' => $data['birth_date'] ?? null,
                'social_name' => $data['social_name'] ?? null,
                'gender' => $data['gender'] ?? null,
            ], fn ($v) => filled($v));

            if (! empty($fieldsToUpdate)) {
                $existing->update($fieldsToUpdate);
            }

            if ($tenant) {
                $existing->schools()->syncWithoutDetaching([$tenant->id]);
            }

            Notification::make()
                ->title('Pessoa vinculada da rede!')
                ->body("{$existing->name} já possuía cadastro na rede e foi vinculada com sucesso a esta unidade.")
                ->success()
                ->send();

            return $existing;
        }

        $record = parent::handleRecordCreation($data);

        if ($tenant) {
            $record->schools()->syncWithoutDetaching([$tenant->id]);
        }

        return $record;
    }

    public function mount(): void
    {
        parent::mount();

        $this->forStudentId = filled(request()->query('for_student_id')) ? (string) request()->query('for_student_id') : null;
        $this->relationshipParam = filled(request()->query('relationship')) ? (string) request()->query('relationship') : null;

        if ($this->forStudentId) {
            $student = Person::withoutGlobalScopes()->find($this->forStudentId);
            if ($student) {
                Notification::make()
                    ->title('Cadastrando Responsável Financeiro')
                    ->body("Cadastre os dados de quem responderá financeiramente por {$student->name}. Ao salvar, você será direcionado para a emissão do Contrato com ambos vinculados.")
                    ->info()
                    ->persistent()
                    ->send();
            }
        }
    }

    protected function afterCreate(): void
    {
        $forStudentId = $this->forStudentId ?: request()->query('for_student_id');
        $isResponsible = (bool) ($this->data['is_responsible'] ?? false);
        $relationship = $this->data['responsible_relationship'] ?? null;

        if ($forStudentId) {
            $relParam = $this->relationshipParam ?: request()->query('relationship');
            $relEnum = $relParam ? (RelationshipEnum::tryFrom($relParam) ?? RelationshipEnum::Legal_guardian) : RelationshipEnum::Legal_guardian;

            Responsible::firstOrCreate(
                [
                    'person_id' => $this->record->id,
                    'financial_responsible' => true,
                ],
                [
                    'relationship' => $relEnum,
                ]
            );
        } elseif ($isResponsible) {
            Responsible::create([
                'person_id' => $this->record->id,
                'financial_responsible' => true,
                'relationship' => $relationship ?: RelationshipEnum::Legal_guardian,
            ]);
        }
    }

    protected function getRedirectUrl(): string
    {
        $forStudentId = $this->forStudentId ?: request()->query('for_student_id');
        if ($forStudentId) {
            $resp = Responsible::where('person_id', $this->record->id)->first();

            return route('filament.admin.resources.contracts.create', array_filter([
                'tenant' => Filament::getTenant()?->slug ?? Filament::getTenant()?->id,
                'student_id' => $forStudentId,
                'responsible_id' => $resp?->id,
            ]));
        }

        if (request()->query('redirect_to_contract') || request()->boolean('redirect_to_contract')) {
            return route('filament.admin.resources.contracts.create', [
                'student_id' => $this->record->id,
            ]);
        }

        // Ao salvar a nova pessoa, abre imediatamente na tela de edição (onde aparecem contatos, endereços e documentos)
        return $this->getResource()::getUrl('edit', [
            'record' => $this->record,
        ]);
    }
}

<?php

namespace App\Filament\Resources\People\Pages;

use App\Filament\Resources\People\PersonResource;
use App\Models\Contract;
use App\Models\Person;
use App\Models\School;
use App\Services\SchoolTransferService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Utilities\Get;

class ListPeople extends ListRecords
{
    protected static string $resource = PersonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('transferToAnotherSchool')
                ->label('Transferir para outra Unidade')
                ->icon('heroicon-o-arrows-right-left')
                ->color('info')
                ->visible(fn (): bool => School::query()->withoutGlobalScopes()->count() > 1)
                ->modalHeading('Transferir Pessoa e Vínculos para outra Unidade')
                ->modalDescription('Selecione uma pessoa desta unidade para transferir seu cadastro para outra unidade da rede. Caso ela possua contratos vinculados, você poderá escolher se deseja transferir os contratos e parcelas em aberto juntos.')
                ->modalSubmitActionLabel('Confirmar Transferência')
                ->schema([
                    Select::make('person_id')
                        ->label('Pessoa / Aluno desta Unidade')
                        ->searchable()
                        ->preload()
                        ->live()
                        ->options(function (): array {
                            $tenant = Filament::getTenant();
                            $tenantId = $tenant?->id;

                            return Person::query()
                                ->when($tenantId, function ($q) use ($tenantId) {
                                    $q->where(function ($sub) use ($tenantId) {
                                        $sub->whereHas('schools', fn ($sq) => $sq->whereKey($tenantId))
                                            ->orWhereHas('contracts', fn ($cq) => $cq->withoutGlobalScopes()->where('school_id', $tenantId));
                                    });
                                })
                                ->orderBy('name')
                                ->limit(100)
                                ->get()
                                ->mapWithKeys(fn (Person $p): array => [
                                    $p->id => $p->name.($p->cpf ? " • CPF: {$p->cpf}" : ''),
                                ])
                                ->all();
                        })
                        ->getSearchResultsUsing(function (string $search): array {
                            $tenant = Filament::getTenant();
                            $tenantId = $tenant?->id;

                            return Person::query()
                                ->when($tenantId, function ($q) use ($tenantId) {
                                    $q->where(function ($sub) use ($tenantId) {
                                        $sub->whereHas('schools', fn ($sq) => $sq->whereKey($tenantId))
                                            ->orWhereHas('contracts', fn ($cq) => $cq->withoutGlobalScopes()->where('school_id', $tenantId));
                                    });
                                })
                                ->where(function ($q) use ($search) {
                                    $q->where('name', 'like', "%{$search}%")
                                        ->orWhere('email', 'like', "%{$search}%")
                                        ->orWhereCpf($search);
                                })
                                ->orderBy('name')
                                ->limit(30)
                                ->get()
                                ->mapWithKeys(fn (Person $p): array => [
                                    $p->id => $p->name.($p->cpf ? " • CPF: {$p->cpf}" : ''),
                                ])
                                ->all();
                        })
                        ->required()
                        ->helperText('Apenas pessoas vinculadas a esta unidade aparecem para envio.'),

                    Radio::make('transfer_contracts')
                        ->label('⚠️ Esta pessoa possui Contrato(s) vinculado(s) nesta unidade. O que deseja transferir?')
                        ->options([
                            '1' => 'Transferir TUDO: Pessoa + Contratos, Progresso LMS e Financeiro em aberto (parcelas já pagas ficam na origem)',
                            '0' => 'Transferir APENAS o cadastro da Pessoa (manter contratos e financeiro na unidade atual)',
                        ])
                        ->default('1')
                        ->required()
                        ->visible(function (Get $get): bool {
                            $personId = $get('person_id');
                            if (! $personId) {
                                return false;
                            }
                            $tenantId = Filament::getTenant()?->id;

                            return Contract::query()
                                ->withoutGlobalScopes()
                                ->where('student_id', $personId)
                                ->when($tenantId, fn ($q) => $q->where('school_id', $tenantId))
                                ->exists();
                        }),

                    Select::make('target_school_id')
                        ->label('Unidade de Destino')
                        ->options(function (): array {
                            $tenantId = Filament::getTenant()?->id;

                            return School::query()
                                ->withoutGlobalScopes()
                                ->when($tenantId, fn ($q) => $q->where('id', '!=', $tenantId))
                                ->orderBy('trade_name')
                                ->get()
                                ->mapWithKeys(fn (School $s): array => [$s->id => $s->getFilamentName()])
                                ->all();
                        })
                        ->required()
                        ->native(false)
                        ->searchable(),

                    Textarea::make('reason')
                        ->label('Motivo da Transferência / Observações')
                        ->placeholder('Ex: Aluno mudou de bairro e continuará os estudos na unidade de destino.')
                        ->rows(2),

                    Toggle::make('keep_origin_link')
                        ->label('Manter cadastro vinculado também nesta unidade de origem (compartilhar)')
                        ->helperText('Se desmarcado (padrão), a pessoa será transferida definitivamente para a unidade de destino.')
                        ->default(false),

                    Toggle::make('send_notification')
                        ->label('Notificar a unidade de destino na Comunicação da Rede')
                        ->default(true),
                ])
                ->action(function (array $data): void {
                    $originSchool = Filament::getTenant() instanceof School ? Filament::getTenant() : null;
                    $person = Person::query()->withoutGlobalScopes()->findOrFail($data['person_id']);
                    $targetSchool = School::query()->withoutGlobalScopes()->findOrFail($data['target_school_id']);
                    $transferContracts = ! isset($data['transfer_contracts']) || (string) $data['transfer_contracts'] === '1';

                    app(SchoolTransferService::class)->transferPerson(
                        person: $person,
                        targetSchool: $targetSchool,
                        originSchool: $originSchool,
                        reason: $data['reason'] ?? null,
                        keepOriginPersonLink: (bool) ($data['keep_origin_link'] ?? false),
                        sendNotificationMessage: (bool) ($data['send_notification'] ?? true),
                        transferContracts: $transferContracts,
                    );

                    Notification::make()
                        ->title('Transferência concluída com sucesso!')
                        ->body($transferContracts
                            ? "{$person->name} (junto com seus contratos, progresso LMS e financeiro em aberto) foi transferido(a) para {$targetSchool->getFilamentName()}."
                            : "O cadastro de {$person->name} foi transferido/vinculado à unidade {$targetSchool->getFilamentName()} (contratos mantidos na origem).")
                        ->success()
                        ->send();
                }),
            CreateAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\Contracts\Pages;

use App\Filament\Resources\Contracts\ContractsResource;
use App\Filament\Resources\Contracts\Widgets\ContractsStatsOverview;
use App\Models\Contract;
use App\Models\School;
use App\Services\SchoolTransferService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;

class ListContracts extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = ContractsResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            ContractsStatsOverview::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('transferContractHeader')
                ->label('Transferir para outra Unidade')
                ->icon('heroicon-o-arrows-right-left')
                ->color('info')
                ->visible(fn (): bool => School::query()->withoutGlobalScopes()->count() > 1)
                ->modalHeading('Transferir Contrato, Aluno e Responsável para outra Unidade')
                ->modalDescription('Selecione o contrato desta unidade que será transferido para outra escola da rede. O aluno, seu responsável financeiro, progresso no LMS e parcelas acompanharão o contrato.')
                ->modalSubmitActionLabel('Confirmar Transferência')
                ->schema([
                    Select::make('contract_id')
                        ->label('Contrato / Aluno desta Unidade')
                        ->options(fn (): array => Contract::query()
                            ->with('student')
                            ->orderByDesc('id')
                            ->limit(200)
                            ->get()
                            ->mapWithKeys(fn (Contract $c): array => [
                                $c->id => "#{$c->contract_number} — " . ($c->student?->name ?? 'Aluno'),
                            ])
                            ->all())
                        ->searchable()
                        ->required(),
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
                        ->label('Motivo / Observações para a unidade de destino')
                        ->rows(2),
                    Toggle::make('keep_origin_link')
                        ->label('Manter cadastro da pessoa vinculado também nesta unidade de origem')
                        ->default(false),
                    Toggle::make('send_notification')
                        ->label('Notificar a unidade de destino na Comunicação da Rede')
                        ->default(true),
                ])
                ->action(function (array $data): void {
                    $contract = Contract::query()->findOrFail($data['contract_id']);
                    $targetSchool = School::query()->withoutGlobalScopes()->findOrFail($data['target_school_id']);

                    app(SchoolTransferService::class)->transferContract(
                        contract: $contract,
                        targetSchool: $targetSchool,
                        reason: $data['reason'] ?? null,
                        keepOriginPersonLink: (bool) ($data['keep_origin_link'] ?? false),
                        sendNotificationMessage: (bool) ($data['send_notification'] ?? true),
                    );

                    Notification::make()
                        ->title('Contrato, aluno e responsável transferidos!')
                        ->body("O contrato #{$contract->contract_number} foi transferido para a unidade {$targetSchool->getFilamentName()}.")
                        ->success()
                        ->send();
                }),
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Todos')
                ->icon('heroicon-m-squares-2x2')
                ->badge(fn () => Contract::query()->count() ?: null),

            'active' => Tab::make('Ativos')
                ->icon('heroicon-m-check-circle')
                ->badge(fn () => Contract::query()->where('status', 'active')->count() ?: null)
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'active')),

            'pending_completion' => Tab::make('Aguardando Conclusão')
                ->icon('heroicon-m-academic-cap')
                ->badge(
                    fn () => Contract::query()
                        ->where('status', 'active')
                        ->whereHas('items')
                        ->whereDoesntHave('items', fn ($q) => $q->whereNull('formation_completed_at'))
                        ->count() ?: null
                )
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('status', 'active')
                    ->whereHas('items')
                    ->whereDoesntHave('items', fn ($q) => $q->whereNull('formation_completed_at'))
                ),

            'finished' => Tab::make('Finalizados')
                ->icon('heroicon-m-flag')
                ->badge(fn () => Contract::query()->where('status', 'finished')->count() ?: null)
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'finished')),
        ];
    }

    #[On('ativarContratoCriado')]
    public function ativarContratoCriado(int $contractId): void
    {
        $contract = Contract::find($contractId);

        if ($contract && $contract->status === 'draft') {
            $contract->changeStatus('active', 'Ativação inicial do contrato via notificação pós-criação.');

            Notification::make()
                ->title('Contrato Ativado!')
                ->body("O contrato {$contract->contract_number} agora está ATIVO.")
                ->success()
                ->send();
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Resources\SupportTickets\Pages;

use App\Filament\Resources\SupportTickets\SupportTicketResource;
use App\Models\SupportTicket;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Colors\Color;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ManageSupportTickets extends ManageRecords
{
    protected static string $resource = SupportTicketResource::class;

    public function getTabs(): array
    {
        $schoolId = \Filament\Facades\Filament::getTenant()?->id;

        return [
            'students' => Tab::make('Alunos da Escola')
                ->icon('heroicon-m-user-group')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('type', 'student_support'))
                ->badge(function () use ($schoolId) {
                    $query = SupportTicket::query()
                        ->where('type', 'student_support')
                        ->whereIn('status', ['open', 'in_progress']);
                    if ($schoolId) {
                        $query->where('school_id', $schoolId);
                    }
                    return $query->count();
                })
                ->badgeColor('warning'),

            'saas' => Tab::make('Meus Chamados à Matriz SaaS')
                ->icon('heroicon-m-lifebuoy')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('type', 'school_support'))
                ->badge(function () use ($schoolId) {
                    $query = SupportTicket::query()
                        ->where('type', 'school_support')
                        ->whereIn('status', ['open', 'in_progress', 'waiting_user']);
                    if ($schoolId) {
                        $query->where('school_id', $schoolId);
                    }
                    return $query->count();
                })
                ->badgeColor('primary'),

            'all' => Tab::make('Todos os Chamados')
                ->icon('heroicon-m-list-bullet'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('request_saas_support')
                ->label('🆘 Solicitar Suporte ao Aurosoft SaaS (Matriz)')
                ->color(Color::Amber)
                ->icon('heroicon-o-lifebuoy')
                ->modalHeading('Abrir Chamado com a Equipe Aurosoft SaaS')
                ->modalDescription('Precisa de ajuda com o sistema, dúvidas de configuração ou quer relatar um problema técnico à matriz?')
                ->modalWidth('2xl')
                ->form([
                    Grid::make(2)->schema([
                        Select::make('category')
                            ->label('Assunto Principal')
                            ->options([
                                'duvida_saas' => 'Dúvida Operacional do Sistema',
                                'sistema_bug' => 'Relato de Falha / Bug',
                                'financeiro' => 'Faturamento SaaS / Licença',
                                'ouro_moderno' => 'Integração Ouro Moderno',
                                'melhoria' => 'Sugestão de Nova Funcionalidade',
                            ])
                            ->default('duvida_saas')
                            ->required(),
                        Select::make('priority')
                            ->label('Urgência')
                            ->options([
                                'low' => 'Baixa (Pode aguardar)',
                                'medium' => 'Média (Dúvida comum)',
                                'high' => 'Alta (Impactando atendimento)',
                                'critical' => 'Crítica (Sistema inoperante)',
                            ])
                            ->default('medium')
                            ->required(),
                    ])->columnSpanFull(),
                    TextInput::make('subject')
                        ->label('Título Resumido')
                        ->placeholder('Ex: Dificuldade na importação de notas...')
                        ->required()
                        ->maxLength(150)
                        ->columnSpanFull(),
                    Textarea::make('description')
                        ->label('Descrição Detalhada')
                        ->rows(5)
                        ->placeholder('Explique com clareza o que aconteceu ou o que você precisa...')
                        ->required()
                        ->columnSpanFull(),
                ])
                ->action(function (array $data) {
                    $school = \Filament\Facades\Filament::getTenant();

                    $ticket = SupportTicket::create([
                        'type' => 'school_support',
                        'category' => $data['category'],
                        'priority' => $data['priority'],
                        'status' => 'open',
                        'school_id' => $school?->id,
                        'opened_by_user_id' => Auth::id(),
                        'subject' => $data['subject'],
                        'description' => $data['description'],
                    ]);

                    // Notifica a Matriz SaaS (Anderson) no sino do painel Master
                    try {
                        $schoolName = $school?->trade_name ?: ($school?->legal_name ?? 'Escola Parceira');
                        $superAdmins = \App\Models\User::withoutGlobalScopes()
                            ->where(function ($q) {
                                $q->where('is_super_admin', true)->orWhere('is_admin', true);
                            })
                            ->get();

                        foreach ($superAdmins as $admin) {
                            $notif = Notification::make()
                                ->title("Novo Chamado da Escola: {$ticket->protocol}")
                                ->body("{$schoolName}: {$ticket->subject}")
                                ->icon('heroicon-o-lifebuoy')
                                ->color('primary')
                                ->actions([
                                    \Filament\Notifications\Actions\Action::make('view')
                                        ->label('Atender Escola')
                                        ->url(url('/tickets-and-incidents')),
                                ]);
                            $admin->notifyNow($notif->toDatabase());
                        }
                    } catch (\Throwable) {
                    }

                    Notification::make()
                        ->title('Chamado enviado com sucesso para a Matriz Aurosoft!')
                        ->body('Nossa equipe técnica e pedagógica já recebeu sua solicitação.')
                        ->success()
                        ->send();
                }),
        ];
    }
}

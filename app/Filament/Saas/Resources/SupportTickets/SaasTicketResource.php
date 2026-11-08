<?php

declare(strict_types=1);

namespace App\Filament\Saas\Resources\SupportTickets;

use App\Filament\Saas\Resources\SupportTickets\Pages\ManageSaasTickets;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class SaasTicketResource extends Resource
{
    protected static ?string $model = SupportTicket::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-lifebuoy';

    protected static ?string $navigationLabel = 'Chamados & Incidentes';

    protected static ?string $modelLabel = 'ticket';

    protected static ?string $pluralModelLabel = 'chamados e incidentes';

    protected static ?string $slug = 'tickets-and-incidents';

    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::query()
            ->withoutGlobalScopes()
            ->where('type', 'system_incident')
            ->whereIn('status', ['open', 'in_progress'])
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getEloquentQuery(): Builder
    {
        // No painel Saas/Master exibe tanto chamados de escolas quanto incidentes capturados pelo sistema
        return parent::getEloquentQuery()
            ->withoutGlobalScopes()
            ->whereIn('type', ['school_support', 'system_incident'])
            ->latest('updated_at');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identificação do Registro')
                ->schema([
                    Grid::make(3)->schema([
                        Placeholder::make('protocol')
                            ->label('Protocolo')
                            ->content(fn ($record) => $record?->protocol ?? '-'),
                        Placeholder::make('type')
                            ->label('Origem / Tipo')
                            ->content(fn ($record) => $record?->type === 'system_incident' ? '🛡️ Incidente do Sistema (Telemetria)' : '🏫 Suporte da Escola'),
                        Placeholder::make('school_name')
                            ->label('Escola Solicitante')
                            ->content(fn ($record) => $record?->school?->trade_name ?: ($record?->school?->legal_name ?? 'Sistema Geral')),
                    ]),
                    Grid::make(3)->schema([
                        Select::make('category')
                            ->label('Categoria')
                            ->options([
                                'duvida_saas' => 'Dúvida Operacional',
                                'sistema_bug' => 'Falha Técnica / Bug',
                                'financeiro' => 'Faturamento / Licença',
                                'ouro_moderno' => 'Integração Ouro Moderno',
                                'melhoria' => 'Sugestão de Recurso',
                            ])
                            ->required(),
                        Select::make('priority')
                            ->label('Prioridade')
                            ->options([
                                'low' => 'Baixa',
                                'medium' => 'Média',
                                'high' => 'Alta',
                                'critical' => 'Crítica / Urgente',
                            ])
                            ->required(),
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'open' => 'Aberto (Pendente)',
                                'in_progress' => 'Em Análise / Atendimento',
                                'waiting_user' => 'Aguardando Cliente',
                                'resolved' => 'Resolvido (Corrigido)',
                                'closed' => 'Encerrado',
                            ])
                            ->required(),
                    ]),
                    TextInput::make('subject')
                        ->label('Assunto / Título')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Textarea::make('description')
                        ->label('Descrição do Problema')
                        ->rows(5)
                        ->disabled()
                        ->columnSpanFull(),
                ])->columnSpanFull(),

            Section::make('Telemetria Técnica (Para Incidentes de Sistema)')
                ->schema([
                    Grid::make(2)->schema([
                        Placeholder::make('error_file_line')
                            ->label('Arquivo e Linha do Erro')
                            ->content(fn ($record) => $record?->error_file_line ?? 'N/A'),
                        Placeholder::make('occurrences_count')
                            ->label('Ocorrências Capturadas')
                            ->content(fn ($record) => ($record?->occurrences_count ?? 1) . ' vezes'),
                    ])->columnSpanFull(),
                    Placeholder::make('request_url')
                        ->label('URL da Requisição')
                        ->content(fn ($record) => $record?->request_url ?? '-')
                        ->columnSpanFull(),
                    Textarea::make('error_message')
                        ->label('Mensagem de Exceção')
                        ->rows(3)
                        ->disabled()
                        ->columnSpanFull(),
                    Textarea::make('stack_trace')
                        ->label('Stack Trace (Pilhas)')
                        ->rows(8)
                        ->disabled()
                        ->columnSpanFull(),
                ])
                ->collapsible()
                ->collapsed(fn ($record) => $record?->type !== 'system_incident')
                ->columnSpanFull(),

            Section::make('Histórico de Mensagens / Conversa')
                ->schema([
                    Placeholder::make('messages_thread')
                        ->label('')
                        ->content(fn ($record) => $record ? view('filament.components.ticket-messages-thread', ['ticket' => $record]) : 'Salve o registro para iniciar o histórico.')
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),

            Section::make('Resolução & Notas Técnicas')
                ->schema([
                    Textarea::make('resolution_notes')
                        ->label('Parecer da Matriz / Nota do Deploy')
                        ->placeholder('Ex: Corrigido no commit 62f1b22, testado e validado em produção.')
                        ->rows(4)
                        ->columnSpanFull(),
                ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('protocol')
                    ->label('Protocolo')
                    ->fontFamily('mono')
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'system_incident' => '🛡️ Incidente',
                        'school_support' => '🏫 Escola',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'system_incident' => 'danger',
                        'school_support' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('school.trade_name')
                    ->label('Escola')
                    ->placeholder('Sistema Geral')
                    ->weight('bold')
                    ->searchable(),
                TextColumn::make('subject')
                    ->label('Assunto / Ocorrência')
                    ->limit(40)
                    ->searchable(),
                TextColumn::make('occurrences_count')
                    ->label('Repetições')
                    ->badge()
                    ->color(fn (int $state): string => $state > 5 ? 'danger' : 'gray')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state, $record) => $record?->status_label ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'open' => 'danger',
                        'in_progress' => 'warning',
                        'waiting_user' => 'info',
                        'resolved', 'closed' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('priority')
                    ->label('Urgência')
                    ->badge()
                    ->formatStateUsing(fn ($state, $record) => $record?->priority_label ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'critical' => 'danger',
                        'high' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('updated_at')
                    ->label('Última Atividade')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Origem')
                    ->options([
                        'system_incident' => '🛡️ Incidentes Automáticos de Sistema',
                        'school_support' => '🏫 Chamados de Escolas Parceiras',
                    ]),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'open' => 'Abertos / Pendentes',
                        'in_progress' => 'Em Análise',
                        'waiting_user' => 'Aguardando Cliente',
                        'resolved' => 'Resolvidos',
                    ]),
            ])
            ->actions([
                Action::make('reply_school')
                    ->label('Responder à Escola')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color('primary')
                    ->modalWidth('3xl')
                    ->visible(fn ($record) => $record->type === 'school_support')
                    ->modalHeading(fn ($record) => "Atendimento do Chamado: {$record->protocol}")
                    ->form([
                        Placeholder::make('conversation_history')
                            ->label('')
                            ->content(fn ($record) => view('filament.components.ticket-messages-thread', ['ticket' => $record]))
                            ->columnSpanFull(),
                        Select::make('status')
                            ->label('Atualizar Status Para')
                            ->options([
                                'in_progress' => 'Em Atendimento',
                                'waiting_user' => 'Aguardando Escola',
                                'resolved' => 'Resolvido (Encerrado)',
                            ])
                            ->default('waiting_user')
                            ->required()
                            ->columnSpanFull(),
                        Textarea::make('message')
                            ->label('Resposta da Matriz Aurosoft')
                            ->rows(4)
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->action(function (SupportTicket $record, array $data) {
                        SupportTicketMessage::create([
                            'support_ticket_id' => $record->id,
                            'user_id' => Auth::id(),
                            'author_name' => 'Suporte Central Aurosoft',
                            'author_type' => 'master',
                            'message' => $data['message'],
                        ]);

                        $record->update([
                            'status' => $data['status'],
                            'assigned_to_user_id' => Auth::id(),
                            'updated_at' => now(),
                        ]);

                        // Dispara notificação no sino para o colaborador que abriu o chamado na escola
                        try {
                            if ($record->openedByUser) {
                                $notif = \Filament\Notifications\Notification::make()
                                    ->title('Resposta da Matriz Aurosoft')
                                    ->body("Seu chamado [{$record->protocol}] recebeu uma resposta da equipe técnica.")
                                    ->icon('heroicon-o-lifebuoy')
                                    ->iconColor('success')
                                    ->actions([
                                        \Filament\Notifications\Actions\Action::make('view_ticket')
                                            ->label('Ver Resposta')
                                            ->url(url("/" . ($record->school_id ?? 1) . "/support-tickets"))
                                            ->markAsRead(),
                                    ]);
                                $record->openedByUser->notifyNow($notif->toDatabase());
                            }
                        } catch (\Throwable) {
                            // Silencioso se notificação falhar
                        }

                        Notification::make()
                            ->title('Resposta enviada com sucesso para a escola!')
                            ->success()
                            ->send();
                    }),

                Action::make('resolve_incident')
                    ->label('Marcar Resolvido')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => !in_array($record->status, ['resolved', 'closed'], true))
                    ->form([
                        Textarea::make('notes')
                            ->label('Nota de Resolução')
                            ->placeholder('Ex: Bug corrigido no deploy, migração executada.')
                            ->required(),
                    ])
                    ->action(function (SupportTicket $record, array $data) {
                        $record->update([
                            'status' => 'resolved',
                            'resolved_at' => now(),
                            'resolved_by_user_id' => Auth::id(),
                            'resolution_notes' => $data['notes'],
                        ]);

                        Notification::make()
                            ->title("Incidente {$record->protocol} marcado como Resolvido!")
                            ->success()
                            ->send();
                    }),

                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSaasTickets::route('/'),
        ];
    }
}

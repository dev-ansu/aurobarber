<?php

declare(strict_types=1);

namespace App\Filament\Resources\SupportTickets;

use App\Filament\Resources\SupportTickets\Pages\ManageSupportTickets;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class SupportTicketResource extends Resource
{
    protected static ?string $model = SupportTicket::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static string|UnitEnum|null $navigationGroup = 'Ajuda e suporte';

    protected static ?string $navigationLabel = 'Central de Chamados & Suporte';

    protected static ?string $modelLabel = 'chamado';

    protected static ?string $pluralModelLabel = 'chamados';

    protected static ?string $slug = 'support-tickets';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->latest('updated_at');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informações do Chamado')
                ->schema([
                    Grid::make(3)->schema([
                        Placeholder::make('protocol')
                            ->label('Protocolo')
                            ->content(fn ($record) => $record?->protocol ?? 'Gerado automaticamente'),
                        Placeholder::make('student_name')
                            ->label('Aluno')
                            ->content(fn ($record) => $record?->student?->name ?? 'Não identificado'),
                        Placeholder::make('created_at')
                            ->label('Aberto em')
                            ->content(fn ($record) => $record?->created_at?->format('d/m/Y H:i') ?? '-'),
                    ])->columnSpanFull(),
                    Grid::make(3)->schema([
                        Select::make('category')
                            ->label('Categoria')
                            ->options([
                                'pedagogico' => 'Dúvida Pedagógica / Aulas',
                                'financeiro' => 'Financeiro / Pagamento',
                                'secretaria' => 'Secretaria / Documentos',
                                'ouro_moderno' => 'Laboratório Interativo',
                                'geral' => 'Outros Assuntos',
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
                            ->label('Status do Atendimento')
                            ->options([
                                'open' => 'Aberto (Novo)',
                                'in_progress' => 'Em Atendimento',
                                'waiting_user' => 'Aguardando Aluno',
                                'resolved' => 'Resolvido',
                                'closed' => 'Encerrado',
                            ])
                            ->required(),
                    ])->columnSpanFull(),
                    TextInput::make('subject')
                        ->label('Assunto')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Textarea::make('description')
                        ->label('Descrição Inicial do Aluno')
                        ->rows(4)
                        ->disabled()
                        ->columnSpanFull(),
                ])->columnSpanFull(),

            Section::make('Histórico de Mensagens / Conversa')
                ->schema([
                    Placeholder::make('messages_thread')
                        ->label('')
                        ->content(fn ($record) => $record ? view('filament.components.ticket-messages-thread', ['ticket' => $record]) : 'Salve o chamado para iniciar o histórico.')
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),

            Section::make('Resolução / Encerramento')
                ->schema([
                    Textarea::make('resolution_notes')
                        ->label('Parecer / Nota Final de Conclusão')
                        ->placeholder('Informe a solução dada para este chamado...')
                        ->rows(4)
                        ->columnSpanFull(),
                ])
                ->collapsible()
                ->columnSpanFull(),
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
                TextColumn::make('student.name')
                    ->label('Aluno')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('subject')
                    ->label('Assunto')
                    ->limit(35)
                    ->searchable(),
                TextColumn::make('category')
                    ->label('Categoria')
                    ->badge()
                    ->formatStateUsing(fn ($state, $record) => $record?->category_label ?? $state)
                    ->color('gray'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state, $record) => $record?->status_label ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'open' => 'warning',
                        'in_progress' => 'info',
                        'waiting_user' => 'gray',
                        'resolved', 'closed' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('priority')
                    ->label('Prioridade')
                    ->badge()
                    ->formatStateUsing(fn ($state, $record) => $record?->priority_label ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'critical' => 'danger',
                        'high' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('updated_at')
                    ->label('Última Interação')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'open' => 'Abertos',
                        'in_progress' => 'Em Atendimento',
                        'waiting_user' => 'Aguardando Aluno',
                        'resolved' => 'Resolvidos',
                    ]),
                SelectFilter::make('category')
                    ->label('Categoria')
                    ->options([
                        'pedagogico' => 'Pedagógico',
                        'financeiro' => 'Financeiro',
                        'secretaria' => 'Secretaria',
                        'ouro_moderno' => 'Laboratório',
                    ]),
            ])
            ->actions([
                Action::make('reply')
                    ->label('Atender / Responder')
                    ->icon('heroicon-o-chat-bubble-bottom-center-text')
                    ->color('primary')
                    ->modalWidth('3xl')
                    ->modalHeading(fn ($record) => "Atendimento do Chamado {$record->protocol}")
                    ->modalDescription(fn ($record) => "Aluno: {$record->student?->name} • {$record->subject}")
                    ->form([
                        Placeholder::make('conversation_history')
                            ->label('')
                            ->content(fn ($record) => view('filament.components.ticket-messages-thread', ['ticket' => $record]))
                            ->columnSpanFull(),
                        Select::make('new_status')
                            ->label('Atualizar Status Para')
                            ->options([
                                'in_progress' => 'Em Atendimento',
                                'waiting_user' => 'Aguardando Aluno',
                                'resolved' => 'Resolvido (Finalizado)',
                            ])
                            ->default('waiting_user')
                            ->required()
                            ->columnSpanFull(),
                        Textarea::make('reply_message')
                            ->label('Nova Mensagem de Resposta')
                            ->required()
                            ->rows(4)
                            ->placeholder('Digite a resposta para o aluno visualizar no portal...')
                            ->columnSpanFull(),
                    ])
                    ->action(function (SupportTicket $record, array $data) {
                        SupportTicketMessage::create([
                            'support_ticket_id' => $record->id,
                            'user_id' => Auth::id(),
                            'author_name' => Auth::user()?->name ?? 'Equipe da Escola',
                            'author_type' => 'staff',
                            'message' => $data['reply_message'],
                        ]);

                        $updateData = [
                            'status' => $data['new_status'],
                            'assigned_to_user_id' => $record->assigned_to_user_id ?? Auth::id(),
                            'updated_at' => now(),
                        ];

                        if ($data['new_status'] === 'resolved') {
                            $updateData['resolved_at'] = now();
                            $updateData['resolved_by_user_id'] = Auth::id();
                        }

                        $record->update($updateData);

                        Notification::make()
                            ->title('Resposta enviada com sucesso!')
                            ->body("O chamado {$record->protocol} foi atualizado.")
                            ->success()
                            ->send();
                    }),
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSupportTickets::route('/'),
        ];
    }
}

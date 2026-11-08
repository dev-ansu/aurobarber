<?php

declare(strict_types=1);

namespace App\Filament\Saas\Resources\LandingLeads;

use App\Filament\Saas\Resources\LandingLeads\Pages\EditLandingLead;
use App\Filament\Saas\Resources\LandingLeads\Pages\ListLandingLeads;
use App\Models\LandingLead;
use App\Models\License;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class LandingLeadResource extends Resource
{
    protected static ?string $model = LandingLead::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-plus';

    protected static ?string $navigationLabel = 'Leads & Contatos';

    protected static ?string $modelLabel = 'Lead Comercial';

    protected static ?string $pluralModelLabel = 'Leads da Vitrine';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        $newCount = LandingLead::where('status', 'novo')->count();

        return $newCount > 0 ? (string) $newCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informações do Interessado')
                    ->description('Dados preenchidos no formulário da Landing Page oficial.')
                    ->icon('heroicon-m-user')
                    ->columns(['sm' => 1, 'md' => 2])
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome do Responsável')
                            ->required()
                            ->maxLength(120),

                        TextInput::make('school_name')
                            ->label('Instituição / Escola')
                            ->required()
                            ->maxLength(160),

                        TextInput::make('email')
                            ->label('E-mail')
                            ->email()
                            ->required()
                            ->maxLength(160)
                            ->rule(new \App\Rules\ValidEmailDomain()),

                        TextInput::make('phone')
                            ->label('WhatsApp / Telefone')
                            ->required()
                            ->maxLength(40),

                        TextInput::make('city')
                            ->label('Cidade / Estado')
                            ->maxLength(100),

                        TextInput::make('students_count')
                            ->label('Porte da Escola (Alunos)')
                            ->maxLength(60),

                        Select::make('status')
                            ->label('Status do Lead')
                            ->options([
                                'novo' => 'Novo (Não Atendido)',
                                'em_contato' => 'Em Contato / WhatsApp',
                                'agendado' => 'Demonstração Agendada',
                                'convertido' => 'Convertido em Cliente 🎉',
                                'arquivado' => 'Arquivado / Sem Interesse',
                            ])
                            ->default('novo')
                            ->required(),

                        TextInput::make('ip')
                            ->label('IP de Origem')
                            ->disabled(),
                    ]),

                Section::make('Mensagem & Anotações de Vendas')
                    ->icon('heroicon-m-chat-bubble-left-right')
                    ->columns(1)
                    ->schema([
                        Textarea::make('message')
                            ->label('Mensagem enviada pelo visitante')
                            ->rows(3)
                            ->disabled(),

                        Textarea::make('notes')
                            ->label('Anotações do Desenvolvedor / Equipe Comercial')
                            ->placeholder('Ex: Demonstração marcada para quinta 14h. Estão interessados no módulo de Totem e LMS.')
                            ->rows(4),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('Responsável')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (LandingLead $record): string => $record->email),

                TextColumn::make('school_name')
                    ->label('Escola / Instituição')
                    ->searchable()
                    ->sortable()
                    ->color('primary')
                    ->weight('semibold')
                    ->description(fn (LandingLead $record): ?string => $record->city),

                TextColumn::make('phone')
                    ->label('WhatsApp')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Telefone copiado!')
                    ->formatStateUsing(fn ($state) => $state),

                TextColumn::make('students_count')
                    ->label('Alunos')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'novo' => 'Novo',
                        'em_contato' => 'Em Contato',
                        'agendado' => 'Demonstração',
                        'convertido' => 'Convertido 🎉',
                        'arquivado' => 'Arquivado',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'novo' => 'danger',
                        'em_contato' => 'warning',
                        'agendado' => 'info',
                        'convertido' => 'success',
                        'arquivado' => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('Recebido em')
                    ->dateTime('d/m/Y H:i')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'novo' => 'Novo (Não Atendido)',
                        'em_contato' => 'Em Contato',
                        'agendado' => 'Demonstração Agendada',
                        'convertido' => 'Convertido em Cliente',
                        'arquivado' => 'Arquivado',
                    ]),
            ])
            ->actions([
                // Botão de 1 clique para WhatsApp
                Action::make('whatsapp')
                    ->label('WhatsApp')
                    ->icon('heroicon-m-chat-bubble-oval-left-ellipsis')
                    ->color('success')
                    ->url(fn (LandingLead $record): string => $record->getWhatsAppUrl())
                    ->openUrlInNewTab(),

                // Converter em Escola (Criar Licença)
                Action::make('convert_to_license')
                    ->label('Criar Licença da Escola')
                    ->icon('heroicon-m-sparkles')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Converter Lead em Escola Cliente')
                    ->modalDescription(fn (LandingLead $record): string => "Deseja criar a licença comercial para {$record->school_name}?")
                    ->action(function (LandingLead $record): void {
                        $slug = Str::slug($record->school_name);

                        // Garante slug único
                        $baseSlug = $slug;
                        $counter = 1;
                        while (License::where('slug', $slug)->exists()) {
                            $slug = "{$baseSlug}-{$counter}";
                            $counter++;
                        }

                        $license = License::create([
                            'client_name' => $record->school_name,
                            'trade_name' => $record->school_name,
                            'contact_name' => $record->name,
                            'contact_email' => $record->email,
                            'contact_phone' => $record->phone,
                            'slug' => $slug,
                            'type' => 'cloud_multi_tenant',
                            'status' => 'trial',
                            'plan_name' => 'Plano Inicial Cloud',
                            'max_students' => 300,
                            'expires_at' => now()->addDays(30),
                        ]);

                        $record->update([
                            'status' => 'convertido',
                            'converted_license_id' => $license->id,
                        ]);

                        Notification::make()
                            ->title('Escola cadastrada com sucesso!')
                            ->body("A licença para {$record->school_name} foi criada no plano Trial de 30 dias. Você já pode provisionar o schema!")
                            ->success()
                            ->send();
                    })
                    ->visible(fn (LandingLead $record): bool => $record->status !== 'convertido'),

                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLandingLeads::route('/'),
            'edit' => EditLandingLead::route('/{record}/edit'),
        ];
    }
}

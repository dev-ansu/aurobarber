<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Filament\Resources\Collaborators\CollaboratorResource;
use App\Models\Collaborator;
use App\Models\Person;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Password;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identidade e acesso')
                    ->description('Vincule o usuário a um colaborador da escola e defina o e-mail de acesso.')
                    ->schema([
                        Select::make('person_id')
                            ->label('Colaborador')
                            ->options(function (?User $record): array {
                                $options = Collaborator::query()
                                    ->with('person')
                                    ->where('active', true)
                                    ->get()
                                    ->filter(fn (Collaborator $c): bool => $c->person !== null)
                                    ->mapWithKeys(fn (Collaborator $c): array => [
                                        $c->person_id => $c->person->name.' ('.($c->role ?: 'Colaborador').($c->is_educator ? ' • Educador' : '').')',
                                    ])
                                    ->all();

                                $targetPersonId = $record?->person_id ?: request()->query('person_id');
                                if ($targetPersonId && ! isset($options[$targetPersonId])) {
                                    $person = Person::find($targetPersonId);
                                    if ($person) {
                                        $collaborator = Collaborator::where('person_id', $targetPersonId)->first();
                                        $role = $collaborator?->role ?: 'Colaborador';
                                        $options[$targetPersonId] = $person->name." ({$role})";
                                    }
                                }

                                return $options;
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->hintAction(
                                Action::make('novoColaborador')
                                    ->label('+ Novo Colaborador')
                                    ->icon('heroicon-o-briefcase')
                                    ->color('primary')
                                    ->url(fn (): string => CollaboratorResource::getUrl('create'))
                            )
                            ->afterStateUpdated(function ($state, callable $set): void {
                                $person = Person::find($state);
                                if ($person && filled($person->email)) {
                                    $set('email', $person->email);
                                } elseif ($person) {
                                    $set('email', 'usuario.'.$person->id.'@aurosoft.local');
                                }
                            })
                            ->helperText('Selecione o colaborador da escola para vincular a esta conta de usuário.'),
                        TextInput::make('email')
                            ->label('E-mail')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->rule(new \App\Rules\ValidEmailDomain()),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Senha')
                    ->description('Em edição, deixe em branco para manter a senha atual.')
                    ->schema([
                        TextInput::make('password')
                            ->label('Senha')
                            ->password()
                            ->maxLength(255)
                            ->revealable()
                            ->rule(Password::default())
                            ->helperText('A senha deve ter no mínimo 8 caracteres, contendo letras, números e símbolos.')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create'),
                        TextInput::make('password_confirmation')
                            ->label('Confirmar senha')
                            ->password()
                            ->revealable()
                            ->maxLength(255)
                            ->same('password')
                            ->dehydrated(false)
                            ->required(fn (string $operation): bool => $operation === 'create'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Permissões do sistema')
                    ->description('Os perfis definem quais áreas e ações estarão disponíveis para o usuário.')
                    ->schema([
                        Select::make('schools')
                            ->label('Unidades Escolares autorizadas')
                            ->relationship('schools', 'trade_name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->helperText('Selecione quais unidades este colaborador pode gerenciar. Super administradores têm acesso a todas as unidades automaticamente.')
                            ->columnSpanFull(),
                        Select::make('roles')
                            ->label('Perfis de acesso')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->helperText('Obrigatório para colaboradores: colaboradores sem papel definido não conseguem efetuar login no sistema até que um papel seja atribuído.'),
                        Toggle::make('panel_access')
                            ->label('Acesso ao Painel Administrativo (/admin)')
                            ->default(true)
                            ->helperText('Define se este usuário pode efetuar login e gerenciar a escola pelo Painel Administrativo. Se desativado, o acesso fica estritamente restrito ao Portal do Aluno/Educador.')
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}

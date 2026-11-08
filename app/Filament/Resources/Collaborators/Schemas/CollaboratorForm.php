<?php

namespace App\Filament\Resources\Collaborators\Schemas;

use App\Actions\PersonSelect;
use App\Models\Collaborator;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class CollaboratorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identificação e pagamento')->schema([

                    Grid::make(2)->schema([

                        PersonSelect::make('person_id')
                            ->label('Pessoa')
                            ->hidden(fn (string $operation): bool => $operation == 'edit')
                            ->required()
                            ->hintAction(
                                \Filament\Actions\Action::make('novaPessoa')
                                    ->label('+ Cadastrar Nova Pessoa')
                                    ->icon('heroicon-o-user-plus')
                                    ->color('primary')
                                    ->url(fn (): string => route('filament.admin.resources.people.create', ['redirect_to_collaborator' => 1]))
                            )
                            ->suffixAction(
                                \Filament\Actions\Action::make('goToCreatePerson')
                                    ->icon('heroicon-o-user-plus')
                                    ->tooltip('Ir para cadastro completo de pessoa')
                                    ->url(fn (): string => route('filament.admin.resources.people.create', ['redirect_to_collaborator' => 1]))
                            ),
                        TextInput::make('pix')->label('Chave PIX'),
                    ]),

                ])->columnSpanFull(),

                Section::make('Dados do vínculo')->schema([
                    Grid::make(3)->schema([
                        DatePicker::make('admission_date')
                            ->label('Data de admissão')
                            ->required(),
                        DatePicker::make('dismissal_date')->label('Data de demissão'),
                        TextInput::make('role')
                            ->label('Cargo / Função')
                            ->placeholder('Ex: Vendedor, Professor, Secretária')
                            ->required(),
                    ]),
                ])->columnSpanFull(),

                Section::make('Regras de Comissionamento e Remuneração')->schema([
                    Grid::make(2)->schema([
                        Select::make('commission_type')
                            ->label('Regra de comissão')
                            ->options([
                                Collaborator::COMMISSION_TYPE_NONE => 'Sem comissão',
                                Collaborator::COMMISSION_TYPE_FIXED_PER_CONTRACT => 'Valor fixo por matrícula (R$)',
                                Collaborator::COMMISSION_TYPE_PERCENTAGE_CONTRACT => 'Percentual sobre valor das vendas (%)',
                                Collaborator::COMMISSION_TYPE_PERCENTAGE_RECEIVED => 'Percentual sobre parcelas recebidas (%)',
                                Collaborator::COMMISSION_TYPE_HOURLY_RATE => 'Valor por hora-aula (R$)',
                            ])
                            ->default(Collaborator::COMMISSION_TYPE_NONE)
                            ->live()
                            ->required(),

                        TextInput::make('commission_rate')
                            ->label(fn (Get $get) => match ($get('commission_type')) {
                                Collaborator::COMMISSION_TYPE_FIXED_PER_CONTRACT => 'Valor da comissão por matrícula (R$)',
                                Collaborator::COMMISSION_TYPE_PERCENTAGE_CONTRACT, Collaborator::COMMISSION_TYPE_PERCENTAGE_RECEIVED => 'Percentual de comissão (%)',
                                default => 'Taxa de comissão',
                            })
                            ->prefix(fn (Get $get) => $get('commission_type') === Collaborator::COMMISSION_TYPE_FIXED_PER_CONTRACT ? 'R$' : null)
                            ->suffix(fn (Get $get) => in_array($get('commission_type'), [Collaborator::COMMISSION_TYPE_PERCENTAGE_CONTRACT, Collaborator::COMMISSION_TYPE_PERCENTAGE_RECEIVED]) ? '%' : null)
                            ->numeric()
                            ->default(0.00)
                            ->visible(fn (Get $get) => in_array($get('commission_type'), [
                                Collaborator::COMMISSION_TYPE_FIXED_PER_CONTRACT,
                                Collaborator::COMMISSION_TYPE_PERCENTAGE_CONTRACT,
                                Collaborator::COMMISSION_TYPE_PERCENTAGE_RECEIVED,
                            ])),

                        TextInput::make('hourly_rate')
                            ->label('Valor por hora-aula (R$)')
                            ->prefix('R$')
                            ->numeric()
                            ->default(0.00)
                            ->visible(fn (Get $get) => $get('commission_type') === Collaborator::COMMISSION_TYPE_HOURLY_RATE),
                    ]),

                    Textarea::make('commission_notes')
                        ->label('Observações ou acordos da comissão')
                        ->placeholder('Ex: Comissão paga no dia 5 de cada mês mediante fechamento.')
                        ->rows(2)
                        ->columnSpanFull(),
                ])->columnSpanFull(),

                Section::make('Perfil Pedagógico e Acesso aos Cursos')->schema([
                    Toggle::make('is_educator')
                        ->label('É Educador / Professor LMS?')
                        ->helperText('Habilita privilégios pedagógicos de professor para liberar aulas/digitação presencialmente com suas credenciais e ter acesso total aos cursos caso não haja restrição.')
                        ->default(false)
                        ->live(),

                    Select::make('courses')
                        ->label(fn (Get $get): string => (bool) $get('is_educator')
                            ? 'Cursos sob responsabilidade do educador'
                            : 'Cursos liberados para o colaborador')
                        ->relationship('courses', 'name')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->helperText(fn (Get $get): string => (bool) $get('is_educator')
                            ? 'Educador / Professor: Deixe em branco para liberar TODOS os cursos da plataforma. Ou selecione cursos específicos para restringir.'
                            : 'Colaborador não-educador: Selecione quais cursos este colaborador poderá acessar no portal. Deixe em branco para NÃO ter acesso a nenhum curso.')
                        ->live(),
                ])->columnSpanFull(),

                Section::make('Situação')->schema([
                    Toggle::make('active')->label('Colaborador ativo')->default(true)->required(),
                ])->columnSpanFull(),
            ]);
    }
}

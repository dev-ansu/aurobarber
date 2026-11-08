<?php

namespace App\Filament\Resources\ContractTypes\Schemas;

use App\Models\ContractType;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class ContractTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(1)->schema([

                    Section::make('Informações do Modelo')
                        ->description('Defina o tipo de documento, o título, a versão e o status de vigência desta minuta.')
                        ->schema([
                            Select::make('document_type')
                                ->label('Tipo de Documento')
                                ->options(ContractType::DOCUMENT_TYPES)
                                ->default(ContractType::TYPE_CONTRACT)
                                ->required()
                                ->native(false),

                            TextInput::make('name')
                                ->label('Título do Modelo')
                                ->placeholder('Ex: Termo de Rescisão e Distrato Contratual')
                                ->required()
                                ->maxLength(255),

                            TextInput::make('version')
                                ->label('Versão / Ano')
                                ->placeholder('Ex: 2026.1')
                                ->required()
                                ->maxLength(50),

                            Toggle::make('is_active')
                                ->label('Modelo ativo vigente')
                                ->helperText('Apenas um modelo por tipo de documento pode ficar ativo. Ao ativar este, os demais do mesmo tipo serão inativados automaticamente.')
                                ->default(true)
                                ->required(),
                        ])
                        ->columns(2),

                    Section::make('Minuta e Cláusulas Jurídicas')
                        ->description('Insira o texto completo com as cláusulas que serão impressas para o aluno ou responsável.')
                        ->schema([
                            Placeholder::make('tags_help')
                                ->label('Variáveis dinâmicas disponíveis para personalização:')
                                ->content(new HtmlString(
                                    '<div class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                                        <p>Você pode utilizar as seguintes tags no texto das cláusulas que o sistema preencherá automaticamente na emissão:</p>
                                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-1 font-mono text-[11px] text-primary-600 dark:text-primary-400 mt-1">
                                            <span>{aluno_nome}</span>
                                            <span>{cpf_aluno}</span>
                                            <span>{rg_aluno}</span>
                                            <span>{responsavel_nome}</span>
                                            <span>{cpf_responsavel}</span>
                                            <span>{escola_nome}</span>
                                            <span>{cnpj_escola}</span>
                                            <span>{cidade_escola}</span>
                                            <span>{estado_escola}</span>
                                            <span>{curso_nome}</span>
                                            <span>{numero_contrato}</span>
                                            <span>{valor_total}</span>
                                            <span>{motivo_cancelamento}</span>
                                            <span>{valor_multa}</span>
                                            <span>{data_cancelamento}</span>
                                            <span>{dias_trancamento}</span>
                                            <span>{data_inicio_trancamento}</span>
                                            <span>{data_retorno_trancamento}</span>
                                            <span>{motivo_trancamento}</span>
                                            <span>{data_comparecimento}</span>
                                            <span>{horario_inicio}</span>
                                            <span>{horario_fim}</span>
                                            <span>{finalidade_comparecimento}</span>
                                        </div>
                                    </div>'
                                ))
                                ->columnSpanFull(),

                            RichEditor::make('content')
                                ->label('Conteúdo e Cláusulas')
                                ->placeholder('Escreva ou personalize as cláusulas contratuais aqui...')
                                ->toolbarButtons([
                                    'bold',
                                    'italic',
                                    'strike',
                                    'underline',
                                    'bulletList',
                                    'orderedList',
                                    'h2',
                                    'h3',
                                    'undo',
                                    'redo',
                                ])
                                ->columnSpanFull(),

                            Textarea::make('description')
                                ->label('Observação Interna / Justificativa da Alteração')
                                ->placeholder('Ex: Atualizadas as regras de multa rescisória e cancelamento conforme alinhamento com a diretoria...')
                                ->rows(3)
                                ->columnSpanFull(),
                        ]),
                ])->columnSpanFull(),
            ]);
    }
}

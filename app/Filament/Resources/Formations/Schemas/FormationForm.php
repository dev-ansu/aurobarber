<?php

namespace App\Filament\Resources\Formations\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FormationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dados da Formação')
                    ->description('Organize os cursos em uma trilha de aprendizagem profissionalizante.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome da Formação')
                            ->placeholder('Ex: Gestão Empresarial & Liderança, Informática Profissionalizante...')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Textarea::make('short_description')
                            ->label('Resumo de Impacto (Chamada Rápida)')
                            ->placeholder('Breve chamada chamativa para o card da formação no portal da escola.')
                            ->rows(2)
                            ->maxLength(300)
                            ->columnSpanFull(),

                        Textarea::make('description')
                            ->label('Descrição Completa / Ementa')
                            ->placeholder('Conteúdo programático, público-alvo, objetivos da formação...')
                            ->rows(4)
                            ->maxLength(4000)
                            ->columnSpanFull(),

                        Toggle::make('active')
                            ->label('Formação ativa para novas matrículas')
                            ->default(true)
                            ->required(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('Vitrine & Divulgação no Portal da Escola')
                    ->description('Configure como esta formação será apresentada aos futuros alunos no site da escola.')
                    ->schema([
                        FileUpload::make('image_path')
                            ->label('Capa / Foto da Formação')
                            ->image()
                            ->disk('public')
                            ->directory('formations/covers')
                            ->visibility('public')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(2048) // Limite estrito de 2MB
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('16:10')
                            ->imageResizeTargetWidth('1200')
                            ->imageResizeTargetHeight('750')
                            ->imageEditor()
                            ->helperText('Máximo 2MB (JPG, PNG ou WebP). O sistema otimiza e redimensiona a imagem automaticamente para economizar espaço de armazenamento.')
                            ->columnSpanFull(),

                        Grid::make(3)
                            ->schema([
                                TextInput::make('highlight_badge')
                                    ->label('Selo de Destaque')
                                    ->placeholder('Ex: Mais Procurado, Alta Empregabilidade, Formação VIP')
                                    ->maxLength(100),

                                TextInput::make('workload_hours')
                                    ->label('Carga Horária Total')
                                    ->numeric()
                                    ->suffix('horas')
                                    ->helperText('Deixe em branco para calcular automaticamente a soma dos cursos vinculados.'),

                                TextInput::make('duration_months')
                                    ->label('Duração Estimada')
                                    ->numeric()
                                    ->suffix('meses')
                                    ->placeholder('Ex: 12'),
                            ]),

                        Grid::make(3)
                            ->schema([
                                Toggle::make('has_certificate')
                                    ->label('Certificado Válido Incluso')
                                    ->helperText('Exibe o selo oficial de emissão de certificado ao concluir.')
                                    ->default(true),

                                Toggle::make('show_in_showcase')
                                    ->label('Exibir na Vitrine Pública da Escola')
                                    ->helperText('Permite que futuros alunos vejam esta formação e solicitem informações.')
                                    ->default(true),

                                TextInput::make('order')
                                    ->label('Ordem de Exibição')
                                    ->numeric()
                                    ->default(0)
                                    ->helperText('Menor número aparece primeiro.'),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}

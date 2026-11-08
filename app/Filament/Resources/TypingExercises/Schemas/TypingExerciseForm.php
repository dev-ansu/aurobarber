<?php

declare(strict_types=1);

namespace App\Filament\Resources\TypingExercises\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TypingExerciseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identificação e Metodologia do Exercício')
                    ->description('Defina o título, nível de dificuldade gradual e categoria do treino.')
                    ->schema([
                        TextInput::make('title')
                            ->label('Título do Exercício')
                            ->placeholder('Ex: Exercício 08: Acentuação Aguda e Crase')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),

                        Select::make('level')
                            ->label('Nível de Dificuldade')
                            ->options([
                                1 => 'Nível 1: Teclas Guia e Dedos Individuais (ASDF JKLÇ)',
                                2 => 'Nível 2: Alfabeto Completo & Pangramas (Todas as Letras A-Z)',
                                3 => 'Nível 3: Palavras Completas & Vocabulário de Informática',
                                4 => 'Nível 4: Acentuação ABNT2 (Agudo, Circunflexo, Til, Crase e Cedilha)',
                                5 => 'Nível 5: Pontuação, Aspas, Parênteses e Símbolos (@ # $ % & *)',
                                6 => 'Nível 6: Textos Curtos (Ergonomia e Boas Práticas)',
                                7 => 'Nível 7: Textos Médios (Sistemas Operacionais e Informática)',
                                8 => 'Nível 8: Textos Longos (Redação Empresarial e Produtividade)',
                            ])
                            ->default(1)
                            ->required(),

                        Select::make('category')
                            ->label('Categoria Temática')
                            ->options([
                                'home_row' => 'Teclas Guias (Home Row)',
                                'all_letters' => 'Todas as Letras / Pangramas (A-Z)',
                                'words' => 'Palavras Completas e Vocabulário',
                                'accents' => 'Acentuação e Cedilha',
                                'symbols' => 'Pontuação, Aspas e Símbolos',
                                'short_text' => 'Textos Curtos (30 a 60 palavras)',
                                'medium_text' => 'Textos Médios (80 a 140 palavras)',
                                'long_text' => 'Textos Longos (150+ palavras)',
                            ])
                            ->default('home_row')
                            ->required(),

                        TextInput::make('min_wpm')
                            ->label('Meta Mínima de Velocidade (PPM / WPM)')
                            ->numeric()
                            ->default(15)
                            ->minValue(5)
                            ->maxValue(120)
                            ->required(),

                        TextInput::make('min_accuracy')
                            ->label('Meta Mínima de Precisão (%)')
                            ->numeric()
                            ->default(85.00)
                            ->minValue(50.00)
                            ->maxValue(100.00)
                            ->suffix('%')
                            ->required(),

                        TextInput::make('sort_order')
                            ->label('Ordem Sequencial')
                            ->numeric()
                            ->default(1)
                            ->required(),

                        Toggle::make('is_active')
                            ->label('Exercício Ativo')
                            ->default(true)
                            ->required(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('Conteúdo Prático de Digitação')
                    ->description('Texto exato que o aluno deverá digitar no teclado físico.')
                    ->schema([
                        Textarea::make('target_text')
                            ->label('Texto do Desafio')
                            ->placeholder('Digite aqui a sequência exata de caracteres, palavras ou frases que o aluno deverá treinar...')
                            ->rows(5)
                            ->required()
                            ->columnSpanFull(),

                        Textarea::make('instructions')
                            ->label('Dica Ergonômica / Instruções de Dedos')
                            ->placeholder('Ex: Para a crase, segure Shift e aperte a tecla do agudo, depois a letra A. Mantenha os pulsos retos...')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}

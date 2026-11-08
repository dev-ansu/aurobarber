<?php

namespace App\Filament\Resources\StudentAnnouncements;

use App\Filament\Resources\StudentAnnouncements\Pages\ManageStudentAnnouncements;
use App\Models\StudentAnnouncement;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class StudentAnnouncementResource extends Resource
{
    protected static bool $isScopedToTenant = false;

    protected static ?string $model = StudentAnnouncement::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-megaphone';

    protected static string|UnitEnum|null $navigationGroup = 'Administrativo';

    protected static ?string $navigationLabel = 'Avisos do kiosk';

    protected static ?string $modelLabel = 'aviso';

    protected static ?string $pluralModelLabel = 'avisos do kiosk';

    protected static ?string $slug = 'student-announcements';

    protected static ?int $navigationSort = 12;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Conteúdo do aviso')
                ->description('Adicione um texto, uma imagem ou os dois. Os avisos ativos e dentro do prazo aparecem no kiosk.')
                ->schema([
                    TextInput::make('title')
                        ->label('Título')
                        ->required()
                        ->maxLength(160),
                    Textarea::make('body')
                        ->label('Mensagem')
                        ->rows(5)
                        ->maxLength(5000)
                        ->required(fn (Get $get): bool => blank($get('image_path')))
                        ->helperText('Use texto simples. É possível combinar a mensagem com uma imagem.'),
                    FileUpload::make('image_path')
                        ->label('Imagem')
                        ->image()
                        ->disk('public')
                        ->directory('student-announcements')
                        ->visibility('public')
                        ->imagePreviewHeight('240')
                        ->maxSize(5120)
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->required(fn (Get $get): bool => blank($get('body')))
                        ->helperText('JPEG, PNG ou WebP, até 5 MB. A prévia aparece aqui antes de salvar.'),
                    TextInput::make('external_url')
                        ->label('Link externo')
                        ->url()
                        ->maxLength(2048)
                        ->placeholder('https://...')
                        ->helperText('Abre em uma nova aba, por exemplo uma pesquisa de satisfação.'),
                ])->columns(2)->columnSpanFull(),
            Section::make('Exibição e Unidade')
                ->schema([
                    Select::make('school_id')
                        ->label('Unidade Escolar')
                        ->relationship('school', 'trade_name')
                        ->placeholder('Todas as Unidades (Aviso Global da Rede)')
                        ->searchable()
                        ->preload()
                        ->helperText('Se não selecionar nenhuma unidade, o aviso será exibido em todos os totens e portais de alunos.'),
                    DateTimePicker::make('expires_at')
                        ->label('Expira em')
                        ->native(false)
                        ->seconds(false)
                        ->helperText('Deixe em branco para um aviso sem data de expiração.'),
                    TextInput::make('sort_order')
                        ->label('Ordem de exibição')
                        ->numeric()
                        ->integer()
                        ->minValue(0)
                        ->default(0)
                        ->required()
                        ->helperText('Números menores aparecem primeiro.'),
                    Toggle::make('is_active')
                        ->label('Ativo')
                        ->default(true)
                        ->required(),
                ])->columns(4)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                ImageColumn::make('image_path')
                    ->label('Prévia')
                    ->disk('public')
                    ->square(),
                TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('school.trade_name')
                    ->label('Unidade')
                    ->placeholder('Todas (Global)')
                    ->badge()
                    ->sortable(),
                TextColumn::make('body')
                    ->label('Mensagem')
                    ->limit(70)
                    ->wrap(),
                TextColumn::make('sort_order')
                    ->label('Ordem')
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->label('Expiração')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Sem expiração')
                    ->sortable(),
                ToggleColumn::make('is_active')
                    ->label('Ativo'),
            ])
            ->filters([
                SelectFilter::make('school_id')
                    ->label('Unidade Escolar')
                    ->relationship('school', 'trade_name')
                    ->placeholder('Todas as Unidades'),
                TernaryFilter::make('is_active')
                    ->label('Situação')
                    ->trueLabel('Ativos')
                    ->falseLabel('Inativos')
                    ->placeholder('Todos'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageStudentAnnouncements::route('/'),
        ];
    }
}

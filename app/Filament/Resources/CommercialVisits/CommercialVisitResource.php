<?php

namespace App\Filament\Resources\CommercialVisits;

use App\Filament\Resources\CommercialVisits\Pages\ManageCommercialVisits;
use App\Models\Collaborator;
use App\Models\CommercialLead;
use App\Models\CommercialVisit;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CommercialVisitResource extends Resource
{
    protected static ?string $model = CommercialVisit::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static string|UnitEnum|null $navigationGroup = 'Comercial';

    protected static ?string $navigationLabel = 'Visitas';

    protected static ?string $modelLabel = 'visita';

    protected static ?string $pluralModelLabel = 'visitas';

    protected static ?string $slug = 'commercial-visits';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Registro da visita')->schema([
                Select::make('commercial_lead_id')
                    ->label('Pessoa interessada')
                    ->options(fn (): array => CommercialLead::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->preload()
                    ->required(),
                DateTimePicker::make('visited_at')
                    ->label('Data e horário')
                    ->default(now())
                    ->native(false)
                    ->seconds(false)
                    ->required(),
                Select::make('status')
                    ->label('Situação')
                    ->options(CommercialVisit::statuses())
                    ->default('attended')
                    ->required(),
                Select::make('collaborator_id')
                    ->label('Atendido por')
                    ->options(fn (): array => Collaborator::query()->join('people', 'people.id', '=', 'collaborators.person_id')->where('collaborators.active', true)->orderBy('people.name')->pluck('people.name', 'collaborators.id')->all())
                    ->searchable()
                    ->preload(),
                TextInput::make('purpose')->label('Assunto da consulta')->maxLength(255)->columnSpanFull(),
                Textarea::make('notes')->label('Resumo e próximos passos')->rows(4)->columnSpanFull(),
            ])->columns(2)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['lead', 'collaborator.person', 'contract']))
            ->defaultSort('visited_at', 'desc')
            ->columns([
                TextColumn::make('visited_at')->label('Data')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('lead.name')->label('Visitante')->searchable()->sortable(),
                TextColumn::make('purpose')->label('Assunto')->placeholder('—')->limit(45),
                TextColumn::make('collaborator.person.name')->label('Atendido por')->placeholder('—'),
                TextColumn::make('status')->label('Situação')->formatStateUsing(fn (string $state): string => CommercialVisit::statuses()[$state] ?? $state)->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'attended' => 'success',
                        'no_show' => 'danger',
                        'scheduled' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('contract.contract_number')->label('Contrato vinculado')->placeholder('Ainda não matriculado'),
            ])
            ->filters([
                SelectFilter::make('status')->label('Situação')->options(CommercialVisit::statuses()),
                SelectFilter::make('commercial_lead_id')
                    ->label('Visitante')
                    ->options(fn (): array => CommercialLead::query()->orderBy('name')->pluck('name', 'id')->all()),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->requiresConfirmation(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageCommercialVisits::route('/')];
    }
}

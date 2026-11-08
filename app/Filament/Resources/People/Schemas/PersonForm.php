<?php

namespace App\Filament\Resources\People\Schemas;

use App\Enums\GenderEnum;
use App\Enums\RelationshipEnum;
use App\Models\Person;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class PersonForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identificação')
                    ->description('Dados pessoais usados nos cadastros e contratos.')
                    ->schema([
                        TextInput::make('name')->label('Nome completo')->required()->maxLength(255),
                        TextInput::make('social_name')->label('Nome social')->maxLength(255),
                        DatePicker::make('birth_date')->label('Data de nascimento'),
                        Select::make('gender')->label('Gênero')->options(GenderEnum::class)->native(false),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Contato e documentos')
                    ->schema([
                        TextInput::make('email')
                            ->label('E-mail')
                            ->email()
                            ->nullable()
                            ->maxLength(255)
                            ->rule(function (?Person $record) {
                                return function (string $attribute, $value, \Closure $fail) use ($record) {
                                    if (blank($value)) {
                                        return;
                                    }
                                    $tenantId = \Filament\Facades\Filament::getTenant()?->id;
                                    $existsInCurrentSchool = Person::query()
                                        ->when($record, fn ($q) => $q->whereKeyNot($record->id))
                                        ->where('email', $value)
                                        ->when($tenantId, fn ($q) => $q->whereHas('schools', fn ($sq) => $sq->whereKey($tenantId)))
                                        ->exists();

                                    if ($existsInCurrentSchool) {
                                        $fail('Já existe uma pessoa com este e-mail cadastrada nesta unidade.');
                                    }
                                };
                            })
                            ->helperText('Opcional. Caso já pertença a outra unidade da rede, os dados serão integrados.'),
                        TextInput::make('cpf')
                            ->label('CPF')
                            ->mask('999.999.999-99')
                            ->maxLength(14)
                            ->live(onBlur: true)
                            ->rule(function (?Person $record) {
                                return function (string $attribute, $value, \Closure $fail) use ($record) {
                                    if (blank($value)) {
                                        return;
                                    }
                                    $tenantId = \Filament\Facades\Filament::getTenant()?->id;
                                    $cleanCpf = preg_replace('/[^0-9]/', '', (string) $value);

                                    $existsInCurrentSchool = Person::query()
                                        ->when($record, fn ($q) => $q->whereKeyNot($record->id))
                                        ->whereCpf($cleanCpf ?: $value)
                                        ->when($tenantId, fn ($q) => $q->whereHas('schools', fn ($sq) => $sq->whereKey($tenantId)))
                                        ->exists();

                                    if ($existsInCurrentSchool) {
                                        $fail('Esta pessoa já está cadastrada nesta unidade.');
                                    }
                                };
                            })
                            ->afterStateUpdated(function (?string $state, callable $set): void {
                                if (blank($state)) {
                                    return;
                                }
                                $tenantId = \Filament\Facades\Filament::getTenant()?->id;
                                $cleanCpf = preg_replace('/[^0-9]/', '', $state);

                                $networkPerson = Person::query()
                                    ->whereCpf($cleanCpf ?: $state)
                                    ->when($tenantId, fn ($q) => $q->whereDoesntHave('schools', fn ($sq) => $sq->whereKey($tenantId)))
                                    ->with('schools')
                                    ->first();

                                if ($networkPerson) {
                                    $set('name', $networkPerson->name);
                                    if ($networkPerson->birth_date) {
                                        $set('birth_date', $networkPerson->birth_date->format('Y-m-d'));
                                    }
                                    $set('social_name', $networkPerson->social_name);
                                    $set('email', $networkPerson->email);
                                    if ($networkPerson->gender) {
                                        $set('gender', $networkPerson->gender->value ?? $networkPerson->gender);
                                    }

                                    $units = $networkPerson->schools->pluck('trade_name')->filter()->join(', ');
                                    \Filament\Notifications\Notification::make()
                                        ->title('Pessoa localizada na rede!')
                                        ->body("{$networkPerson->name} está cadastrada na unidade ({$units}). Os dados foram carregados e serão vinculados à sua unidade ao salvar.")
                                        ->info()
                                        ->send();
                                }
                            })
                            ->helperText('Se já cadastrada em outra escola da rede, os dados serão pré-preenchidos e vinculados à sua unidade.'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Responsabilidade Financeira')
                    ->description('Defina se esta pessoa deve ser cadastrada também como responsável financeiro para a criação de contratos.')
                    ->schema([
                        Checkbox::make('is_responsible')
                            ->label('Responsável financeiro')
                            ->live()
                            ->dehydrated(false)
                            ->afterStateHydrated(function (Checkbox $component, ?Person $record): void {
                                if (! $record) {
                                    return;
                                }
                                $resp = $record->responsibles()->where('financial_responsible', true)->first()
                                    ?? $record->responsibles()->first();
                                $component->state($resp !== null && (bool) $resp->financial_responsible);
                            }),

                        Select::make('responsible_relationship')
                            ->label('Parentesco')
                            ->options(RelationshipEnum::class)
                            ->native(false)
                            ->placeholder('Selecione o parentesco...')
                            ->visible(fn (Get $get): bool => (bool) $get('is_responsible'))
                            ->required(fn (Get $get): bool => (bool) $get('is_responsible'))
                            ->dehydrated(false)
                            ->afterStateHydrated(function (Select $component, ?Person $record): void {
                                if (! $record) {
                                    return;
                                }
                                $resp = $record->responsibles()->where('financial_responsible', true)->first()
                                    ?? $record->responsibles()->first();
                                if ($resp) {
                                    $component->state($resp->relationship?->value ?? $resp->relationship);
                                }
                            }),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}

<?php

namespace App\Filament\Widgets;

use App\Enums\ContactTypeEnum;
use App\Models\Person;
use App\Models\School;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BirthdaysTableWidget extends BaseWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = '🎂 Aniversariantes do Mês e do Dia';

    public static function canView(): bool
    {
        $user = filament()->auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        return $user->hasRole('super_admin')
            || $user->can('View:BirthdaysTableWidget');
    }

    public function table(Table $table): Table
    {
        $currentMonth = Carbon::now()->month;
        $driver = DB::connection()->getDriverName();
        $dayExpr = match ($driver) {
            'pgsql' => 'EXTRACT(DAY FROM birth_date)',
            'sqlite' => "CAST(strftime('%d', birth_date) AS INTEGER)",
            default => 'DAY(birth_date)',
        };

        $tenantId = Filament::getTenant()?->id;

        return $table
            ->query(
                Person::query()
                    ->whereNotNull('birth_date')
                    ->when($tenantId, function (Builder $query) use ($tenantId): void {
                        $query->where(function (Builder $sub) use ($tenantId): void {
                            $sub->whereHas('schools', fn (Builder $sq) => $sq->where('schools.id', $tenantId))
                                ->orWhereHas('contracts', fn (Builder $cq) => $cq->where('school_id', $tenantId))
                                ->orWhereHas('responsibles.contracts', fn (Builder $cq) => $cq->where('school_id', $tenantId));
                        });
                    })
                    ->orderByRaw("{$dayExpr} asc")
                    ->with([
                        'contacts',
                        'contracts' => fn ($q) => $q->select('id', 'student_id', 'status', 'is_ead'),
                        'collaborators' => fn ($q) => $q->select('id', 'person_id', 'role'),
                        'responsibles' => fn ($q) => $q->select('id', 'person_id'),
                    ])
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $sub) use ($search) {
                            $sub->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhereHas('contacts', fn (Builder $c) => $c->where('value', 'like', "%{$search}%"));
                        });
                    })
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Person $record): string => $record->email ?? 'Sem e-mail'),

                TextColumn::make('profile_badge')
                    ->label('Perfil')
                    ->badge()
                    ->state(function (Person $record): string {
                        if ($record->collaborators->isNotEmpty()) {
                            return 'Colaborador';
                        }

                        $activeContracts = $record->contracts->where('status', 'active');
                        $hasEad = $activeContracts->where('is_ead', true)->isNotEmpty();
                        $hasPresencial = $activeContracts->where('is_ead', false)->isNotEmpty();

                        if ($hasEad && $hasPresencial) {
                            return 'Presencial & EAD';
                        }
                        if ($hasEad) {
                            return 'Aluno EAD';
                        }
                        if ($hasPresencial) {
                            return 'Aluno Presencial';
                        }
                        if ($record->contracts->isNotEmpty()) {
                            return 'Aluno';
                        }
                        if ($record->responsibles->isNotEmpty()) {
                            return 'Responsável';
                        }

                        return 'Contato';
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Colaborador' => 'purple',
                        'Presencial & EAD' => 'indigo',
                        'Aluno EAD' => 'info',
                        'Aluno Presencial' => 'success',
                        'Aluno' => 'gray',
                        'Responsável' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('birth_day')
                    ->label('Dia do Aniversário')
                    ->state(fn (Person $record): string => $record->birth_date ? $record->birth_date->translatedFormat('d \d\e F') : '—')
                    ->description(fn (Person $record): string => $record->birth_date ? 'Nascido em '.$record->birth_date->format('d/m/Y') : '')
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderByRaw("{$dayExpr} {$direction}")),

                TextColumn::make('age_turning')
                    ->label('Idade a Completar')
                    ->state(function (Person $record): string {
                        if (! $record->birth_date) {
                            return '—';
                        }
                        $age = Carbon::now()->year - $record->birth_date->year;

                        return "{$age} anos";
                    })
                    ->alignCenter(),

                TextColumn::make('status_badge')
                    ->label('Situação')
                    ->badge()
                    ->state(function (Person $record): string {
                        if (! $record->birth_date) {
                            return '—';
                        }
                        $now = Carbon::now();
                        $bMonth = (int) $record->birth_date->month;
                        $bDay = (int) $record->birth_date->day;

                        if ($bMonth === (int) $now->month && $bDay === (int) $now->day) {
                            return '🎉 HOJE!';
                        }

                        if ($bMonth === (int) $now->month) {
                            if ($bDay > (int) $now->day) {
                                $diff = $bDay - (int) $now->day;

                                return $diff === 1 ? 'Amanhã' : "Em {$diff} dias";
                            }

                            return 'Já comemorou';
                        }

                        return 'Outro mês';
                    })
                    ->color(fn (string $state): string => match (true) {
                        str_contains($state, 'HOJE') => 'success',
                        str_contains($state, 'Amanhã') || str_contains($state, 'Em ') => 'warning',
                        $state === 'Já comemorou' => 'gray',
                        default => 'info',
                    }),

                TextColumn::make('primary_phone')
                    ->label('Telefone / WhatsApp')
                    ->state(function (Person $record): string {
                        $contact = $record->contacts
                            ->whereIn('type', [ContactTypeEnum::WhatsApp, ContactTypeEnum::Celular, ContactTypeEnum::Telefone])
                            ->sortByDesc('is_primary')
                            ->first();

                        return $contact?->value ?? '—';
                    }),
            ])
            ->filters([
                SelectFilter::make('period')
                    ->label('Período')
                    ->options([
                        'current_month' => '🎂 Este mês (Padrão)',
                        'today' => '🎉 Apenas Hoje!',
                        'next_7_days' => '📅 Próximos 7 dias',
                        'next_month' => '📆 Próximo mês',
                        'all_year' => '🌍 Ano inteiro',
                    ])
                    ->default('current_month')
                    ->query(function (Builder $query, array $data): Builder {
                        $period = $data['value'] ?? 'current_month';
                        $now = Carbon::now();

                        return match ($period) {
                            'today' => $query->whereMonth('birth_date', $now->month)
                                ->whereDay('birth_date', $now->day),
                            'next_7_days' => $query->where(function (Builder $sub) use ($now) {
                                foreach (range(0, 6) as $i) {
                                    $d = $now->copy()->addDays($i);
                                    $sub->orWhere(function (Builder $q) use ($d) {
                                        $q->whereMonth('birth_date', $d->month)
                                            ->whereDay('birth_date', $d->day);
                                    });
                                }
                            }),
                            'next_month' => $query->whereMonth('birth_date', $now->copy()->addMonth()->month),
                            'all_year' => $query,
                            default => $query->whereMonth('birth_date', $now->month),
                        };
                    }),

                SelectFilter::make('profile')
                    ->label('Vínculo / Perfil')
                    ->options([
                        'all' => 'Todos os perfis',
                        'student_active' => 'Alunos com Contrato Ativo',
                        'student_ead' => 'Alunos EAD (Online)',
                        'student_presencial' => 'Alunos Presenciais',
                        'student' => 'Todos os Alunos',
                        'collaborator' => 'Colaboradores / Professores',
                        'responsible' => 'Responsáveis',
                    ])
                    ->default('all')
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? 'all') {
                            'student_active' => $query->whereHas('contracts', fn (Builder $q) => $q->where('status', 'active')),
                            'student_ead' => $query->whereHas('contracts', fn (Builder $q) => $q->where('status', 'active')->where('is_ead', true)),
                            'student_presencial' => $query->whereHas('contracts', fn (Builder $q) => $q->where('status', 'active')->where('is_ead', false)),
                            'student' => $query->whereHas('contracts'),
                            'collaborator' => $query->whereHas('collaborators'),
                            'responsible' => $query->whereHas('responsibles'),
                            default => $query,
                        };
                    }),

                SelectFilter::make('specific_month')
                    ->label('Filtrar por Mês')
                    ->options([
                        '1' => 'Janeiro',
                        '2' => 'Fevereiro',
                        '3' => 'Março',
                        '4' => 'Abril',
                        '5' => 'Maio',
                        '6' => 'Junho',
                        '7' => 'Julho',
                        '8' => 'Agosto',
                        '9' => 'Setembro',
                        '10' => 'Outubro',
                        '11' => 'Novembro',
                        '12' => 'Dezembro',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (! empty($data['value'])) {
                            return $query->whereMonth('birth_date', (int) $data['value']);
                        }

                        return $query;
                    }),
            ])
            ->recordActions([
                Action::make('felicitarWhatsapp')
                    ->label('Felicitar')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color('success')
                    ->tooltip('Enviar parabéns no WhatsApp com mensagem personalizada')
                    ->url(function (Person $record): ?string {
                        $phone = $record->contacts
                            ->whereIn('type', [ContactTypeEnum::WhatsApp, ContactTypeEnum::Celular, ContactTypeEnum::Telefone])
                            ->sortByDesc('is_primary')
                            ->first()?->value;

                        if (! $phone) {
                            return null;
                        }

                        $digits = preg_replace('/\D/', '', $phone);
                        if (strlen($digits) === 10 || strlen($digits) === 11) {
                            $digits = '55'.$digits;
                        }

                        $school = (class_exists(\Filament\Facades\Filament::class) ? \Filament\Facades\Filament::getTenant() : null)
                            ?? $record->schools->first()
                            ?? $record->contracts->where('status', 'active')->first()?->school
                            ?? $record->contracts->first()?->school
                            ?? School::getActiveSchool()
                            ?? School::query()->first();
                        $schoolName = $school?->trade_name ?: ($school?->legal_name ?: 'Nossa Escola');
                        $firstName = explode(' ', trim($record->name))[0];

                        $isEad = $record->contracts->where('status', 'active')->where('is_ead', true)->isNotEmpty();
                        if ($isEad) {
                            $text = "Olá {$firstName}! 🎉🎂 A equipe da {$schoolName} te deseja um Feliz Aniversário! Muita saúde, paz, conquistas e muito sucesso nos seus estudos online! Conte sempre conosco!";
                        } else {
                            $text = "Olá {$firstName}! 🎉🎂 A equipe da {$schoolName} te deseja um Feliz Aniversário! Muita saúde, paz, sucesso e muitas realizações!";
                        }

                        return "https://wa.me/{$digits}?text=".urlencode($text);
                    }, shouldOpenInNewTab: true)
                    ->visible(function (Person $record): bool {
                        return $record->contacts
                            ->whereIn('type', [ContactTypeEnum::WhatsApp, ContactTypeEnum::Celular, ContactTypeEnum::Telefone])
                            ->isNotEmpty();
                    }),
            ])
            ->emptyStateHeading('Nenhum aniversariante encontrado')
            ->emptyStateDescription('Não há aniversariantes registrados para o período e filtros selecionados.')
            ->emptyStateIcon('heroicon-o-cake')
            ->paginated([5, 10, 25, 50])
            ->defaultPaginationPageOption(5);
    }
}

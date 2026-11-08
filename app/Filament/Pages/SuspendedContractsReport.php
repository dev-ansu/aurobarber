<?php

namespace App\Filament\Pages;

use App\Models\Contract;
use App\Services\FinancialCalculationService;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use UnitEnum;

class SuspendedContractsReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $title = 'Acompanhamento de Trancamentos de Matrícula';

    protected static string|UnitEnum|null $navigationGroup = 'Relatórios';

    protected static ?string $navigationParentItem = 'Pedagógico';

    protected static ?string $navigationLabel = 'Trancamentos de Matrícula';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-pause-circle';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.suspended-contracts-report';

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return true;
        }

        return $user->getAllPermissions()->contains('name', 'View:SuspendedContractsReport')
            || $user->getAllPermissions()->contains('name', 'View:Contract');
    }

    public function getHeaderWidgets(): array
    {
        return [];
    }

    public function getStats(): array
    {
        $now = today();

        $totalSuspended = Contract::query()->where('status', 'suspended')->count();
        $upcoming15 = Contract::query()
            ->where('status', 'suspended')
            ->whereBetween('suspension_expected_return_date', [$now, $now->copy()->addDays(15)])
            ->count();
        $overdue = Contract::query()
            ->where('status', 'suspended')
            ->whereDate('suspension_expected_return_date', '<', $now)
            ->count();
        $reactivated30 = Contract::query()
            ->whereNotNull('suspension_returned_at')
            ->where('suspension_returned_at', '>=', now()->subDays(30))
            ->count();

        return [
            'total_suspended' => $totalSuspended,
            'upcoming_15' => $upcoming15,
            'overdue' => $overdue,
            'reactivated_30' => $reactivated30,
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Contract::query()
                ->where(function (Builder $q) {
                    $q->where('status', 'suspended')
                        ->orWhereNotNull('suspension_returned_at');
                })
                ->with(['student.documents', 'student.contacts', 'responsible.person.contacts', 'school', 'items.formation'])
            )
            ->defaultSort('suspension_expected_return_date', 'asc')
            ->columns([
                TextColumn::make('student.name')
                    ->label('Aluno')
                    ->weight('bold')
                    ->searchable()
                    ->sortable()
                    ->description(function (Contract $record): string {
                        $phone = app(FinancialCalculationService::class)->resolveContractContactPhone($record);
                        $phoneFormatted = $phone ? " • Tel: {$phone}" : '';
                        $cpf = $record->student?->cpf ?: ($record->student?->documents?->firstWhere('type', 'cpf')?->document_number ?? '');
                        return ($cpf ? "CPF: {$cpf}" : 'Sem CPF') . $phoneFormatted;
                    }),

                TextColumn::make('contract_number')
                    ->label('Contrato')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('suspended_at')
                    ->label('Data Trancamento')
                    ->dateTime('d/m/Y')
                    ->sortable(),

                TextColumn::make('suspension_expected_return_date')
                    ->label('Previsão de Retorno')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('situation')
                    ->label('Situação do Prazo')
                    ->badge()
                    ->state(function (Contract $record): string {
                        if ($record->suspension_returned_at) {
                            return 'Reativado em ' . Carbon::parse($record->suspension_returned_at)->format('d/m/Y');
                        }

                        if (! $record->suspension_expected_return_date) {
                            return 'Em Trancamento';
                        }

                        $expected = Carbon::parse($record->suspension_expected_return_date)->startOfDay();
                        $today = today();

                        if ($today->gt($expected)) {
                            $diff = $today->diffInDays($expected);
                            return $diff === 0 ? 'Vence Hoje!' : "Atrasado há {$diff} dias";
                        }

                        $diff = $today->diffInDays($expected);
                        if ($diff === 0) {
                            return 'Retorno Hoje!';
                        }
                        return "Faltam {$diff} dias";
                    })
                    ->color(function (Contract $record): string {
                        if ($record->suspension_returned_at) {
                            return 'success';
                        }
                        if (! $record->suspension_expected_return_date) {
                            return 'warning';
                        }
                        $expected = Carbon::parse($record->suspension_expected_return_date)->startOfDay();
                        $today = today();

                        if ($today->gt($expected)) {
                            return 'danger';
                        }
                        if ($today->diffInDays($expected) <= 7) {
                            return 'warning';
                        }
                        return 'info';
                    }),

                TextColumn::make('suspension_reason')
                    ->label('Motivo')
                    ->limit(35)
                    ->tooltip(fn (Contract $record): ?string => $record->suspension_reason)
                    ->placeholder('Não informado'),

                TextColumn::make('school.trade_name')
                    ->label('Unidade')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('school_id')
                    ->label('Unidade')
                    ->relationship('school', 'trade_name')
                    ->visible(fn (): bool => \App\Models\School::count() > 1),
            ])
            ->recordActions([
                \Filament\Actions\Action::make('whatsappContact')
                    ->label('WhatsApp')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color('success')
                    ->visible(fn (Contract $record): bool => $record->status === 'suspended')
                    ->action(function (Contract $record, $livewire): void {
                        $calcService = app(FinancialCalculationService::class);
                        $phone = $calcService->resolveContractContactPhone($record);

                        if (! $phone) {
                            \Filament\Notifications\Notification::make()
                                ->title('Telefone não encontrado')
                                ->body('Cadastre um telefone na ficha de Pessoa do aluno ou responsável.')
                                ->warning()
                                ->send();
                            return;
                        }

                        $cleanPhone = $calcService->formatInternationalPhone($phone);
                        $studentName = $record->student?->name ?? 'Aluno';
                        $schoolName = $record->school?->trade_name
                            ?: ($record->school?->legal_name
                                ?: (class_exists(\Filament\Facades\Filament::class) && \Filament\Facades\Filament::getTenant() ? \Filament\Facades\Filament::getTenant()->trade_name : null)
                                ?: (\App\Models\School::getActiveSchool()?->trade_name ?: config('app.name')));
                        $expectedDate = $record->suspension_expected_return_date
                            ? Carbon::parse($record->suspension_expected_return_date)->format('d/m/Y')
                            : 'em breve';

                        $msg = "Olá, {$studentName}! 👋 Tudo bem?\n\nEstamos entrando em contato da escola {$schoolName}. Notamos que o prazo de trancamento da sua matrícula está previsto para encerrar em {$expectedDate}.\n\nGostaríamos de conversar sobre o seu retorno às aulas para organizar seus horários e turmas com todo o carinho! Como estão suas coisas por aí?";

                        $livewire->js("window.open('https://wa.me/{$cleanPhone}?text=' + encodeURIComponent(" . json_encode($msg) . "), '_blank');");
                    }),

                \Filament\Actions\Action::make('reactivate')
                    ->label('Reativar')
                    ->icon('heroicon-m-play-circle')
                    ->color('success')
                    ->visible(fn (Contract $record): bool => $record->status === 'suspended')
                    ->requiresConfirmation()
                    ->modalHeading('Reativar Matrícula do Aluno')
                    ->modalDescription('Confirma o retorno do aluno às aulas? O status voltará para "Ativo".')
                    ->action(function (Contract $record): void {
                        $record->update([
                            'status' => 'active',
                            'suspension_returned_at' => now(),
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Matrícula Reativada!')
                            ->body("O contrato #{$record->contract_number} foi reativado com sucesso.")
                            ->success()
                            ->send();
                    }),

                \Filament\Actions\Action::make('printTerm')
                    ->label('Termo')
                    ->icon('heroicon-m-printer')
                    ->color('warning')
                    ->url(fn (Contract $record): string => route('contracts.termo-trancamento', $record))
                    ->openUrlInNewTab(),

                \Filament\Actions\Action::make('viewContract')
                    ->label('Ver')
                    ->icon('heroicon-m-eye')
                    ->color('gray')
                    ->url(fn (Contract $record): string => route('filament.admin.resources.contracts.view', ['record' => $record]))
                    ->openUrlInNewTab(),
            ]);
    }
}


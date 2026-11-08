<?php

declare(strict_types=1);

use App\Actions\PersonSelect;
use App\Enums\RelationshipEnum;
use App\Filament\Pages\CompletionReports;
use App\Filament\Pages\OverdueInstallmentReport;
use App\Filament\Pages\SchoolAppointmentsCalendar;
use App\Filament\Pages\SchoolMessenger;
use App\Filament\Pages\TaskBoardKanban;
use App\Filament\Resources\CashTransactions\CashTransactionsResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\CashRegister;
use App\Models\CashTransaction;
use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\ContractStatusHistory;
use App\Models\Formation;
use App\Models\Installment;
use App\Models\Payable;
use App\Models\PaymentMethod;
use App\Models\Person;
use App\Models\Responsible;
use App\Models\School;
use App\Models\SchoolAppointment;
use App\Models\SchoolMessage;
use App\Models\TaskBoard;
use App\Models\TaskCard;
use App\Models\TaskColumn;
use App\Models\User;
use App\Services\ContractCancellationReport;
use App\Services\DreService;
use App\Services\PaymentMethodReceiptReport;
use App\Services\TaskAndAppointmentReminderService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

    $this->matriz = School::query()->first() ?? School::create([
        'legal_name' => 'Escola Matriz Ltda',
        'trade_name' => 'Unidade Matriz',
    ]);
    $this->matriz->update(['trade_name' => 'Unidade Matriz']);

    $this->filial = School::create([
        'legal_name' => 'Escola Filial Norte Ltda',
        'trade_name' => 'Unidade Filial Norte',
    ]);

    $staffPerson = Person::withoutGlobalScopes()->create([
        'school_id' => $this->matriz->id,
        'name' => 'Coordenadora Ana',
        'email' => 'ana.coord@aurosoft.com',
    ]);

    $this->staffUser = User::create([
        'person_id' => $staffPerson->id,
        'email' => 'ana.coord@aurosoft.com',
        'password' => bcrypt('secret123'),
        'panel_access' => true,
    ]);
    $this->staffUser->assignRole($this->role);
    $this->staffUser->schools()->sync([$this->matriz->id, $this->filial->id]);
});

test('cashTransaction defines cashRegister relation and scopes cash transactions per school without error', function () {
    $this->actingAs($this->staffUser);

    $regMatriz = CashRegister::withoutGlobalScopes()->create([
        'school_id' => $this->matriz->id,
        'name' => 'Caixa Matriz',
        'status' => 'open',
        'opening_balance' => 100,
        'current_balance' => 150,
        'user_id' => $this->staffUser->id,
        'opened_at' => now(),
    ]);

    $regFilial = CashRegister::withoutGlobalScopes()->create([
        'school_id' => $this->filial->id,
        'name' => 'Caixa Filial',
        'status' => 'open',
        'opening_balance' => 200,
        'current_balance' => 280,
        'user_id' => $this->staffUser->id,
        'opened_at' => now(),
    ]);

    $txMatriz = CashTransaction::create([
        'cash_register_id' => $regMatriz->id,
        'user_id' => $this->staffUser->id,
        'type' => 'input',
        'amount' => 50,
        'description' => 'Recebimento Matriz',
    ]);

    $txFilial = CashTransaction::create([
        'cash_register_id' => $regFilial->id,
        'user_id' => $this->staffUser->id,
        'type' => 'input',
        'amount' => 80,
        'description' => 'Recebimento Filial',
    ]);

    expect($txMatriz->cashRegister)->not->toBeNull()
        ->and($txMatriz->cashRegister->id)->toBe($regMatriz->id);

    Filament::setTenant($this->matriz);
    $matrizIds = CashTransactionsResource::getEloquentQuery()->pluck('id')->all();
    expect($matrizIds)->toContain($txMatriz->id)
        ->not->toContain($txFilial->id);

    Filament::setTenant($this->filial);
    $filialIds = CashTransactionsResource::getEloquentQuery()->pluck('id')->all();
    expect($filialIds)->toContain($txFilial->id)
        ->not->toContain($txMatriz->id);
});

test('technical Admin account is hidden from people, selects, global search, and team lists while preserving auth person', function () {
    $adminPerson = Person::withoutGlobalScopes()->create([
        'name' => 'Admin',
        'email' => 'admin@admin.com',
    ]);

    $adminUser = User::create([
        'person_id' => $adminPerson->id,
        'email' => 'admin@admin.com',
        'password' => bcrypt('admin123'),
        'panel_access' => true,
    ]);
    $adminUser->assignRole($this->role);

    $realStudent = Person::withoutGlobalScopes()->create([
        'school_id' => $this->matriz->id,
        'name' => 'Carlos Eduardo Silva',
        'email' => 'carlos@aluno.com',
    ]);

    $this->actingAs($adminUser);
    Filament::setTenant($this->matriz);

    // O próprio admin continua conseguindo ler seu nome no perfil/sessão
    expect($adminUser->fresh()->name)->toBe('Admin');

    // Mas nunca aparece em Pessoas, PersonSelect, Global Search de Usuários ou listas de equipe
    expect(Person::query()->pluck('id')->all())
        ->not->toContain($adminPerson->id)
        ->toContain($realStudent->id);

    $selectOptions = PersonSelect::make('person_id')->getSearchResults('admin');
    expect(array_keys($selectOptions))->not->toContain($adminPerson->id);

    expect(UserResource::getGlobalSearchEloquentQuery()->pluck('id')->all())
        ->not->toContain($adminUser->id)
        ->toContain($this->staffUser->id);

    expect(UserResource::getEloquentQuery()->pluck('id')->all())
        ->not->toContain($adminUser->id)
        ->toContain($this->staffUser->id);

    $calendarPage = new SchoolAppointmentsCalendar;
    expect($calendarPage->staffUsers()->pluck('id')->all())
        ->not->toContain($adminUser->id);

    $kanbanPage = new TaskBoardKanban;
    expect($kanbanPage->staffUsers()->pluck('id')->all())
        ->not->toContain($adminUser->id);
});

test('reports isolate completed students, DRE, payment receipts, cancellations, and overdue installments strictly per school', function () {
    $this->actingAs($this->staffUser);
    Permission::findOrCreate('View:CompletionReports', 'web');
    Permission::findOrCreate('View:OverdueInstallmentReport', 'web');
    $this->role->givePermissionTo(['View:CompletionReports', 'View:OverdueInstallmentReport']);

    $pix = PaymentMethod::create(['name' => 'PIX Teste', 'active' => true]);
    $formation = Formation::create(['name' => 'Informática Essencial']);

    // Dados Escola Matriz
    $studentMatriz = Person::withoutGlobalScopes()->create(['school_id' => $this->matriz->id, 'name' => 'Aluna Concluída Matriz']);
    $respMatriz = Responsible::create(['person_id' => $studentMatriz->id, 'relationship' => RelationshipEnum::Outro, 'financial_responsible' => true]);
    $contractMatriz = Contract::withoutGlobalScopes()->create([
        'school_id' => $this->matriz->id,
        'contract_number' => 'MTZ-2026-01',
        'student_id' => $studentMatriz->id,
        'responsible_id' => $respMatriz->id,
        'status' => 'finished',
        'start_date' => today()->subMonths(6)->toDateString(),
        'completion_date' => today()->toDateString(),
        'total_value' => 1500,
    ]);
    ContractStatusHistory::create([
        'contract_id' => $contractMatriz->id,
        'from_status' => 'active',
        'to_status' => 'finished',
        'user_id' => $this->staffUser->id,
    ]);
    ContractItem::create([
        'contract_id' => $contractMatriz->id,
        'formation_id' => $formation->id,
        'status' => 'completed',
        'completed_at' => now(),
    ]);
    Installment::withoutGlobalScopes()->create([
        'school_id' => $this->matriz->id,
        'contract_id' => $contractMatriz->id,
        'installment_number' => 1,
        'total_installments' => 1,
        'amount' => 450,
        'amount_paid' => 450,
        'due_date' => today()->toDateString(),
        'paid_at' => today()->toDateString(),
        'payment_method_id' => $pix->id,
        'status' => 'paid',
    ]);
    $overdueMatriz = Installment::withoutGlobalScopes()->create([
        'school_id' => $this->matriz->id,
        'contract_id' => $contractMatriz->id,
        'installment_number' => 2,
        'total_installments' => 2,
        'amount' => 200,
        'due_date' => today()->subDays(10)->toDateString(),
        'status' => 'pending',
    ]);
    Payable::withoutGlobalScopes()->create([
        'school_id' => $this->matriz->id,
        'description' => 'Energia Matriz',
        'amount' => 120,
        'amount_paid' => 120,
        'due_date' => today()->toDateString(),
        'payment_date' => today()->toDateString(),
        'status' => 'paid',
    ]);

    // Dados Escola Filial
    $studentFilial = Person::withoutGlobalScopes()->create(['school_id' => $this->filial->id, 'name' => 'Aluno Concluído Filial']);
    $respFilial = Responsible::create(['person_id' => $studentFilial->id, 'relationship' => RelationshipEnum::Outro, 'financial_responsible' => true]);
    $contractFilial = Contract::withoutGlobalScopes()->create([
        'school_id' => $this->filial->id,
        'contract_number' => 'FIL-2026-01',
        'student_id' => $studentFilial->id,
        'responsible_id' => $respFilial->id,
        'status' => 'canceled',
        'start_date' => today()->subMonths(3)->toDateString(),
        'cancellation_reason' => 'Mudança de cidade',
        'total_value' => 1800,
    ]);
    ContractStatusHistory::create([
        'contract_id' => $contractFilial->id,
        'from_status' => 'active',
        'to_status' => 'finished',
        'user_id' => $this->staffUser->id,
    ]);
    ContractStatusHistory::create([
        'contract_id' => $contractFilial->id,
        'from_status' => 'finished',
        'to_status' => 'canceled',
        'reason' => 'Mudança de cidade',
        'user_id' => $this->staffUser->id,
    ]);
    ContractItem::create([
        'contract_id' => $contractFilial->id,
        'formation_id' => $formation->id,
        'status' => 'completed',
        'completed_at' => now(),
    ]);
    Installment::withoutGlobalScopes()->create([
        'school_id' => $this->filial->id,
        'contract_id' => $contractFilial->id,
        'installment_number' => 1,
        'total_installments' => 1,
        'amount' => 900,
        'amount_paid' => 900,
        'due_date' => today()->toDateString(),
        'paid_at' => today()->toDateString(),
        'payment_method_id' => $pix->id,
        'status' => 'paid',
    ]);
    $overdueFilial = Installment::withoutGlobalScopes()->create([
        'school_id' => $this->filial->id,
        'contract_id' => $contractFilial->id,
        'installment_number' => 2,
        'total_installments' => 2,
        'amount' => 330,
        'due_date' => today()->subDays(10)->toDateString(),
        'status' => 'pending',
    ]);

    $start = today()->startOfMonth();
    $end = today()->endOfMonth();

    // 1. Escopo Matriz
    Filament::setTenant($this->matriz);

    $dreMatriz = app(DreService::class)->generate($start, $end, 'caixa');
    expect((float) $dreMatriz['revenue']['gross'])->toBe(450.0)
        ->and((float) $dreMatriz['expenses']['total'])->toBe(120.0);

    $receiptsMatriz = app(PaymentMethodReceiptReport::class)->query($start, $end)->get();
    expect((float) $receiptsMatriz->sum('amount_total'))->toBe(450.0);

    $cancelsMatriz = app(ContractCancellationReport::class)->generate($start, $end);
    expect($cancelsMatriz['cancellation_events'])->toBe(0);

    Livewire::test(CompletionReports::class)
        ->assertSee('Aluna Concluída Matriz')
        ->assertDontSee('Aluno Concluído Filial');

    Livewire::test(OverdueInstallmentReport::class)
        ->assertCanSeeTableRecords([$overdueMatriz])
        ->assertCanNotSeeTableRecords([$overdueFilial]);

    // 2. Escopo Filial
    Filament::setTenant($this->filial);

    $dreFilial = app(DreService::class)->generate($start, $end, 'caixa');
    expect((float) $dreFilial['revenue']['gross'])->toBe(900.0)
        ->and((float) $dreFilial['expenses']['total'])->toBe(0.0);

    $receiptsFilial = app(PaymentMethodReceiptReport::class)->query($start, $end)->get();
    expect((float) $receiptsFilial->sum('amount_total'))->toBe(900.0);

    $cancelsFilial = app(ContractCancellationReport::class)->generate($start, $end);
    expect($cancelsFilial['cancellation_events'])->toBe(1);

    Livewire::test(CompletionReports::class)
        ->assertSee('Aluno Concluído Filial')
        ->assertDontSee('Aluna Concluída Matriz');

    Livewire::test(OverdueInstallmentReport::class)
        ->assertCanSeeTableRecords([$overdueFilial])
        ->assertCanNotSeeTableRecords([$overdueMatriz]);
});

test('school messenger context menu converts a message into an appointment or a to-do task card', function () {
    $this->actingAs($this->staffUser);
    Filament::setTenant($this->matriz);

    $message = SchoolMessage::create([
        'sender_school_id' => $this->filial->id,
        'recipient_school_id' => $this->matriz->id,
        'sender_user_id' => $this->staffUser->id,
        'category' => 'pedagogical',
        'subject' => 'Confirmar reposição da turma de sábado',
        'body' => 'Por favor ligar para os responsáveis da turma das 10h para confirmar reposição.',
    ]);

    Livewire::test(SchoolMessenger::class)
        ->call('openAppointmentFromMessage', $message->id)
        ->assertSet('showAppointmentModal', true)
        ->assertSet('apptTitle', 'Confirmar reposição da turma de sábado')
        ->set('apptDate', today()->toDateString())
        ->set('apptStartTime', '14:00')
        ->set('apptEndTime', '15:00')
        ->call('saveAppointmentFromMessage')
        ->assertSet('showAppointmentModal', false)
        ->call('openTaskFromMessage', $message->id)
        ->assertSet('showTaskModal', true)
        ->assertSet('taskTitle', 'Confirmar reposição da turma de sábado')
        ->set('taskPriority', 'high')
        ->call('saveTaskFromMessage')
        ->assertSet('showTaskModal', false);

    $appointment = SchoolAppointment::query()->where('title', 'Confirmar reposição da turma de sábado')->first();
    expect($appointment)->not->toBeNull()
        ->and((int) $appointment->assigned_to_user_id)->toBe($this->staffUser->id)
        ->and((int) $appointment->school_id)->toBe($this->matriz->id)
        ->and($appointment->description)->toContain('ligar para os responsáveis');

    $taskCard = TaskCard::query()->where('title', 'Confirmar reposição da turma de sábado')->first();
    expect($taskCard)->not->toBeNull()
        ->and($taskCard->priority)->toBe('high')
        ->and($taskCard->assignedUsers()->whereKey($this->staffUser->id)->exists())->toBeTrue();
});

test('daily task and appointment reminder service sends system notification for today and overdue items without duplicating', function () {
    Cache::flush();
    $this->actingAs($this->staffUser);
    Filament::setTenant($this->matriz);

    $board = TaskBoard::create([
        'school_id' => $this->matriz->id,
        'name' => 'Quadro Matriz',
        'is_default' => true,
    ]);
    $todoCol = TaskColumn::create([
        'task_board_id' => $board->id,
        'name' => 'A Fazer',
        'sort_order' => 1,
        'is_done_column' => false,
    ]);

    $todayCard = TaskCard::create([
        'task_board_id' => $board->id,
        'task_column_id' => $todoCol->id,
        'title' => 'Fechar folha de presença hoje',
        'priority' => 'high',
        'due_date' => Carbon::today()->toDateString(),
        'created_by_user_id' => $this->staffUser->id,
    ]);
    $todayCard->assignedUsers()->attach($this->staffUser->id);

    SchoolAppointment::create([
        'school_id' => $this->matriz->id,
        'title' => 'Entrevista pendente de ontem',
        'type' => SchoolAppointment::TYPE_ATENDIMENTO,
        'priority' => SchoolAppointment::PRIORITY_URGENTE,
        'status' => SchoolAppointment::STATUS_SCHEDULED,
        'date' => Carbon::yesterday()->toDateString(),
        'assigned_to_user_id' => $this->staffUser->id,
        'created_by_user_id' => $this->staffUser->id,
    ]);

    $service = app(TaskAndAppointmentReminderService::class);

    $sentFirst = $service->notifyUserIfNeeded($this->staffUser, $this->matriz, false);
    expect($sentFirst)->toBeTrue()
        ->and($this->staffUser->notifications()->count())->toBe(1);

    $notifData = $this->staffUser->notifications()->first()->data;
    expect($notifData['title'])->toContain('para hoje')
        ->and($notifData['title'])->toContain('em atraso')
        ->and($notifData['body'])->toContain('Fechar folha de presença hoje')
        ->and($notifData['body'])->toContain('Entrevista pendente de ontem');

    // Segunda chamada com os mesmos itens no mesmo dia não duplica
    $sentDuplicate = $service->notifyUserIfNeeded($this->staffUser, $this->matriz, false);
    expect($sentDuplicate)->toBeFalse()
        ->and($this->staffUser->notifications()->count())->toBe(1);

    // Comando artisan também roda com sucesso
    $this->artisan('tasks:notify-daily')->assertSuccessful();
});

<?php

declare(strict_types=1);

use App\Filament\Pages\TaskBoardKanban;
use App\Models\Person;
use App\Models\TaskBoard;
use App\Models\TaskCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
});

function createTestStaffUser(string $name, string $email): User
{
    $person = Person::create([
        'name' => $name,
        'email' => $email,
    ]);

    return User::create([
        'person_id' => $person->id,
        'email' => $email,
        'password' => bcrypt('secret123'),
    ]);
}

test('super admin can access task board kanban page and default board is auto created', function () {
    $user = createTestStaffUser('Admin Gestor', 'admin@example.com');
    $user->assignRole($this->superAdminRole);

    $this->actingAs($user);

    expect(TaskBoard::count())->toBe(0);

    Livewire::test(TaskBoardKanban::class)
        ->assertSuccessful()
        ->assertSee('Quadro Geral da Escola')
        ->assertSee('A Fazer')
        ->assertSee('Em Andamento')
        ->assertSee('Concluído');

    expect(TaskBoard::count())->toBe(1);
    $board = TaskBoard::first();
    expect($board->name)->toBe('Quadro Geral da Escola');
    expect($board->columns)->toHaveCount(4);
});

test('user without permissions cannot access task board kanban page', function () {
    $user = createTestStaffUser('Funcionario Sem Acesso', 'noaccess@example.com');

    $this->actingAs($user);

    Livewire::test(TaskBoardKanban::class)
        ->assertForbidden();
});

test('user can create, edit and delete cards with priority, sector and staff assignees', function () {
    $admin = createTestStaffUser('Admin', 'admin2@example.com');
    $admin->assignRole($this->superAdminRole);

    $employee1 = createTestStaffUser('João Silva', 'joao@example.com');
    $employee2 = createTestStaffUser('Maria Souza', 'maria@example.com');

    $this->actingAs($admin);

    $board = TaskBoard::create([
        'name' => 'Quadro Teste',
        'is_default' => true,
    ]);
    $columnTodo = $board->columns()->create(['name' => 'A Fazer', 'color' => 'gray', 'sort_order' => 1]);
    $columnDone = $board->columns()->create(['name' => 'Concluído', 'color' => 'emerald', 'sort_order' => 2, 'is_done_column' => true]);

    // Create card assigned to employee 1
    Livewire::test(TaskBoardKanban::class)
        ->set('selectedBoardId', $board->id)
        ->call('openCreateCardModal', $columnTodo->id)
        ->set('cardTitle', 'Revisar material do curso')
        ->set('cardColumnId', $columnTodo->id)
        ->set('cardPriority', 'high')
        ->set('cardSector', 'pedagogico')
        ->set('cardDueDate', now()->addDays(2)->format('Y-m-d'))
        ->set('cardAssignedToAll', false)
        ->set('cardAssignedUserIds', [$employee1->id])
        ->call('saveCard')
        ->assertNotified();

    $card = TaskCard::where('title', 'Revisar material do curso')->first();
    expect($card)->not->toBeNull();
    expect($card->priority)->toBe('high');
    expect($card->sector)->toBe('pedagogico');
    expect($card->assigned_to_all)->toBeFalse();
    expect($card->assignedUsers)->toHaveCount(1);
    expect($card->assignedUsers->first()->id)->toBe($employee1->id);

    // Edit card to assign to all employees
    Livewire::test(TaskBoardKanban::class)
        ->set('selectedBoardId', $board->id)
        ->call('openEditCardModal', $card->id)
        ->set('cardAssignedToAll', true)
        ->call('saveCard')
        ->assertNotified();

    $card->refresh();
    expect($card->assigned_to_all)->toBeTrue();

    // Move card to done column -> should mark completed_at
    expect($card->completed_at)->toBeNull();
    Livewire::test(TaskBoardKanban::class)
        ->set('selectedBoardId', $board->id)
        ->call('moveCard', $card->id, $columnDone->id)
        ->assertNotified();

    $card->refresh();
    expect($card->task_column_id)->toBe($columnDone->id);
    expect($card->completed_at)->not->toBeNull();
    expect($card->isCompleted())->toBeTrue();
});

test('user can add and toggle checklist items on a card', function () {
    $admin = createTestStaffUser('Admin 3', 'admin3@example.com');
    $admin->assignRole($this->superAdminRole);

    $this->actingAs($admin);

    $board = TaskBoard::create(['name' => 'Quadro Checklist']);
    $column = $board->columns()->create(['name' => 'Tarefas']);
    $card = $column->cards()->create([
        'task_board_id' => $board->id,
        'title' => 'Organizar evento de boas-vindas',
    ]);

    Livewire::test(TaskBoardKanban::class)
        ->set('selectedBoardId', $board->id)
        ->call('openEditCardModal', $card->id)
        ->set('newChecklistTitle', 'Comprar suprimentos')
        ->call('addChecklistItem')
        ->set('newChecklistTitle', 'Enviar e-mail para alunos')
        ->call('addChecklistItem');

    expect($card->checklists)->toHaveCount(2);

    $item1 = $card->checklists()->where('title', 'Comprar suprimentos')->first();
    expect($item1->is_completed)->toBeFalse();

    // Toggle complete
    Livewire::test(TaskBoardKanban::class)
        ->set('selectedBoardId', $board->id)
        ->call('toggleChecklistItem', $item1->id);

    $item1->refresh();
    expect($item1->is_completed)->toBeTrue();
    expect($item1->completed_at)->not->toBeNull();

    $card->refresh();
    $progress = $card->checklistProgress();
    expect($progress['total'])->toBe(2);
    expect($progress['completed'])->toBe(1);
    expect($progress['percent'])->toBe(50);
});

test('user can filter cards by search, sector, priority and assignee', function () {
    $admin = createTestStaffUser('Admin 4', 'admin4@example.com');
    $admin->assignRole($this->superAdminRole);

    $employee = createTestStaffUser('Carlos', 'carlos@example.com');

    $this->actingAs($admin);

    $board = TaskBoard::create(['name' => 'Quadro Filtros']);
    $col = $board->columns()->create(['name' => 'A Fazer']);

    $cardPed = $col->cards()->create([
        'task_board_id' => $board->id,
        'title' => 'Elaborar simulado bimestral',
        'sector' => 'pedagogico',
        'priority' => 'high',
        'assigned_to_all' => false,
    ]);
    $cardPed->assignedUsers()->attach($employee->id);

    $cardFin = $col->cards()->create([
        'task_board_id' => $board->id,
        'title' => 'Conciliação bancária',
        'sector' => 'financeiro',
        'priority' => 'urgent',
        'assigned_to_all' => true,
    ]);

    $component = Livewire::test(TaskBoardKanban::class)
        ->set('selectedBoardId', $board->id);

    // Initial: both cards are present in the column
    $cards = $component->instance()->getCardsForColumn($col);
    expect($cards)->toHaveCount(2);

    // Filter by sector: pedagogico
    $component->set('filterSector', 'pedagogico');
    $filtered = $component->instance()->getCardsForColumn($col);
    expect($filtered)->toHaveCount(1);
    expect($filtered->first()->title)->toBe('Elaborar simulado bimestral');

    // Filter by priority: urgent
    $component->set('filterSector', '')->set('filterPriority', 'urgent');
    $filtered = $component->instance()->getCardsForColumn($col);
    expect($filtered)->toHaveCount(1);
    expect($filtered->first()->title)->toBe('Conciliação bancária');

    // Filter by search text
    $component->set('filterPriority', '')->set('search', 'simulado');
    $filtered = $component->instance()->getCardsForColumn($col);
    expect($filtered)->toHaveCount(1);
    expect($filtered->first()->title)->toBe('Elaborar simulado bimestral');
});

test('database notifications are sent to assigned users and creator upon completion', function () {
    $admin = createTestStaffUser('Gestor Escola', 'gestor@example.com');
    $admin->assignRole($this->superAdminRole);

    $employee = createTestStaffUser('Professor Bruno', 'bruno@example.com');

    $this->actingAs($admin);

    $board = TaskBoard::create(['name' => 'Quadro Notificações']);
    $colTodo = $board->columns()->create(['name' => 'A Fazer', 'sort_order' => 1]);
    $colDone = $board->columns()->create(['name' => 'Concluído', 'sort_order' => 2, 'is_done_column' => true]);

    expect($employee->notifications()->count())->toBe(0);

    // Create card assigned to Bruno
    Livewire::test(TaskBoardKanban::class)
        ->set('selectedBoardId', $board->id)
        ->call('openCreateCardModal', $colTodo->id)
        ->set('cardTitle', 'Lançar notas da turma A')
        ->set('cardColumnId', $colTodo->id)
        ->set('cardPriority', 'high')
        ->set('cardSector', 'pedagogico')
        ->set('cardAssignedToAll', false)
        ->set('cardAssignedUserIds', [$employee->id])
        ->call('saveCard');

    // Bruno should have received a notification
    expect($employee->notifications()->count())->toBe(1);
    $notif = $employee->notifications()->first();
    expect($notif->data['title'])->toContain('Lançar notas da turma A');

    // Bruno logs in and moves the task to Done
    $card = TaskCard::where('title', 'Lançar notas da turma A')->first();
    $employee->givePermissionTo('View:TaskBoard');
    $this->actingAs($employee);

    expect($admin->notifications()->count())->toBe(0);

    Livewire::test(TaskBoardKanban::class)
        ->set('selectedBoardId', $board->id)
        ->call('moveCard', $card->id, $colDone->id);

    // Gestor should have received completion notification
    expect($admin->notifications()->count())->toBe(1);
    $adminNotif = $admin->notifications()->first();
    expect($adminNotif->data['title'])->toContain('Tarefa concluída');
});

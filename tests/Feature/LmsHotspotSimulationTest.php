<?php

declare(strict_types=1);

use App\Filament\Studio\Pages\LmsStudio;
use App\Livewire\Lms\LmsPlayer;
use App\Models\Contract;
use App\Models\Course;
use App\Models\LmsLesson;
use App\Models\LmsLessonStep;
use App\Models\LmsModule;
use App\Models\LmsStudentLessonProgress;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->person = Person::create([
        'name' => 'Professor Coordenador',
        'email' => 'coordenador@aurosoft.com',
    ]);

    $this->user = User::create([
        'person_id' => $this->person->id,
        'email' => 'coordenador@aurosoft.com',
        'password' => bcrypt('secret'),
    ]);

    $this->user->givePermissionTo(\Spatie\Permission\Models\Permission::findOrCreate('View:LmsStudio', 'web'));
    $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $this->user->assignRole($superAdmin);

    $this->course = Course::create([
        'name' => 'Informática Essencial',
        'workload' => 40,
        'active' => true,
        'ouro_moderno_id' => null,
    ]);

    $this->module = LmsModule::create([
        'course_id' => $this->course->id,
        'title' => 'Módulo 1: Primeiros Passos no Windows',
        'sort_order' => 1,
    ]);

    $this->lesson = LmsLesson::create([
        'module_id' => $this->module->id,
        'title' => 'Aula 01: Explorando a Área de Trabalho',
        'sort_order' => 1,
        'estimated_duration_minutes' => 90,
        'passing_score' => 80.0,
    ]);

    $this->responsiblePerson = Person::create([
        'name' => 'Responsável Legal',
        'email' => 'resp@teste.com',
    ]);

    $this->responsible = \App\Models\Responsible::create([
        'person_id' => $this->responsiblePerson->id,
        'relationship' => \App\Enums\RelationshipEnum::Father,
        'financial_responsible' => true,
    ]);

    $this->student = Person::create([
        'name' => 'Aluno Simulação Teste',
        'email' => 'aluno.sim@teste.com',
    ]);

    $this->contract = Contract::create([
        'contract_number' => 'MAT-2026-SIM',
        'student_id' => $this->student->id,
        'responsible_id' => $this->responsible->id,
        'status' => 'active',
        'start_date' => today(),
        'total_value' => 1000,
        'validity_months' => 12,
        'enrollment_fee' => 0,
        'monthly_payment' => 100,
        'installments_count' => 10,
        'payment_due_day' => 10,
    ]);

    $this->studentUser = User::create([
        'person_id' => $this->student->id,
        'email' => 'aluno.sim@teste.com',
        'password' => bcrypt('secret'),
    ]);
});

test('educator can set hotspot coordinates and save simulation step in lms studio', function () {
    $this->actingAs($this->user);

    \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('studio'));
    Livewire::test(LmsStudio::class, ['course_id' => $this->course->id])
        ->set('selectedModuleId', $this->module->id)
        ->set('selectedLessonId', $this->lesson->id)
        ->call('openCreateStepModal', 'practice')
        ->set('stepType', 'hotspot_simulation')
        ->set('stepTitle', 'Clique no Menu Iniciar')
        ->set('stepInstructions', 'Localize o ícone do Windows e clique com o botão esquerdo.')
        ->set('stepMediaType', 'image')
        ->set('stepMediaUrl', 'https://example.com/windows-desktop.png')
        ->call('setHotspotCoordinates', 4.5, 96.2)
        ->set('hotspotRadius', 5.0)
        ->set('hotspotLabel', 'Menu Iniciar')
        ->set('stepExplanation', 'O menu Iniciar fica no canto inferior esquerdo da tela.')
        ->call('saveStep');

    $step = LmsLessonStep::where('lesson_id', $this->lesson->id)->first();
    expect($step)->not->toBeNull()
        ->and($step->phase)->toBe('practice')
        ->and($step->step_type)->toBe('hotspot_simulation')
        ->and($step->content['hotspot']['x'])->toEqual(4.5)
        ->and($step->content['hotspot']['y'])->toEqual(96.2)
        ->and($step->content['hotspot']['radius'])->toEqual(5.0)
        ->and($step->content['hotspot']['label'])->toBe('Menu Iniciar');
});

test('student clicking inside hotspot coordinates succeeds and records attempt', function () {
    $this->actingAs($this->studentUser);

    $step = LmsLessonStep::create([
        'lesson_id' => $this->lesson->id,
        'phase' => 'practice',
        'step_type' => 'hotspot_simulation',
        'title' => 'Salvar Documento no Excel',
        'sort_order' => 1,
        'content' => [
            'media_type' => 'image',
            'media_url' => 'https://example.com/excel-screen.png',
            'instructions' => 'Clique no ícone de disquete no canto superior esquerdo para salvar.',
            'hotspot' => [
                'x' => 10.0,
                'y' => 8.0,
                'radius' => 6.0,
                'label' => 'Ícone Salvar',
            ],
            'explanation_on_error' => 'O ícone de salvar é o disquete localizado na Barra de Ferramentas de Acesso Rápido.',
        ],
    ]);

    LmsLessonStep::create([
        'lesson_id' => $this->lesson->id,
        'phase' => 'examen',
        'step_type' => 'text',
        'title' => 'Conclusão',
        'sort_order' => 2,
        'content' => ['body' => 'Parabéns'],
    ]);

    Livewire::test(LmsPlayer::class, [
        'lesson' => $this->lesson,
        'contract_id' => $this->contract->id,
    ])
        ->call('clickHotspotCoordinates', 11.2, 9.1) // Dentro do raio de 6.0
        ->assertSet('hotspotHit', true)
        ->assertSet('simulationHotspotClicked', true)
        ->assertSet('lastAnswerCorrect', true)
        ->assertSee('Você localizou e acionou com precisão');

    $progress = LmsStudentLessonProgress::where('contract_id', $this->contract->id)
        ->where('lesson_id', $this->lesson->id)
        ->first();

    expect($progress)->not->toBeNull();
    expect($progress->attempts()->where('is_correct', true)->count())->toBe(1);
});

test('student clicking outside hotspot coordinates fails, tracks mistakes, and allows recovery', function () {
    $this->actingAs($this->studentUser);

    $step = LmsLessonStep::create([
        'lesson_id' => $this->lesson->id,
        'phase' => 'practice',
        'step_type' => 'hotspot_simulation',
        'title' => 'Salvar Documento no Excel',
        'sort_order' => 1,
        'content' => [
            'media_type' => 'image',
            'media_url' => 'https://example.com/excel-screen.png',
            'instructions' => 'Clique no ícone de disquete no canto superior esquerdo para salvar.',
            'hotspot' => [
                'x' => 10.0,
                'y' => 8.0,
                'radius' => 5.0,
                'label' => 'Ícone Salvar',
            ],
            'explanation_on_error' => 'O ícone de salvar é o disquete localizado na Barra de Ferramentas de Acesso Rápido.',
        ],
    ]);

    $player = Livewire::test(LmsPlayer::class, [
        'lesson' => $this->lesson,
        'contract_id' => $this->contract->id,
    ])
        // 1º erro: Clicou longe (x=50, y=50)
        ->call('clickHotspotCoordinates', 50.0, 50.0)
        ->assertSet('hotspotHit', false)
        ->assertSet('lastAnswerCorrect', false)
        ->assertSet('hotspotMistakeCount', 1)
        ->assertSee('O ícone de salvar é o disquete')
        // Tenta avançar com erro pendente (Repetitio inaciana impede avançar)
        ->call('nextStep')
        ->assertSet('currentStepIndex', 0)
        // 2º erro: Clicou longe novamente (x=80, y=20)
        ->call('clickHotspotCoordinates', 80.0, 20.0)
        ->assertSet('hotspotMistakeCount', 2)
        ->assertSee('Dica Inaciana Ativada')
        // Aluno agora clica no ponto correto (x=10.5, y=8.2)
        ->call('clickHotspotCoordinates', 10.5, 8.2)
        ->assertSet('hotspotHit', true)
        ->assertSet('lastAnswerCorrect', true)
        ->assertSee('Você localizou e acionou com precisão');
});

test('educator can set text selection coordinates with direction in lms studio', function () {
    $this->actingAs($this->user);

    \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('studio'));
    Livewire::test(LmsStudio::class, ['course_id' => $this->course->id])
        ->set('selectedModuleId', $this->module->id)
        ->set('selectedLessonId', $this->lesson->id)
        ->call('openCreateStepModal', 'practice')
        ->set('stepType', 'hotspot_simulation')
        ->set('stepTitle', 'Selecione o Título do Documento')
        ->set('stepInstructions', 'Clique no início do título e arraste até o final da esquerda para a direita.')
        ->set('stepMediaType', 'image')
        ->set('stepMediaUrl', 'https://example.com/word-screen.png')
        ->set('hotspotInteractionType', 'select_text')
        ->set('hotspotDirection', 'left_to_right')
        ->call('setHotspotSelection', 15.0, 20.0, 45.0, 20.0)
        ->set('hotspotRadius', 5.0)
        ->set('hotspotLabel', 'Título Principal')
        ->set('stepExplanation', 'Arraste o cursor cobrindo todo o texto do título da esquerda para a direita.')
        ->call('saveStep');

    $step = LmsLessonStep::where('lesson_id', $this->lesson->id)->first();
    expect($step)->not->toBeNull()
        ->and($step->step_type)->toBe('hotspot_simulation')
        ->and($step->content['hotspot']['interaction_type'])->toBe('select_text')
        ->and($step->content['hotspot']['direction'])->toBe('left_to_right')
        ->and($step->content['hotspot']['x'])->toEqual(15.0)
        ->and($step->content['hotspot']['y'])->toEqual(20.0)
        ->and($step->content['hotspot']['end_x'])->toEqual(45.0)
        ->and($step->content['hotspot']['end_y'])->toEqual(20.0);
});

test('student dragging to select text from left to right succeeds', function () {
    $this->actingAs($this->studentUser);

    $step = LmsLessonStep::create([
        'lesson_id' => $this->lesson->id,
        'phase' => 'practice',
        'step_type' => 'hotspot_simulation',
        'title' => 'Selecione o Título no Word',
        'sort_order' => 1,
        'content' => [
            'media_type' => 'image',
            'media_url' => 'https://example.com/word-screen.png',
            'instructions' => 'Selecione o texto da esquerda para a direita.',
            'hotspot' => [
                'interaction_type' => 'select_text',
                'direction' => 'left_to_right',
                'x' => 15.0,
                'y' => 20.0,
                'end_x' => 45.0,
                'end_y' => 20.0,
                'radius' => 6.0,
                'label' => 'Título do Trabalho',
            ],
            'explanation_on_error' => 'Inicie no início do texto e arraste até o final.',
        ],
    ]);

    LmsLessonStep::create([
        'lesson_id' => $this->lesson->id,
        'phase' => 'examen',
        'step_type' => 'text',
        'title' => 'Conclusão',
        'sort_order' => 2,
        'content' => ['body' => 'Parabéns'],
    ]);

    Livewire::test(LmsPlayer::class, [
        'lesson' => $this->lesson,
        'contract_id' => $this->contract->id,
    ])
        // Aluno arrasta do ponto inicial (15.5, 20.1) até o final (44.8, 19.8) da esquerda para a direita
        ->call('dragHotspotCoordinates', 15.5, 20.1, 44.8, 19.8)
        ->assertSet('hotspotHit', true)
        ->assertSet('simulationHotspotClicked', true)
        ->assertSet('lastAnswerCorrect', true)
        ->assertSee('Você selecionou "Título do Trabalho" com precisão')
        ->assertSee('Texto Selecionado');

    $progress = LmsStudentLessonProgress::where('contract_id', $this->contract->id)
        ->where('lesson_id', $this->lesson->id)
        ->first();

    expect($progress)->not->toBeNull();
    expect($progress->attempts()->where('is_correct', true)->count())->toBe(1);
});

test('student dragging text in reverse direction fails when left to right is required', function () {
    $this->actingAs($this->studentUser);

    $step = LmsLessonStep::create([
        'lesson_id' => $this->lesson->id,
        'phase' => 'practice',
        'step_type' => 'hotspot_simulation',
        'title' => 'Selecione o Título no Word',
        'sort_order' => 1,
        'content' => [
            'media_type' => 'image',
            'media_url' => 'https://example.com/word-screen.png',
            'instructions' => 'Selecione o texto da esquerda para a direita.',
            'hotspot' => [
                'interaction_type' => 'select_text',
                'direction' => 'left_to_right',
                'x' => 15.0,
                'y' => 20.0,
                'end_x' => 45.0,
                'end_y' => 20.0,
                'radius' => 6.0,
                'label' => 'Título do Trabalho',
            ],
        ],
    ]);

    $player = Livewire::test(LmsPlayer::class, [
        'lesson' => $this->lesson,
        'contract_id' => $this->contract->id,
    ])
        // Aluno arrastou da DIREITA para a ESQUERDA (iniciou em 45 e terminou em 15)
        ->call('dragHotspotCoordinates', 45.0, 20.0, 15.0, 20.0)
        ->assertSet('hotspotHit', false)
        ->assertSet('lastAnswerCorrect', false)
        ->assertSee('Atenção ao sentido: selecione o texto da esquerda para a direita')
        // 2º erro para ativar dica inaciana
        ->call('dragHotspotCoordinates', 80.0, 80.0, 90.0, 90.0)
        ->assertSet('hotspotMistakeCount', 2)
        ->assertSee('Dica Inaciana Ativada')
        ->assertSee('1. Início')
        ->assertSee('2. Solte');
});

test('educator can set text typing simulation step in lms studio', function () {
    $this->actingAs($this->user);

    \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('studio'));
    Livewire::test(LmsStudio::class, ['course_id' => $this->course->id])
        ->set('selectedModuleId', $this->module->id)
        ->set('selectedLessonId', $this->lesson->id)
        ->call('openCreateStepModal', 'practice')
        ->set('stepType', 'hotspot_simulation')
        ->set('stepTitle', 'Digite a Fórmula de Soma no Excel')
        ->set('stepInstructions', 'Clique na célula B10 e digite a fórmula =SOMA(B2:B9) para somar os valores.')
        ->set('stepMediaType', 'image')
        ->set('stepMediaUrl', 'https://example.com/excel-sheet.png')
        ->set('hotspotInteractionType', 'type_text')
        ->set('hotspotExpectedText', '=SOMA(B2:B9)')
        ->set('hotspotCaseSensitive', false)
        ->set('hotspotTrimWhitespace', true)
        ->call('setHotspotCoordinates', 30.5, 45.2)
        ->set('hotspotLabel', 'Célula B10')
        ->call('saveStep');

    $step = LmsLessonStep::where('lesson_id', $this->lesson->id)->first();
    expect($step)->not->toBeNull()
        ->and($step->content['hotspot']['interaction_type'])->toBe('type_text')
        ->and($step->content['hotspot']['expected_text'])->toBe('=SOMA(B2:B9)')
        ->and($step->content['hotspot']['case_sensitive'])->toBeFalse()
        ->and($step->content['hotspot']['trim_whitespace'])->toBeTrue()
        ->and($step->content['hotspot']['x'])->toEqual(30.5)
        ->and($step->content['hotspot']['y'])->toEqual(45.2);
});

test('student typing exact or case-insensitive text succeeds in simulation', function () {
    $this->actingAs($this->studentUser);

    $step = LmsLessonStep::create([
        'lesson_id' => $this->lesson->id,
        'phase' => 'practice',
        'step_type' => 'hotspot_simulation',
        'title' => 'Digite o Comando no Prompt',
        'sort_order' => 1,
        'content' => [
            'media_type' => 'image',
            'media_url' => 'https://example.com/cmd.png',
            'instructions' => 'Digite ipconfig e pressione Enter.',
            'hotspot' => [
                'interaction_type' => 'type_text',
                'expected_text' => 'ipconfig',
                'case_sensitive' => false,
                'trim_whitespace' => true,
                'x' => 20.0,
                'y' => 30.0,
                'label' => 'Prompt de Comando',
            ],
        ],
    ]);

    // Aluno digita com maiúsculas e espaços extras: '  IPCONFIG  ' (deve aceitar pois case_sensitive=false e trim=true)
    LmsLessonStep::create([
        'lesson_id' => $this->lesson->id,
        'phase' => 'examen',
        'step_type' => 'text',
        'title' => 'Conclusão',
        'sort_order' => 2,
        'content' => ['body' => 'Parabéns'],
    ]);

    Livewire::test(LmsPlayer::class, [
        'lesson' => $this->lesson,
        'contract_id' => $this->contract->id,
    ])
        ->call('submitTypedText', '  IPCONFIG  ')
        ->assertSet('hotspotHit', true)
        ->assertSet('simulationHotspotClicked', true)
        ->assertSet('lastAnswerCorrect', true)
        ->assertSee('Você digitou "Prompt de Comando" com perfeição');

    $progress = LmsStudentLessonProgress::where('contract_id', $this->contract->id)
        ->where('lesson_id', $this->lesson->id)
        ->first();

    expect($progress)->not->toBeNull();
    expect($progress->attempts()->where('is_correct', true)->count())->toBe(1);
});

test('student typing wrong text fails, records attempt, and shows hint after 2 mistakes', function () {
    $this->actingAs($this->studentUser);

    $step = LmsLessonStep::create([
        'lesson_id' => $this->lesson->id,
        'phase' => 'practice',
        'step_type' => 'hotspot_simulation',
        'title' => 'Fórmula Excel',
        'sort_order' => 1,
        'content' => [
            'media_type' => 'image',
            'media_url' => 'https://example.com/excel.png',
            'instructions' => 'Digite =SOMA(A1:A5) e dê Enter.',
            'hotspot' => [
                'interaction_type' => 'type_text',
                'expected_text' => '=SOMA(A1:A5)',
                'case_sensitive' => true,
                'trim_whitespace' => true,
                'x' => 25.0,
                'y' => 35.0,
                'label' => 'Fórmula',
            ],
            'explanation_on_error' => 'Atenção aos nomes das células e parênteses da fórmula.',
        ],
    ]);

    LmsLessonStep::create([
        'lesson_id' => $this->lesson->id,
        'phase' => 'examen',
        'step_type' => 'text',
        'title' => 'Conclusão',
        'sort_order' => 2,
        'content' => ['body' => 'Parabéns'],
    ]);

    Livewire::test(LmsPlayer::class, [
        'lesson' => $this->lesson,
        'contract_id' => $this->contract->id,
    ])
        // 1º erro: digitou errado
        ->call('submitTypedText', '=MEDIA(A1:A5)')
        ->assertSet('hotspotHit', false)
        ->assertSet('lastAnswerCorrect', false)
        ->assertSet('hotspotMistakeCount', 1)
        ->assertSee('Atenção aos nomes das células e parênteses da fórmula')
        // 2º erro: digitou em minúsculo com case_sensitive = true
        ->call('submitTypedText', '=soma(a1:a5)')
        ->assertSet('hotspotHit', false)
        ->assertSet('hotspotMistakeCount', 2)
        ->assertSee('Dica Inaciana')
        ->assertSee('=SOMA(A1:A5)')
        // 3ª tentativa: digitou perfeitamente
        ->call('submitTypedText', '=SOMA(A1:A5)')
        ->assertSet('hotspotHit', true)
        ->assertSet('lastAnswerCorrect', true);
});

test('hotspot simulation in player does not render duplicate image and renders rich formatted instructions', function () {
    $this->actingAs($this->studentUser);

    $step = LmsLessonStep::create([
        'lesson_id' => $this->lesson->id,
        'phase' => 'practice',
        'step_type' => 'hotspot_simulation',
        'title' => 'Prática Formatada Sem Duplicação',
        'sort_order' => 1,
        'content' => [
            'media_type' => 'image',
            'media_url' => 'https://example.com/unique_software_screenshot.png',
            'instructions' => 'Por favor clique no botão **Salvar** e utilize o atalho <kbd>Ctrl+S</kbd> no <u>menu principal</u>.',
            'hotspot' => [
                'interaction_type' => 'click',
                'x' => 50.0,
                'y' => 50.0,
                'radius' => 6.0,
                'label' => 'Salvar',
            ],
        ],
    ]);

    $response = Livewire::test(LmsPlayer::class, [
        'lesson' => $this->lesson,
        'contract_id' => $this->contract->id,
    ]);

    // O workspace interativo deve conter a imagem da simulação
    $response->assertSee('alt="Interface do Software"', false);
    $response->assertSee('https://example.com/unique_software_screenshot.png');

    // A imagem NÃO deve aparecer na seção de mídia duplicada (que possui "Ampliar Imagem" em cima)
    $html = $response->html();
    $mediaOccurrences = substr_count($html, 'https://example.com/unique_software_screenshot.png');
    // Deve aparecer exatamente 1 vez no workspace interativo (ou no botão zoom da barra do simulador)
    expect($mediaOccurrences)->toBeLessThanOrEqual(2); // 1 na tag img do workspace + 1 no wire:click do zoom da barra de título

    // Não deve conter a tag img duplicada da Seção de Mídia
    expect($html)->not->toContain('<img src="https://example.com/unique_software_screenshot.png" alt="Prática Formatada Sem Duplicação"');

    // Instruções formatadas com negrito, sublinhado e kbd
    $response->assertSee('<strong>Salvar</strong>', false);
    $response->assertSee('<u>menu principal</u>', false);
    $response->assertSee('<kbd>Ctrl+S</kbd>', false);
});

test('hotspot simulation uses body fallback when instructions field is empty', function () {
    $this->actingAs($this->studentUser);

    LmsLessonStep::create([
        'lesson_id' => $this->lesson->id,
        'phase' => 'practice',
        'step_type' => 'hotspot_simulation',
        'title' => 'Fallback de Instruções',
        'sort_order' => 1,
        'content' => [
            'media_type' => 'image',
            'media_url' => 'https://example.com/fallback.png',
            'instructions' => '',
            'body' => 'Clique no **botão direito** para abrir o menu contextual.',
            'hotspot' => [
                'interaction_type' => 'right_click',
                'x' => 40.0,
                'y' => 40.0,
                'radius' => 6.0,
            ],
        ],
    ]);

    LmsLessonStep::create([
        'lesson_id' => $this->lesson->id,
        'phase' => 'examen',
        'step_type' => 'text',
        'title' => 'Conclusão',
        'sort_order' => 2,
        'content' => ['body' => 'Parabéns'],
    ]);

    Livewire::test(LmsPlayer::class, [
        'lesson' => $this->lesson,
        'contract_id' => $this->contract->id,
    ])
        ->assertSee('Clique no')
        ->assertSee('<strong>botão direito</strong>', false);
});




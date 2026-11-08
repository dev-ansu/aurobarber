<?php

use App\Enums\ContactTypeEnum;
use App\Enums\RelationshipEnum;
use App\Filament\Pages\StudentContactsReport;
use App\Filament\Resources\Contracts\RelationManagers\OuroModernoLogsRelationManager;
use App\Models\Classroom;
use App\Models\Contact;
use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\Formation;
use App\Models\Person;
use App\Models\Responsible;
use App\Models\StudentSchedule;
use App\Services\OuroModernoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config([
        'services.ouro_moderno.url' => 'http://lms.test',
        'services.ouro_moderno.token' => 'ef782c82',
    ]);
});

test('Ouro Moderno logs query calls /api/aluno/logs directly with cod_int and token', function (): void {
    Http::fake([
        'lms.test/api/aluno/logs*' => Http::response([
            'Logs' => [
                'Itens' => [
                    [
                        'id' => '1272968',
                        'tipo' => '1',
                        'curso' => '126',
                        'aula' => '1',
                        'data' => '15/10/2026 21:02:13',
                        'timestamp' => 1792108933000,
                        'userId' => '5924',
                        'id_aluno' => '5924',
                        'cod_int' => '25',
                        'aluno' => 'DEMO-2026-002',
                        'autor' => 'Pedro Lima (Demonstra&ccedil;&atilde;o)',
                        'ip' => '100.71.233.111',
                        'logtxt' => 'Aluno realizou o teste e atingiu a média. Nota: 100',
                        'metadata' => json_encode([
                            [
                                'pergunta' => 'O que são TAGS.',
                                'certa' => 'São conjuntos de caracteres.',
                                'resposta' => 'São conjuntos de caracteres.',
                                'acertou' => 1,
                            ],
                        ]),
                        'atividade' => [
                            'tempo' => '56',
                            'inicio' => '15/10/2026 21:00:50',
                            'fim' => '15/10/2026 21:02:14',
                        ],
                    ],
                ],
                'Total' => 1,
            ],
            'Code' => '1',
        ]),
    ]);

    $service = app(OuroModernoService::class);
    $response = $service->studentLogs(25);

    expect(data_get($response, 'Logs.Total'))->toBe(1)
        ->and(data_get($response, 'Logs.Itens.0.cod_int'))->toBe('25');

    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/api/aluno/logs')
        && $request->method() === 'GET'
        && (int) $request['cod_int'] === 25
        && $request['token'] === 'ef782c82');
});

test('Student contacts report filters and exports CSV with contacts and formations', function (): void {
    $student = Person::create([
        'name' => 'Carlos Silva',
        'email' => 'carlos@example.com',
        'cpf' => '123.456.789-00',
        'birth_date' => '2005-04-10',
    ]);

    Contact::create([
        'person_id' => $student->id,
        'type' => ContactTypeEnum::WhatsApp,
        'value' => '11999998888',
    ]);

    $responsiblePerson = Person::create([
        'name' => 'Maria Silva',
        'email' => 'maria@example.com',
    ]);

    $responsible = Responsible::create([
        'person_id' => $responsiblePerson->id,
        'relationship' => RelationshipEnum::Mother,
        'financial_responsible' => true,
    ]);

    $formation = Formation::create(['name' => 'Desenvolvedor Web']);
    $classroom = Classroom::create(['name' => 'Laboratório 1', 'capacity' => 20]);

    $contract = Contract::create([
        'contract_number' => 2026001,
        'student_id' => $student->id,
        'responsible_id' => $responsible->id,
        'start_date' => '2026-01-10',
        'end_date' => '2026-12-10',
        'status' => 'active',
        'total_value' => 2400.00,
    ]);

    $item = ContractItem::create([
        'contract_id' => $contract->id,
        'formation_id' => $formation->id,
    ]);

    StudentSchedule::create([
        'contract_item_id' => $item->id,
        'classroom_id' => $classroom->id,
        'weekday' => 1,
        'start_hour' => '14:00:00',
        'end_hour' => '15:00:00',
    ]);

    $page = new StudentContactsReport;
    $page->contractStatus = 'active';
    $page->weekday = '1';
    $page->classroomId = (string) $classroom->id;
    $page->formationId = (string) $formation->id;

    $results = $page->records();
    expect($results)->toHaveCount(1)
        ->and($results->first()->student->name)->toBe('Carlos Silva');

    $metrics = $page->metrics();
    expect($metrics['total_contracts'])->toBe(1)
        ->and($metrics['with_whatsapp'])->toBe(1);

    // Test with non-matching filter
    $page->weekday = '5'; // Friday
    expect($page->records())->toHaveCount(0);
});

test('Ouro Moderno logs relation manager decodes entities, formats durations and inspects test metadata', function (): void {
    $manager = new OuroModernoLogsRelationManager;
    $manager->ownerRecord = new Contract;

    expect($manager->cleanText('Pedro Lima (Demonstra&ccedil;&atilde;o)'))->toBe('Pedro Lima (Demonstração)')
        ->and($manager->formatDuration(56))->toBe('56s')
        ->and($manager->formatDuration(130))->toBe('2min 10s')
        ->and($manager->formatDuration(0))->toBe('—');

    $manager->logs = [
        [
            'id' => '1272968',
            'tipo' => '1',
            'curso' => '126',
            'aula' => '1',
            'data' => '15/10/2026 21:02:13',
            'logtxt' => 'Aluno realizou o teste. Nota: 100',
            'metadata' => json_encode([
                [
                    'pergunta' => 'O que são TAGS.',
                    'certa' => 'São elementos.',
                    'resposta' => 'São elementos.',
                    'acertou' => 1,
                ],
                [
                    'pergunta' => 'O que é HTML.',
                    'certa' => 'Linguagem de marcação.',
                    'resposta' => 'Linguagem de programação.',
                    'acertou' => 0,
                ],
            ]),
        ],
    ];

    $manager->showTestDetails(0);

    expect($manager->selectedTest)->not->toBeNull()
        ->and($manager->selectedTest['total'])->toBe(2)
        ->and($manager->selectedTest['correct'])->toBe(1)
        ->and($manager->selectedTest['questions'])->toHaveCount(2);

    $manager->closeTestDetails();
    expect($manager->selectedTest)->toBeNull();
});

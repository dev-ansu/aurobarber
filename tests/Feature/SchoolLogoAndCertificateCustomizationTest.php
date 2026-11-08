<?php

use App\Http\Middleware\EnsureLivewireChildrenMemoExists;
use App\Models\Contract;
use App\Models\Course;
use App\Models\Person;
use App\Models\School;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

uses(RefreshDatabase::class);

beforeEach(function () {
    $person = Person::firstOrCreate(
        ['email' => 'admin.test@aurosoft.com'],
        ['name' => 'Admin Teste']
    );

    $user = User::firstOrCreate(
        ['email' => 'admin.test@aurosoft.com'],
        [
            'person_id' => $person->id,
            'name' => 'Admin Teste',
            'password' => bcrypt('password'),
        ]
    );
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

test('ensure livewire children memo middleware injects empty children array if missing', function () {
    $middleware = new EnsureLivewireChildrenMemoExists();

    $snapshotWithoutChildren = json_encode([
        'data' => ['name' => 'Teste'],
        'memo' => ['id' => 'comp-123'],
    ]);

    $request = Request::create('/livewire/update', 'POST', [
        'components' => [
            [
                'snapshot' => $snapshotWithoutChildren,
                'updates' => [],
                'calls' => [],
            ],
        ],
    ]);
    $request->headers->set('X-Livewire', 'true');

    $response = $middleware->handle($request, function ($req) {
        $components = $req->input('components');
        $snap = json_decode($components[0]['snapshot'], true);

        expect($snap['memo'])->toHaveKey('children')
            ->and($snap['memo']['children'])->toBeArray()->toBeEmpty();

        return new Response('OK');
    });

    expect($response->getContent())->toBe('OK');
});

test('school model accepts logo_path and includes it in toContractSnapshot', function () {
    $school = School::firstOrNew(['id' => 1]);
    $school->legal_name = 'Escola Teste LTDA';
    $school->trade_name = 'Aurosoft Cursos';
    $school->cnpj = '12.345.678/0001-99';
    $school->logo_path = 'schools/logos/test-logo.png';
    $school->save();

    $snapshot = $school->toContractSnapshot();

    expect($snapshot)->toHaveKey('logo_path')
        ->and($snapshot['logo_path'])->toBe('schools/logos/test-logo.png')
        ->and($snapshot)->toHaveKey('logo_url')
        ->and($snapshot['logo_url'])->toContain('test-logo.png');
});

test('certificate route accepts query parameters to customize printed certificate data', function () {
    $school = School::firstOrNew(['id' => 1]);
    $school->legal_name = 'Escola Teste LTDA';
    $school->trade_name = 'Aurosoft Cursos';
    $school->cnpj = '12.345.678/0001-99';
    $school->logo_path = 'schools/logos/test-logo.png';
    $school->save();

    $student = Person::create(['name' => 'Aluno Formando', 'email' => 'formando@teste.com']);
    $contract = Contract::create([
        'student_id' => $student->id,
        'contract_number' => 'CTR-CERT-01',
        'status' => 'draft',
        'start_date' => '2026-01-10',
        'total_value' => 1200,
    ]);

    $response = $this->get(route('contracts.certificate', [
        'contract' => $contract,
        'course_title' => 'Gestão e Tecnologia Empresarial Avançada',
        'total_workload' => 240,
        'average_grade' => 98.5,
        'book_number' => '05',
        'sheet_number' => '042',
        'registration_number' => '9999',
        'school_representative' => 'Prof. Diretor Especial',
        'legal_base' => 'Certificado regulamentado pela Resolução CEE 99/2026.',
    ]));

    $response->assertOk();
    $content = $response->getContent();

    expect($content)->toContain('Gestão e Tecnologia Empresarial Avançada')
        ->and($content)->toContain('240 horas')
        ->and($content)->toContain('98,5%')
        ->and($content)->toContain('Livro de Registro: <strong>05</strong>')
        ->and($content)->toContain('Folha nº: <strong>042</strong>')
        ->and($content)->toContain('Reg. nº: <strong>9999</strong>')
        ->and($content)->toContain('Prof. Diretor Especial')
        ->and($content)->toContain('Resolução CEE 99/2026')
        ->and($content)->toContain('test-logo.png');
});

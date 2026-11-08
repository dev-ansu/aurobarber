<?php

use App\Models\Course;
use App\Services\CourseSyncService;
use App\Services\OuroModernoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config([
        'services.ouro_moderno.url' => 'http://lms.test',
        'services.ouro_moderno.token' => 'token-de-teste-nao-real',
    ]);
});

test('Ouro Moderno client submits the token as form data and reads available courses', function () {
    Http::fake([
        'lms.test/api/cursos/lista' => Http::response([
            ['id' => 17, 'curso' => 'Informática', 'carga' => 40],
        ]),
    ]);

    $courses = app(OuroModernoService::class)->courses();

    expect($courses)->toBe([
        ['id' => 17, 'curso' => 'Informática', 'carga' => 40],
    ]);

    Http::assertSent(fn (Request $request): bool => $request->url() === 'http://lms.test/api/cursos/lista'
        && $request->method() === 'POST'
        && $request['token'] === 'token-de-teste-nao-real');
});

test('Ouro Moderno client exposes documented enrollment and progress endpoints', function () {
    Http::fake(['lms.test/*' => Http::response(['ok' => true])]);
    $api = app(OuroModernoService::class);

    $api->enrollCourses(42, [7, 9, 7]);
    $api->studentCourseStatus(42, 7);
    $api->studentLessonStatus(42, 7);
    $api->studentTestStatus(42, 7);

    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/api/alunos/matricular/')
        && (int) $request['cod_int'] === 42
        && $request['cursos'] === '7,9');
    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/api/aluno/curso/situacao')
        && (int) $request['cod_int'] === 42
        && (int) $request['curso'] === 7);
    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/api/aluno/aulas/situacao')
        && (int) $request['curso'] === 7);
    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/api/aluno/testes/situacao')
        && (int) $request['curso'] === 7);
});

test('Ouro Moderno client reports network failures without leaking credentials', function () {
    config(['services.ouro_moderno.url' => '']);

    expect(fn () => app(OuroModernoService::class)->courses())
        ->toThrow(RuntimeException::class, 'Configure OURO_MODERNO_URL e OURO_MODERNO_TOKEN');

    config(['services.ouro_moderno.url' => 'http://lms.test']);
    Http::fake(['*' => Http::response(['error' => 'unauthorized'], 401)]);

    expect(fn () => app(OuroModernoService::class)->courses())
        ->toThrow(RuntimeException::class, 'HTTP 401');
});

test('course catalog synchronization updates by LMS ID and rejects malformed payloads atomically', function () {
    Http::fake([
        'lms.test/api/cursos/lista' => Http::sequence()
            ->push([
                ['id' => 7, 'curso' => 'Curso atualizado', 'carga' => '32'],
                ['id' => 9, 'curso' => 'Novo curso', 'carga' => 24],
            ])
            ->push([
                ['id' => 11, 'curso' => 'Válido'],
                ['curso' => 'Sem identificador'],
            ]),
    ]);
    $course = Course::create(['name' => 'Nome antigo', 'ouro_moderno_id' => 7]);

    expect(app(CourseSyncService::class)->syncCourses())->toBe(2)
        ->and($course->fresh()->name)->toBe('Curso atualizado')
        ->and($course->fresh()->workload)->toBe(32)
        ->and(Course::query()->where('ouro_moderno_id', 9)->value('name'))->toBe('Novo curso');

    expect(fn () => app(CourseSyncService::class)->syncCourses())
        ->toThrow(RuntimeException::class, 'nenhuma alteração foi salva');
    expect(Course::query()->where('ouro_moderno_id', 11)->exists())->toBeFalse();
});

test('Ouro Moderno client supports all gamification API endpoints', function () {
    Http::fake([
        'lms.test/api/game/aluno/historico-moedas*' => Http::response([
            'historico' => [
                ['data' => '2026-10-16', 'moedas' => 20, 'descricao' => 'Bônus aula'],
            ],
        ]),
        'lms.test/api/game/aluno/historico*' => Http::response([
            'historico' => [
                ['data' => '2026-10-16', 'pontos' => 10, 'descricao' => 'Exercício concluído'],
            ],
        ]),
        'lms.test/api/game/aluno?*' => Http::response([
            'id_aluno' => 4,
            'total_points' => 350,
            'total_coins' => 120,
            'level' => ['name' => 'Mestre'],
            'achievements' => [
                ['nome' => 'Primeira Aula', 'icone' => '🥇'],
            ],
        ]),
        'lms.test/api/game/pontos/adicionar' => Http::response(['success' => true, 'novos_pontos' => 360]),
        'lms.test/api/game/pontos/remover' => Http::response(['success' => true, 'novos_pontos' => 340]),
        'lms.test/api/game/moedas/adicionar' => Http::response(['success' => true, 'novas_moedas' => 140]),
        'lms.test/api/game/moedas/remover' => Http::response(['success' => true, 'novas_moedas' => 110]),
        'lms.test/api/game/acoes*' => Http::response([
            'pontos_por_acao' => [
                'concluir_aula' => 50,
                'fazer_exercicio' => 20,
            ],
        ]),
    ]);

    $service = app(OuroModernoService::class);

    $profile = $service->gamificationStudentProfile(4);
    expect($profile['total_points'])->toBe(350);

    $addPts = $service->gamificationAddPoints(4, 10, 'Exercício em aula');
    expect($addPts['novos_pontos'])->toBe(360);

    $remPts = $service->gamificationRemovePoints(4, 10, 'Estorno');
    expect($remPts['novos_pontos'])->toBe(340);

    $addCoins = $service->gamificationAddCoins(4, 20, 'Bônus');
    expect($addCoins['novas_moedas'])->toBe(140);

    $remCoins = $service->gamificationRemoveCoins(4, 10, 'Estorno bônus');
    expect($remCoins['novas_moedas'])->toBe(110);

    $ptsHist = $service->gamificationPointsHistory(4);
    expect($ptsHist['historico'])->toHaveCount(1);

    $coinHist = $service->gamificationCoinsHistory(4);
    expect($coinHist['historico'])->toHaveCount(1);

    $actions = $service->gamificationActions();
    expect($actions['pontos_por_acao']['concluir_aula'])->toBe(50);
});

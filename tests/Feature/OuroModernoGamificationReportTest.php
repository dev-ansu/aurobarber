<?php

use App\Filament\Pages\OuroModernoGamificationReport;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config([
        'services.ouro_moderno.url' => 'http://lms.test',
        'services.ouro_moderno.token' => 'token-test',
    ]);
});

test('gamification report page loads players, allows viewing profile, history, and submitting adjustments', function () {
    $adminRole = Role::findOrCreate('super_admin', 'web');
    $person = Person::create([
        'name' => 'Coordenador Gamificacao',
        'email' => 'coord@example.com',
    ]);
    $user = User::create([
        'person_id' => $person->id,
        'email' => 'coord@example.com',
        'password' => 'secret',
    ]);
    $user->assignRole($adminRole);
    $user->givePermissionTo(
        Permission::findOrCreate('View:OuroModernoGamificationReport', 'web'),
        Permission::findOrCreate('Export:OuroModernoGamificationReport', 'web'),
    );

    $this->actingAs($user);

    Http::fake([
        'lms.test/api/game/jogadores*' => Http::response([
            'players' => [
                [
                    'user_id' => 4,
                    'user_name' => 'Lucas Aluno',
                    'total_points' => 450,
                    'total_coins' => 80,
                    'total_achievements' => 3,
                    'level' => ['current_level' => 'Avançado'],
                    'gamificacao_efetiva' => true,
                    'gamificacao_label' => 'Ativa',
                ],
            ],
            'current_page' => 1,
            'total_pages' => 1,
            'total_records' => 1,
            'has_previous' => false,
            'has_next' => false,
        ]),
        'lms.test/api/game/ranking*' => Http::response([
            'ranking' => [
                [
                    'ranking_position' => 1,
                    'user_id' => 4,
                    'user_name' => 'Lucas Aluno',
                    'total_points' => 450,
                    'sala_activities' => 12,
                    'level' => ['name' => 'Avançado'],
                ],
            ],
        ]),
        'lms.test/api/game/aluno/historico-moedas*' => Http::response([
            'historico' => [
                ['data' => '16/10/2026', 'moedas' => 20, 'descricao' => 'Bônus aula'],
            ],
        ]),
        'lms.test/api/game/aluno/historico*' => Http::response([
            'historico' => [
                ['data' => '16/10/2026', 'pontos' => 15, 'descricao' => 'Exercício correto'],
            ],
        ]),
        'lms.test/api/game/aluno?*' => Http::response([
            'id_aluno' => 4,
            'user_name' => 'Lucas Aluno',
            'total_points' => 450,
            'total_coins' => 80,
            'ranking_position' => 1,
            'level' => ['name' => 'Avançado'],
            'achievements' => [
                ['nome' => 'Mestre dos Testes', 'descricao' => 'Acertou 10 testes seguidos', 'icone' => '🏆'],
            ],
        ]),
        'lms.test/api/game/pontos/adicionar' => Http::response(['success' => true, 'novos_pontos' => 470]),
        'lms.test/api/game/acoes*' => Http::response([
            'pontos_por_acao' => [
                'concluir_aula' => 50,
                'fazer_exercicio' => 15,
            ],
        ]),
    ]);

    expect(OuroModernoGamificationReport::canAccess())->toBeTrue();

    // 1. Carrega relatório
    $component = Livewire::test(OuroModernoGamificationReport::class)
        ->call('loadReport')
        ->assertSee('Lucas Aluno')
        ->assertSee('450')
        ->assertSee('80')
        ->assertSee('Avançado')
        ->assertSee('🥇 1º Lugar');

    // 2. Abre perfil do aluno com conquistas
    $component->call('viewStudentProfile', 4)
        ->assertSee('Mestre dos Testes')
        ->assertSee('Acertou 10 testes seguidos')
        ->assertSee('Exercício correto');

    // 3. Alterna para extrato de moedas
    $component->call('switchHistoryTab', 'coins')
        ->assertSee('Bônus aula');

    // 4. Abre modal de regras de pontuação
    $component->call('openRulesModal')
        ->assertSee('Concluir Aula')
        ->assertSee('+50 pts');

    // 5. Abre modal de lançamento de pontos e confirma
    $component->call('openAdjustmentModal', 4, 'Lucas Aluno', 'points_add')
        ->set('adjustAmount', 20)
        ->set('adjustDescription', 'Destaque no projeto prático')
        ->call('submitAdjustment')
        ->assertHasNoErrors();
});

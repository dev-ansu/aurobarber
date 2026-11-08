<?php

declare(strict_types=1);

use App\Enums\RelationshipEnum;
use App\Filament\Pages\SatisfactionSurveyReport;
use App\Models\Contract;
use App\Models\Person;
use App\Models\Responsible;
use App\Models\SatisfactionSurvey;
use App\Models\User;
use App\Services\SatisfactionSurveyReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('satisfaction survey report service aggregates cards, stars distribution, nps and multiple choice', function () {
    $survey = SatisfactionSurvey::create([
        'title' => 'Pesquisa Clima e Instalações',
        'is_active' => true,
    ]);

    $qStars = $survey->questions()->create([
        'question' => 'Como você avalia a infraestrutura dos laboratórios?',
        'type' => 'rating_5',
        'sort_order' => 1,
    ]);

    $qNps = $survey->questions()->create([
        'question' => 'Qual a probabilidade de você recomendar a escola?',
        'type' => 'nps_10',
        'sort_order' => 2,
    ]);

    $qChoice = $survey->questions()->create([
        'question' => 'Qual o melhor horário para suas aulas?',
        'type' => 'multiple_choice',
        'options' => ['Manhã', 'Tarde', 'Noite'],
        'sort_order' => 3,
    ]);

    $qText = $survey->questions()->create([
        'question' => 'Deixe uma sugestão de melhoria:',
        'type' => 'text',
        'sort_order' => 4,
    ]);

    // Aluno 1: Nota 5 estrelas, NPS 10, Tarde, Comentário elogioso
    $student1 = Person::create(['name' => 'Aluno Alfa', 'email' => 'alfa@example.com']);
    $resp1 = Responsible::create(['person_id' => $student1->id, 'relationship' => RelationshipEnum::Outro, 'financial_responsible' => true]);
    $contract1 = Contract::create(['contract_number' => 'MATR-S1', 'student_id' => $student1->id, 'responsible_id' => $resp1->id, 'status' => 'active', 'start_date' => today(), 'total_value' => 1000]);

    $r1 = $survey->responses()->create(['contract_id' => $contract1->id, 'student_id' => $student1->id]);
    $r1->answers()->create(['satisfaction_survey_question_id' => $qStars->id, 'rating_value' => 5]);
    $r1->answers()->create(['satisfaction_survey_question_id' => $qNps->id, 'rating_value' => 10]);
    $r1->answers()->create(['satisfaction_survey_question_id' => $qChoice->id, 'text_value' => 'Tarde']);
    $r1->answers()->create(['satisfaction_survey_question_id' => $qText->id, 'text_value' => 'Aulas excelentes e instrutores muito atenciosos!']);

    // Aluno 2: Nota 4 estrelas, NPS 8 (Neutro), Tarde
    $student2 = Person::create(['name' => 'Aluno Beta', 'email' => 'beta@example.com']);
    $resp2 = Responsible::create(['person_id' => $student2->id, 'relationship' => RelationshipEnum::Outro, 'financial_responsible' => true]);
    $contract2 = Contract::create(['contract_number' => 'MATR-S2', 'student_id' => $student2->id, 'responsible_id' => $resp2->id, 'status' => 'active', 'start_date' => today(), 'total_value' => 1000]);

    $r2 = $survey->responses()->create(['contract_id' => $contract2->id, 'student_id' => $student2->id]);
    $r2->answers()->create(['satisfaction_survey_question_id' => $qStars->id, 'rating_value' => 4]);
    $r2->answers()->create(['satisfaction_survey_question_id' => $qNps->id, 'rating_value' => 8]);
    $r2->answers()->create(['satisfaction_survey_question_id' => $qChoice->id, 'text_value' => 'Tarde']);

    // Aluno 3: Nota 3 estrelas, NPS 6 (Detrator), Manhã, Comentário com sugestão
    $student3 = Person::create(['name' => 'Aluno Gama', 'email' => 'gama@example.com']);
    $resp3 = Responsible::create(['person_id' => $student3->id, 'relationship' => RelationshipEnum::Outro, 'financial_responsible' => true]);
    $contract3 = Contract::create(['contract_number' => 'MATR-S3', 'student_id' => $student3->id, 'responsible_id' => $resp3->id, 'status' => 'active', 'start_date' => today(), 'total_value' => 1000]);

    $r3 = $survey->responses()->create(['contract_id' => $contract3->id, 'student_id' => $student3->id]);
    $r3->answers()->create(['satisfaction_survey_question_id' => $qStars->id, 'rating_value' => 3]);
    $r3->answers()->create(['satisfaction_survey_question_id' => $qNps->id, 'rating_value' => 6]);
    $r3->answers()->create(['satisfaction_survey_question_id' => $qChoice->id, 'text_value' => 'Manhã']);
    $r3->answers()->create(['satisfaction_survey_question_id' => $qText->id, 'text_value' => 'Poderia ter mais computadores novos na sala 2.']);

    $service = app(SatisfactionSurveyReportService::class);
    $metrics = $service->getMetrics($survey->id);

    expect($metrics['total_responses'])->toBe(3);
    expect($metrics['unique_students'])->toBe(3);

    // Média de estrelas: (5 + 4 + 3) / 3 = 4.0
    expect($metrics['average_rating_5'])->toBe(4.0);

    // Média percentual: (100 + 80 + 60 + 100 + 80 + 60) / 6 = 80.0%
    expect($metrics['average_percentage'])->toBe(80.0);

    // NPS: 1 Promotor (10), 1 Neutro (8), 1 Detrator (6) -> Score = (1 - 1)/3 = 0
    expect($metrics['nps_score'])->toBe(0);
    expect($metrics['promoters_count'])->toBe(1);
    expect($metrics['neutrals_count'])->toBe(1);
    expect($metrics['detractors_count'])->toBe(1);

    // Perguntas
    $starData = collect($metrics['questions'])->firstWhere('id', $qStars->id);
    expect($starData['average'])->toBe(4.0);
    expect($starData['stars_distribution'][5])->toBe(1);
    expect($starData['stars_distribution'][4])->toBe(1);
    expect($starData['stars_distribution'][3])->toBe(1);

    $choiceData = collect($metrics['questions'])->firstWhere('id', $qChoice->id);
    expect($choiceData['options_distribution']['Tarde']['count'])->toBe(2);
    expect($choiceData['options_distribution']['Manhã']['count'])->toBe(1);

    expect($metrics['recent_comments'])->toHaveCount(2);
    expect($metrics['recent_comments'][0]['text'])->toContain('Aulas excelentes');
});

test('satisfaction survey report page renders stats cards, charts and handles export', function () {
    $person = Person::create(['name' => 'Diretoria Pedagógica', 'email' => 'diretoria@example.com']);
    $user = User::create(['person_id' => $person->id, 'email' => 'diretoria@example.com', 'password' => bcrypt('password')]);
    $role = Role::findOrCreate('super_admin', 'web');
    $user->assignRole($role);
    $this->actingAs($user);

    $survey = SatisfactionSurvey::create([
        'title' => 'Pesquisa de Satisfação Geral',
        'is_active' => true,
    ]);

    $q = $survey->questions()->create([
        'question' => 'Como você avalia a escola no geral?',
        'type' => 'rating_5',
        'sort_order' => 1,
    ]);

    $student = Person::create(['name' => 'Aluno Teste Page', 'email' => 'teste.page@example.com']);
    $resp = Responsible::create(['person_id' => $student->id, 'relationship' => RelationshipEnum::Outro, 'financial_responsible' => true]);
    $contract = Contract::create(['contract_number' => 'MATR-PG-1', 'student_id' => $student->id, 'responsible_id' => $resp->id, 'status' => 'active', 'start_date' => today(), 'total_value' => 1000]);

    $response = $survey->responses()->create(['contract_id' => $contract->id, 'student_id' => $student->id]);
    $response->answers()->create(['satisfaction_survey_question_id' => $q->id, 'rating_value' => 5]);

    Livewire::test(SatisfactionSurveyReport::class)
        ->assertSuccessful()
        ->assertSee('Resultados de Pesquisas de Satisfação')
        ->assertSee('Média de Satisfação')
        ->assertSee('Satisfação Positiva')
        ->assertSee('Amostragem Coletada')
        ->assertSee('Como você avalia a escola no geral?')
        ->assertSee('5,00')
        ->call('exportCsv')
        ->assertFileDownloaded('relatorio-pesquisa-satisfacao-' . now()->format('Y-m-d') . '.csv');
});

test('satisfaction survey model provides average rating accessor for table badge', function () {
    $survey = SatisfactionSurvey::create(['title' => 'Pesquisa com Média', 'is_active' => true]);
    $q = $survey->questions()->create(['question' => 'Nota?', 'type' => 'rating_5', 'sort_order' => 1]);

    $s = Person::create(['name' => 'Aluno Badge', 'email' => 'badge@example.com']);
    $r = Responsible::create(['person_id' => $s->id, 'relationship' => RelationshipEnum::Outro, 'financial_responsible' => true]);
    $c = Contract::create(['contract_number' => 'MATR-BDG', 'student_id' => $s->id, 'responsible_id' => $r->id, 'status' => 'active', 'start_date' => today(), 'total_value' => 1000]);

    $resp = $survey->responses()->create(['contract_id' => $c->id, 'student_id' => $s->id]);
    $resp->answers()->create(['satisfaction_survey_question_id' => $q->id, 'rating_value' => 5]);

    expect($survey->average_rating)->toBe(5.0);
});

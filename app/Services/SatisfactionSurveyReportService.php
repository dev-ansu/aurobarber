<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SatisfactionSurvey;
use App\Models\SatisfactionSurveyAnswer;
use App\Models\SatisfactionSurveyQuestion;
use App\Models\SatisfactionSurveyResponse;
use Filament\Facades\Filament;
use Illuminate\Support\Carbon;

class SatisfactionSurveyReportService
{
    /**
     * Retorna os dados analíticos consolidados de uma pesquisa específica ou de todas as pesquisas.
     *
     * @return array{
     *     total_responses: int,
     *     unique_students: int,
     *     average_rating_5: float|null,
     *     average_percentage: float|null,
     *     positive_percentage: float|null,
     *     nps_score: int|null,
     *     promoters_count: int,
     *     neutrals_count: int,
     *     detractors_count: int,
     *     promoters_pct: float|null,
     *     neutrals_pct: float|null,
     *     detractors_pct: float|null,
     *     questions: array<int, array<string, mixed>>,
     *     recent_comments: array<int, array<string, mixed>>,
     *     survey_options: array<int, string>,
     * }
     */
    public function getMetrics(
        ?int $surveyId = null,
        ?string $fromDate = null,
        ?string $untilDate = null,
        ?int $schoolId = null,
    ): array {
        $schoolId ??= Filament::getTenant()?->id;
        $surveys = SatisfactionSurvey::query()->orderBy('title')->get();
        $surveyOptions = $surveys->pluck('title', 'id')->all();

        // 1. Filtragem de respostas base (escopada pela unidade escolar ativa)
        $responsesQuery = SatisfactionSurveyResponse::query()
            ->with(['student', 'contract'])
            ->when($schoolId, function ($query) use ($schoolId): void {
                $query->where(function ($sub) use ($schoolId): void {
                    $sub->whereHas('contract', fn ($cq) => $cq->withoutGlobalScopes()->where('school_id', $schoolId))
                        ->orWhere(function ($fallback) use ($schoolId): void {
                            $fallback->whereNull('contract_id')
                                ->whereHas('student.schools', fn ($sq) => $sq->where('schools.id', $schoolId));
                        });
                });
            });

        if ($surveyId) {
            $responsesQuery->where('satisfaction_survey_id', $surveyId);
        }

        if ($fromDate) {
            $responsesQuery->whereDate('created_at', '>=', Carbon::parse($fromDate)->startOfDay());
        }

        if ($untilDate) {
            $responsesQuery->whereDate('created_at', '<=', Carbon::parse($untilDate)->endOfDay());
        }

        $responses = $responsesQuery->get();
        $responseIds = $responses->pluck('id')->all();

        $totalResponses = $responses->count();
        $uniqueStudents = $responses->pluck('student_id')->unique()->filter()->count();

        if ($totalResponses === 0 || empty($responseIds)) {
            return [
                'total_responses' => 0,
                'unique_students' => 0,
                'average_rating_5' => null,
                'average_percentage' => null,
                'positive_percentage' => null,
                'nps_score' => null,
                'promoters_count' => 0,
                'neutrals_count' => 0,
                'detractors_count' => 0,
                'promoters_pct' => null,
                'neutrals_pct' => null,
                'detractors_pct' => null,
                'questions' => [],
                'recent_comments' => [],
                'survey_options' => $surveyOptions,
            ];
        }

        // 2. Busca todas as respostas das questões no período
        $answers = SatisfactionSurveyAnswer::query()
            ->whereIn('satisfaction_survey_response_id', $responseIds)
            ->with(['question', 'response.student', 'response.contract'])
            ->get();

        // 3. Questões a exibir
        $questionsQuery = SatisfactionSurveyQuestion::query()
            ->orderBy('sort_order')
            ->orderBy('id');

        if ($surveyId) {
            $questionsQuery->where('satisfaction_survey_id', $surveyId);
        } else {
            $surveyIdsInResponses = $responses->pluck('satisfaction_survey_id')->unique()->all();
            $questionsQuery->whereIn('satisfaction_survey_id', $surveyIdsInResponses);
        }

        $questions = $questionsQuery->get();

        // 4. Métricas consolidadas gerais
        $rating5Answers = $answers->filter(fn (SatisfactionSurveyAnswer $a) => $a->question?->type === 'rating_5' && $a->rating_value !== null);
        $npsAnswers = $answers->filter(fn (SatisfactionSurveyAnswer $a) => $a->question?->type === 'nps_10' && $a->rating_value !== null);

        $avgRating5 = $rating5Answers->isNotEmpty()
            ? round((float) $rating5Answers->avg('rating_value'), 2)
            : null;

        // Média percentual unificada (0 a 100%)
        $normalizedScores = [];
        foreach ($rating5Answers as $a) {
            $normalizedScores[] = $a->rating_value * 20; // 5 vira 100%
        }
        foreach ($npsAnswers as $a) {
            $normalizedScores[] = $a->rating_value * 10; // 10 vira 100%
        }

        $avgPercentage = ! empty($normalizedScores)
            ? round(array_sum($normalizedScores) / count($normalizedScores), 1)
            : ($avgRating5 !== null ? round($avgRating5 * 20, 1) : null);

        // Taxa positiva (% de notas 4 ou 5 estrelas e NPS >= 8)
        $positiveCount = 0;
        $totalEvaluated = count($rating5Answers) + count($npsAnswers);

        foreach ($rating5Answers as $a) {
            if ($a->rating_value >= 4) {
                $positiveCount++;
            }
        }
        foreach ($npsAnswers as $a) {
            if ($a->rating_value >= 8) {
                $positiveCount++;
            }
        }

        $positivePercentage = $totalEvaluated > 0
            ? round(($positiveCount / $totalEvaluated) * 100, 1)
            : null;

        // NPS Geral Consolidado
        $totalNps = count($npsAnswers);
        $promotersCount = 0;
        $neutralsCount = 0;
        $detractorsCount = 0;

        foreach ($npsAnswers as $a) {
            if ($a->rating_value >= 9) {
                $promotersCount++;
            } elseif ($a->rating_value >= 7) {
                $neutralsCount++;
            } else {
                $detractorsCount++;
            }
        }

        $npsScore = $totalNps > 0
            ? (int) round((($promotersCount - $detractorsCount) / $totalNps) * 100)
            : null;

        $promotersPct = $totalNps > 0 ? round(($promotersCount / $totalNps) * 100, 1) : null;
        $neutralsPct = $totalNps > 0 ? round(($neutralsCount / $totalNps) * 100, 1) : null;
        $detractorsPct = $totalNps > 0 ? round(($detractorsCount / $totalNps) * 100, 1) : null;

        // 5. Métricas detalhadas por pergunta
        $questionsData = [];
        $allRecentComments = [];

        foreach ($questions as $q) {
            $qAnswers = $answers->where('satisfaction_survey_question_id', $q->id);
            $totalQAnswers = $qAnswers->count();

            $qData = [
                'id' => $q->id,
                'question' => $q->question,
                'type' => $q->type,
                'total_answers' => $totalQAnswers,
                'average' => null,
                'stars_distribution' => [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0],
                'stars_percentages' => [5 => 0.0, 4 => 0.0, 3 => 0.0, 2 => 0.0, 1 => 0.0],
                'nps_score' => null,
                'nps_distribution' => array_fill(0, 11, 0),
                'nps_promoters' => 0,
                'nps_neutrals' => 0,
                'nps_detractors' => 0,
                'options_distribution' => [],
                'recent_comments' => [],
            ];

            if ($q->type === 'rating_5') {
                $rated = $qAnswers->whereNotNull('rating_value');
                if ($rated->isNotEmpty()) {
                    $qData['average'] = round((float) $rated->avg('rating_value'), 2);
                    $dist = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
                    foreach ($rated as $ans) {
                        $star = (int) $ans->rating_value;
                        if (isset($dist[$star])) {
                            $dist[$star]++;
                        }
                    }
                    $qData['stars_distribution'] = $dist;
                    $totalRated = $rated->count();
                    foreach ($dist as $star => $c) {
                        $qData['stars_percentages'][$star] = round(($c / $totalRated) * 100, 1);
                    }
                }
            } elseif ($q->type === 'nps_10') {
                $rated = $qAnswers->whereNotNull('rating_value');
                if ($rated->isNotEmpty()) {
                    $qData['average'] = round((float) $rated->avg('rating_value'), 1);
                    $npsDist = array_fill(0, 11, 0);
                    $p = 0;
                    $n = 0;
                    $d = 0;

                    foreach ($rated as $ans) {
                        $val = (int) $ans->rating_value;
                        if (isset($npsDist[$val])) {
                            $npsDist[$val]++;
                        }
                        if ($val >= 9) {
                            $p++;
                        } elseif ($val >= 7) {
                            $n++;
                        } else {
                            $d++;
                        }
                    }

                    $qData['nps_distribution'] = $npsDist;
                    $qData['nps_promoters'] = $p;
                    $qData['nps_neutrals'] = $n;
                    $qData['nps_detractors'] = $d;
                    $qData['nps_score'] = (int) round((($p - $d) / $rated->count()) * 100);
                }
            } elseif ($q->type === 'multiple_choice') {
                $options = is_array($q->options) ? $q->options : [];
                $optionsCounts = [];
                foreach ($options as $opt) {
                    $optionsCounts[$opt] = 0;
                }

                foreach ($qAnswers as $ans) {
                    $choice = trim((string) $ans->text_value);
                    if ($choice !== '') {
                        $optionsCounts[$choice] = ($optionsCounts[$choice] ?? 0) + 1;
                    }
                }

                $optionsTotal = array_sum($optionsCounts);
                $optionsDistribution = [];
                foreach ($optionsCounts as $optName => $c) {
                    $optionsDistribution[$optName] = [
                        'count' => $c,
                        'percentage' => $optionsTotal > 0 ? round(($c / $optionsTotal) * 100, 1) : 0.0,
                    ];
                }
                $qData['options_distribution'] = $optionsDistribution;
            } elseif ($q->type === 'text') {
                $comments = [];
                foreach ($qAnswers->filter(fn ($a) => filled($a->text_value)) as $ans) {
                    $item = [
                        'student_name' => $ans->response?->student?->name ?? 'Aluno',
                        'contract_number' => $ans->response?->contract?->contract_number ?? '',
                        'text' => (string) $ans->text_value,
                        'date' => $ans->created_at?->format('d/m/Y H:i') ?? '',
                    ];
                    $comments[] = $item;
                    $allRecentComments[] = $item;
                }
                $qData['recent_comments'] = array_slice($comments, 0, 10);
            }

            $questionsData[] = $qData;
        }

        return [
            'total_responses' => $totalResponses,
            'unique_students' => $uniqueStudents,
            'average_rating_5' => $avgRating5,
            'average_percentage' => $avgPercentage,
            'positive_percentage' => $positivePercentage,
            'nps_score' => $npsScore,
            'promoters_count' => $promotersCount,
            'neutrals_count' => $neutralsCount,
            'detractors_count' => $detractorsCount,
            'promoters_pct' => $promotersPct,
            'neutrals_pct' => $neutralsPct,
            'detractors_pct' => $detractorsPct,
            'questions' => $questionsData,
            'recent_comments' => array_slice($allRecentComments, 0, 15),
            'survey_options' => $surveyOptions,
        ];
    }
}

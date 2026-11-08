<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\SatisfactionSurvey;
use App\Models\User;
use App\Services\SatisfactionSurveyReportService;
use Filament\Pages\Page;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class SatisfactionSurveyReport extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Relatórios';

    protected static ?string $navigationParentItem = 'Comercial';

    protected static ?string $navigationLabel = 'Pesquisas de satisfação';

    protected static ?string $title = 'Resultados de Pesquisas de Satisfação';

    protected static ?string $slug = 'satisfaction-survey-report';

    protected static ?int $navigationSort = 8;

    protected string $view = 'filament.pages.satisfaction-survey-report';

    public ?int $surveyId = null;

    public ?string $fromDate = null;

    public ?string $untilDate = null;

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            return false;
        }

        return $user->hasRole('super_admin')
            || $user->can('View:SatisfactionSurveyReport')
            || $user->can('page_SatisfactionSurveyReport')
            || $user->can('ViewAny:SatisfactionSurvey');
    }

    public function mount(): void
    {
        $querySurveyId = request()->query('survey_id') ?? request()->query('surveyId');
        if ($querySurveyId) {
            $this->surveyId = (int) $querySurveyId;
        }

        $this->fromDate = request()->query('from_date');
        $this->untilDate = request()->query('until_date');
    }

    public function filterSurvey(?int $id): void
    {
        $this->surveyId = $id;
    }

    public function clearFilters(): void
    {
        $this->surveyId = null;
        $this->fromDate = null;
        $this->untilDate = null;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetricsProperty(): array
    {
        return app(SatisfactionSurveyReportService::class)->getMetrics(
            $this->surveyId,
            $this->fromDate,
            $this->untilDate,
        );
    }

    public function exportCsv(): StreamedResponse
    {
        $metrics = $this->metrics;
        $surveyTitle = $this->surveyId
            ? (SatisfactionSurvey::find($this->surveyId)?->title ?? 'Pesquisa')
            : 'Todas as Pesquisas';

        $filename = 'relatorio-pesquisa-satisfacao-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($metrics, $surveyTitle) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            fputcsv($handle, ['Relatório de Pesquisas de Satisfação — Aurosoft Educacional'], ';');
            fputcsv($handle, ['Escopo:', $surveyTitle], ';');
            fputcsv($handle, ['Data de Emissão:', now()->format('d/m/Y H:i')], ';');
            fputcsv($handle, [], ';');

            fputcsv($handle, ['INDICADORES GERAIS'], ';');
            fputcsv($handle, ['Total de Respostas Coletadas', $metrics['total_responses']], ';');
            fputcsv($handle, ['Alunos Únicos Participantes', $metrics['unique_students']], ';');
            fputcsv($handle, ['Média Geral de Estrelas (1 a 5)', $metrics['average_rating_5'] ?? 'N/A'], ';');
            fputcsv($handle, ['Média Geral Consolidada (%)', ($metrics['average_percentage'] ?? 'N/A') . '%'], ';');
            fputcsv($handle, ['Taxa de Satisfação Positiva (%)', ($metrics['positive_percentage'] ?? 'N/A') . '%'], ';');
            fputcsv($handle, ['NPS Score Consolidado', $metrics['nps_score'] ?? 'N/A'], ';');
            fputcsv($handle, [], ';');

            fputcsv($handle, ['DETALHAMENTO POR PERGUNTA'], ';');
            fputcsv($handle, ['ID', 'Pergunta', 'Tipo', 'Respostas', 'Média/Score'], ';');

            foreach ($metrics['questions'] as $q) {
                fputcsv($handle, [
                    $q['id'],
                    $q['question'],
                    $q['type'],
                    $q['total_answers'],
                    $q['average'] ?? ($q['nps_score'] !== null ? "NPS {$q['nps_score']}" : '—'),
                ], ';');
            }

            fputcsv($handle, [], ';');
            fputcsv($handle, ['COMENTÁRIOS E FEEDBACKS QUALITATIVOS'], ';');
            fputcsv($handle, ['Data', 'Aluno', 'Contrato', 'Mensagem / Sugestão'], ';');

            foreach ($metrics['recent_comments'] as $comm) {
                fputcsv($handle, [
                    $comm['date'],
                    $comm['student_name'],
                    $comm['contract_number'],
                    $comm['text'],
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}

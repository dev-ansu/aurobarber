<?php

namespace App\Filament\Pages;

use App\Services\CommercialPerformanceReport as CommercialPerformanceReportService;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class CommercialReport extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Relatórios';

    protected static ?string $navigationParentItem = 'Comercial';

    protected static ?string $navigationLabel = 'Desempenho comercial';

    protected static ?string $title = 'Desempenho comercial';

    protected static ?string $slug = 'commercial-report';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.commercial-report';

    public ?string $fromDate = null;

    public ?string $untilDate = null;

    public string $marketingInvestment = '';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('View:CommercialReport') ?? false;
    }

    public function mount(): void
    {
        $this->fromDate = now()->startOfMonth()->toDateString();
        $this->untilDate = today()->toDateString();
    }

    public function parseInvestment(): float
    {
        if (trim($this->marketingInvestment) === '') {
            return 0.0;
        }

        $clean = str_replace(['R$', ' ', '.'], '', $this->marketingInvestment);
        $clean = str_replace(',', '.', $clean);

        return max(0.0, (float) $clean);
    }

    public function reportData(): array
    {
        $validator = Validator::make([
            'from' => $this->fromDate,
            'until' => $this->untilDate,
        ], [
            'from' => ['required', 'date'],
            'until' => ['required', 'date', 'after_or_equal:from'],
        ]);

        if ($validator->fails()) {
            return self::emptyReport();
        }

        $data = $validator->validated();

        return app(CommercialPerformanceReportService::class)->generate(
            Carbon::parse($data['from']),
            Carbon::parse($data['until']),
            $this->parseInvestment(),
        );
    }

    public function exportReport(): StreamedResponse
    {
        abort_unless(auth()->user()?->can('Export:CommercialReport'), 403);

        Validator::make([
            'from' => $this->fromDate,
            'until' => $this->untilDate,
        ], [
            'from' => ['required', 'date'],
            'until' => ['required', 'date', 'after_or_equal:from'],
        ])->validate();

        $report = $this->reportData();
        $filename = 'desempenho-comercial-'.$this->fromDate.'-'.$this->untilDate.'.csv';

        return response()->streamDownload(function () use ($report): void {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, ['Desempenho comercial & Métricas de CAC', $this->fromDate, $this->untilDate], ';', '"', '');
            fputcsv($stream, [], ';', '"', '');
            fputcsv($stream, ['Indicador', 'Resultado'], ';', '"', '');
            fputcsv($stream, ['Interessados cadastrados', $report['lead_count']], ';', '"', '');
            fputcsv($stream, ['Matrículas (Contratos assinados)', $report['enrolled_count']], ';', '"', '');
            fputcsv($stream, ['Conversão (%)', $report['conversion_rate']], ';', '"', '');
            fputcsv($stream, ['Investimento comercial (R$)', number_format($report['investment'], 2, ',', '.')], ';', '"', '');
            fputcsv($stream, ['CAC - Custo de Aquisição por Aluno (R$)', number_format($report['cac'], 2, ',', '.')], ';', '"', '');
            fputcsv($stream, ['Visitas registradas', $report['visits_count']], ';', '"', '');
            fputcsv($stream, ['Retornos atrasados', $report['overdue_follow_ups']], ';', '"', '');
            fputcsv($stream, [], ';', '"', '');
            fputcsv($stream, ['Funil de Conversão', 'Quantidade', 'Conversão etapa (%)', 'Conversão geral (%)'], ';', '"', '');
            foreach ($report['funnel'] as $stage) {
                fputcsv($stream, [$stage['stage'], $stage['count'], number_format($stage['rate'], 1, ',', ''), number_format($stage['conversion_from_total'], 1, ',', '')], ';', '"', '');
            }
            fputcsv($stream, [], ';', '"', '');
            fputcsv($stream, ['Motivos mais frequentes de objeção / perda', 'Ocorrências', 'Participação (%)'], ';', '"', '');
            foreach ($report['loss_reasons'] as $loss) {
                fputcsv($stream, [$loss['reason'], $loss['count'], number_format($loss['percentage'], 1, ',', '')], ';', '"', '');
            }
            fputcsv($stream, [], ';', '"', '');
            fputcsv($stream, ['Por origem', 'Interessados', 'Matrículas', 'Conversão (%)'], ';', '"', '');

            foreach ($report['by_source'] as $row) {
                fputcsv($stream, [$row['label'], $row['leads'], $row['enrolled'], $row['conversion_rate']], ';', '"', '');
            }

            fputcsv($stream, [], ';', '"', '');
            fputcsv($stream, ['Por responsável', 'Interessados', 'Matrículas', 'Conversão (%)'], ';', '"', '');

            foreach ($report['by_collaborator'] as $row) {
                fputcsv($stream, [$row['label'], $row['leads'], $row['enrolled'], $row['conversion_rate']], ';', '"', '');
            }

            foreach ([
                'Por estado' => $report['by_state'],
                'Por cidade' => $report['by_city'],
                'Por bairro' => $report['by_neighborhood'],
            ] as $title => $rows) {
                fputcsv($stream, [], ';', '"', '');
                fputcsv($stream, [$title, 'Interessados', 'Matrículas', 'Conversão (%)'], ';', '"', '');

                foreach ($rows as $row) {
                    fputcsv($stream, [$row['label'], $row['leads'], $row['enrolled'], $row['conversion_rate']], ';', '"', '');
                }
            }

            fclose($stream);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private static function emptyReport(): array
    {
        return [
            'lead_count' => 0,
            'enrolled_count' => 0,
            'conversion_rate' => 0,
            'visits_count' => 0,
            'overdue_follow_ups' => 0,
            'investment' => 0.0,
            'cac' => 0.0,
            'funnel' => [],
            'loss_reasons' => [],
            'by_source' => [],
            'by_collaborator' => [],
            'by_state' => [],
            'by_city' => [],
            'by_neighborhood' => [],
            'aging' => [
                ['label' => 'Até 7 dias', 'count' => 0],
                ['label' => '8 a 30 dias', 'count' => 0],
                ['label' => 'Mais de 30 dias', 'count' => 0],
            ],
        ];
    }
}

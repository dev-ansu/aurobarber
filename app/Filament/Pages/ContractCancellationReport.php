<?php

namespace App\Filament\Pages;

use App\Models\Formation;
use App\Services\ContractCancellationReport as ContractCancellationReportService;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class ContractCancellationReport extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Relatórios';

    protected static ?string $navigationParentItem = 'Comercial';

    protected static ?string $navigationLabel = 'Cancelamentos';

    protected static ?string $title = 'Relatório de cancelamentos';

    protected static ?string $slug = 'contract-cancellation-report';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.contract-cancellation-report';

    public ?string $fromDate = null;

    public ?string $untilDate = null;

    public ?string $formationId = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('View:ContractCancellationReport') ?? false;
    }

    public function mount(): void
    {
        $this->fromDate = now()->startOfMonth()->toDateString();
        $this->untilDate = today()->toDateString();
    }

    public function formations(): array
    {
        return Formation::query()->orderBy('name')->pluck('name', 'id')->all();
    }

    public function reportData(): array
    {
        $validated = $this->validateFilters();

        if (! $validated) {
            return self::emptyReport();
        }

        return app(ContractCancellationReportService::class)->generate(
            Carbon::parse($validated['from']),
            Carbon::parse($validated['until']),
            filled($validated['formation_id'] ?? null) ? (int) $validated['formation_id'] : null,
        );
    }

    public function exportReport(): StreamedResponse
    {
        abort_unless(auth()->user()?->can('Export:ContractCancellationReport'), 403);
        $this->validateFilters(true);
        $report = $this->reportData();
        $filename = 'cancelamentos-'.$this->fromDate.'-'.$this->untilDate.'.csv';

        return response()->streamDownload(function () use ($report): void {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, ['Data', 'Aluno', 'Contrato', 'Formação', 'Motivo', 'Dias de vínculo'], ';', '"', '');

            foreach ($report['rows'] as $row) {
                fputcsv($stream, [$row['date'], $row['student'], $row['contract_number'], $row['formations'], $row['reason'], $row['duration_days'] ?? ''], ';', '"', '');
            }

            fclose($stream);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function validateFilters(bool $throw = false): ?array
    {
        $validator = Validator::make([
            'from' => $this->fromDate,
            'until' => $this->untilDate,
            'formation_id' => $this->formationId,
        ], [
            'from' => ['required', 'date'],
            'until' => ['required', 'date', 'after_or_equal:from', 'before_or_equal:today'],
            'formation_id' => ['nullable', 'integer', 'exists:formations,id'],
        ]);

        if ($validator->fails()) {
            if ($throw) {
                $validator->validate();
            }

            return null;
        }

        return $validator->validated();
    }

    private static function emptyReport(): array
    {
        return [
            'cancellation_events' => 0,
            'contracts' => 0,
            'students' => 0,
            'without_reason' => 0,
            'average_duration_days' => null,
            'by_reason' => [],
            'by_month' => [],
            'rows' => [],
        ];
    }
}

<?php

namespace App\Filament\Pages;

use App\Models\Contract;
use App\Models\Formation;
use App\Models\User;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class FlexAndFreeStudentsReport extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Relatórios';

    protected static ?string $navigationParentItem = 'Pedagógico';

    protected static ?string $navigationLabel = 'Alunos Flex e Gratuitos';

    protected static ?string $title = 'Relatório de Alunos Flex e Cursos Gratuitos';

    protected static ?string $slug = 'flex-and-free-students-report';

    protected static ?int $navigationSort = 6;

    protected string $view = 'filament.pages.flex-and-free-students-report';

    public string $filterType = 'all'; // 'all', 'flex', 'free', 'both'

    public string $contractStatus = 'active'; // 'all', 'active', 'draft', 'finished', 'canceled'

    public string $formationId = '';

    public string $search = '';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && ($user->hasRole('super_admin')
                || $user->can('View:FlexAndFreeStudentsReport')
                || $user->can('ViewAny:Contract'));
    }

    public function formations(): array
    {
        return Formation::query()->orderBy('name')->pluck('name', 'id')->all();
    }

    public function contractStatuses(): array
    {
        return [
            'active' => 'Ativo',
            'all' => 'Todos os status',
            'draft' => 'Rascunho',
            'finished' => 'Finalizado',
            'canceled' => 'Cancelado',
            'suspended' => 'Suspenso',
        ];
    }

    public function filterTypes(): array
    {
        return [
            'all' => 'Todos (Flex e Gratuitos)',
            'flex' => 'Apenas Alunos Flex',
            'free' => 'Apenas Cursos Gratuitos / Bolsistas',
            'both' => 'Alunos Flex E Gratuitos simultaneamente',
        ];
    }

    public function clearFilters(): void
    {
        $this->filterType = 'all';
        $this->contractStatus = 'active';
        $this->formationId = '';
        $this->search = '';
    }

    public function metrics(): array
    {
        $base = Contract::query();

        $activeBase = (clone $base)->where('status', 'active');

        $activeFlex = (clone $activeBase)->where('is_flex', true)->count();

        $activeFree = (clone $activeBase)->where(function (Builder $q) {
            $q->where('is_free', true)
                ->orWhere(function (Builder $sq) {
                    $sq->where('total_value', '<=', 0)->doesntHave('installments');
                });
        })->count();

        $activeBoth = (clone $activeBase)->where('is_flex', true)->where(function (Builder $q) {
            $q->where('is_free', true)
                ->orWhere(function (Builder $sq) {
                    $sq->where('total_value', '<=', 0)->doesntHave('installments');
                });
        })->count();

        $totalFiltered = $this->query()->count();

        return [
            'active_flex' => $activeFlex,
            'active_free' => $activeFree,
            'active_both' => $activeBoth,
            'total_filtered' => $totalFiltered,
        ];
    }

    public function query(): Builder
    {
        $query = Contract::query()
            ->with(['student', 'items.formation', 'responsible.person'])
            ->where(function (Builder $q) {
                // Seleciona ou Flex ou Gratuito
                $q->where('is_flex', true)
                    ->orWhere('is_free', true)
                    ->orWhere(function (Builder $sq) {
                        $sq->where('total_value', '<=', 0)->doesntHave('installments');
                    });
            });

        // Filtro específico do tipo
        if ($this->filterType === 'flex') {
            $query->where('is_flex', true);
        } elseif ($this->filterType === 'free') {
            $query->where(function (Builder $q) {
                $q->where('is_free', true)
                    ->orWhere(function (Builder $sq) {
                        $sq->where('total_value', '<=', 0)->doesntHave('installments');
                    });
            });
        } elseif ($this->filterType === 'both') {
            $query->where('is_flex', true)
                ->where(function (Builder $q) {
                    $q->where('is_free', true)
                        ->orWhere(function (Builder $sq) {
                            $sq->where('total_value', '<=', 0)->doesntHave('installments');
                        });
                });
        }

        // Filtro de status
        if ($this->contractStatus !== 'all' && filled($this->contractStatus)) {
            $query->where('status', $this->contractStatus);
        }

        // Filtro por Formação
        if (filled($this->formationId)) {
            $query->whereHas('items', function (Builder $q) {
                $q->where('formation_id', (int) $this->formationId);
            });
        }

        // Filtro por busca
        if (filled($this->search)) {
            $term = trim($this->search);
            $cleanDigits = preg_replace('/\D/', '', $term);

            $query->where(function (Builder $q) use ($term, $cleanDigits) {
                $q->where('contract_number', 'like', "%{$term}%")
                    ->orWhereHas('student', function (Builder $sq) use ($term, $cleanDigits) {
                        $sq->where('name', 'like', "%{$term}%")
                            ->orWhere('email', 'like', "%{$term}%");

                        if (! empty($cleanDigits)) {
                            $sq->orWhereCpf($cleanDigits);
                        }
                    });
            });
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function records(int $limit = 100): Collection
    {
        return $this->query()->limit($limit)->get();
    }

    public function exportCsv(): StreamedResponse
    {
        $contracts = $this->query()->get();

        $filename = 'relatorio-alunos-flex-e-gratuitos-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($contracts) {
            $handle = fopen('php://output', 'w');

            // BOM para UTF-8 no Excel
            fputs($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Nº Contrato',
                'Nome do Aluno',
                'CPF do Aluno',
                'E-mail',
                'Data de Nascimento',
                'Status do Contrato',
                'É Aluno Flex?',
                'É Curso Gratuito?',
                'Valor Total (R$)',
                'Formações / Cursos',
                'Responsável Financeiro',
                'Data Início',
                'Data Término Prevista',
            ], ';');

            foreach ($contracts as $contract) {
                $student = $contract->student;
                $formations = $contract->items->map(fn ($item) => $item->formation?->name)->filter()->implode(', ');
                $isFree = $contract->isFree();

                fputcsv($handle, [
                    $contract->contract_number,
                    $student?->name ?? '—',
                    $student?->cpf ?? '—',
                    $student?->email ?? '—',
                    $student?->birth_date ? $student->birth_date->format('d/m/Y') : '—',
                    $contract->status,
                    $contract->is_flex ? 'SIM' : 'NÃO',
                    $isFree ? 'SIM' : 'NÃO',
                    number_format((float) $contract->total_value, 2, ',', '.'),
                    $formations ?: '—',
                    $contract->responsible?->person?->name ?? ($isFree ? 'Isento (Curso Gratuito)' : 'Não informado'),
                    $contract->start_date ? $contract->start_date->format('d/m/Y') : '—',
                    $contract->end_date ? $contract->end_date->format('d/m/Y') : '—',
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}


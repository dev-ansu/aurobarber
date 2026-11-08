<?php

namespace App\Filament\Pages;

use App\Enums\ContactTypeEnum;
use App\Models\Address;
use App\Models\Classroom;
use App\Models\Contact;
use App\Models\Contract;
use App\Models\Course;
use App\Models\Formation;
use App\Models\StudentSchedule;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class StudentContactsReport extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Relatórios';

    protected static ?string $navigationParentItem = 'Pedagógico';

    protected static ?string $navigationLabel = 'Contatos dos alunos';

    protected static ?string $title = 'Relatório de contatos dos alunos';

    protected static ?string $slug = 'student-contacts-report';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.student-contacts-report';

    public string $contractStatus = 'active';

    public string $weekday = '';

    public string $startHour = '';

    public string $classroomId = '';

    public string $formationId = '';

    public string $courseId = '';

    public string $neighborhood = '';

    public string $search = '';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && ($user->hasRole('super_admin') || $user->can('View:StudentContactsReport'));
    }

    public function classrooms(): array
    {
        return Classroom::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public function formations(): array
    {
        return Formation::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public function courses(): array
    {
        return Course::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public function neighborhoods(): array
    {
        $tenantId = Filament::getTenant()?->id;

        return Address::query()
            ->whereNotNull('neighborhood')
            ->where('neighborhood', '!=', '')
            ->when($tenantId, fn (Builder $q) => $q->whereHas('person.contracts', fn (Builder $cq) => $cq->where('school_id', $tenantId)))
            ->distinct()
            ->orderBy('neighborhood')
            ->pluck('neighborhood', 'neighborhood')
            ->all();
    }

    public function startHours(): array
    {
        return StudentSchedule::query()
            ->whereNull('deleted_at')
            ->distinct()
            ->orderBy('start_hour')
            ->pluck('start_hour')
            ->mapWithKeys(fn (string $hour): array => [
                Carbon::parse($hour)->format('H:i') => Carbon::parse($hour)->format('H:i'),
            ])
            ->all();
    }

    public function weekdays(): array
    {
        return [
            '1' => 'Segunda-feira',
            '2' => 'Terça-feira',
            '3' => 'Quarta-feira',
            '4' => 'Quinta-feira',
            '5' => 'Sexta-feira',
            '6' => 'Sábado',
            '0' => 'Domingo',
        ];
    }

    public function contractStatuses(): array
    {
        return [
            'all' => 'Todas as situações',
            'active' => 'Ativo',
            'draft' => 'Rascunho',
            'suspended' => 'Suspenso',
            'canceled' => 'Cancelado',
            'finished' => 'Finalizado',
        ];
    }

    public function clearFilters(): void
    {
        $this->contractStatus = 'active';
        $this->weekday = '';
        $this->startHour = '';
        $this->classroomId = '';
        $this->formationId = '';
        $this->courseId = '';
        $this->neighborhood = '';
        $this->search = '';
    }

    public function getFilteredQuery(): Builder
    {
        $query = Contract::query()
            ->with([
                'student.contacts',
                'student.addresses',
                'responsible.person.contacts',
                'items.formation',
                'items.contractCourses.course',
                'items.schedules.classroom',
            ]);

        if (filled($this->contractStatus) && $this->contractStatus !== 'all') {
            $query->where('status', $this->contractStatus);
        }

        if (filled($this->formationId)) {
            $query->whereHas('items', fn (Builder $q): Builder => $q->where('formation_id', (int) $this->formationId));
        }

        if (filled($this->courseId)) {
            $query->whereHas('items.contractCourses', fn (Builder $q): Builder => $q->where('course_id', (int) $this->courseId));
        }

        if (filled($this->neighborhood)) {
            $query->whereHas('student.addresses', fn (Builder $q): Builder => $q->where('neighborhood', $this->neighborhood));
        }

        if (filled($this->weekday) || filled($this->startHour) || filled($this->classroomId)) {
            $query->whereHas('items.schedules', function (Builder $scheduleQuery): void {
                $scheduleQuery->whereNull('deleted_at');

                if (filled($this->weekday)) {
                    $scheduleQuery->where('weekday', (int) $this->weekday);
                }

                if (filled($this->startHour)) {
                    $scheduleQuery->where(
                        'start_hour',
                        Carbon::createFromFormat('H:i', $this->startHour)->format('H:i:s'),
                    );
                }

                if (filled($this->classroomId)) {
                    $scheduleQuery->where('classroom_id', (int) $this->classroomId);
                }
            });
        }

        if (filled($this->search)) {
            $term = trim($this->search);
            $query->where(function (Builder $searchQuery) use ($term): void {
                $searchQuery->whereLike('contract_number', "%{$term}%")
                    ->orWhereHas('student', function (Builder $studentQuery) use ($term): void {
                        $studentQuery->whereLike('name', "%{$term}%")
                            ->orWhereLike('cpf', "%{$term}%")
                            ->orWhereLike('email', "%{$term}%");
                    });
            });
        }

        return $query->latest('id');
    }

    /**
     * @return Collection<int, Contract>
     */
    public function records(int $limit = 50): Collection
    {
        return $this->getFilteredQuery()->limit($limit)->get();
    }

    public function totalRecordsCount(): int
    {
        return $this->getFilteredQuery()->count();
    }

    /**
     * @return array{total_contracts: int, total_students: int, with_whatsapp: int, with_phone: int, with_responsible: int}
     */
    public function metrics(): array
    {
        $all = $this->getFilteredQuery()->get();

        $uniqueStudents = $all->pluck('student')->filter()->unique('id');

        $withWhatsapp = 0;
        $withPhone = 0;

        foreach ($uniqueStudents as $student) {
            $types = $student->contacts->pluck('type')->all();
            if (in_array(ContactTypeEnum::WhatsApp, $types, true)) {
                $withWhatsapp++;
            }
            if (in_array(ContactTypeEnum::Celular, $types, true) || in_array(ContactTypeEnum::Telefone, $types, true)) {
                $withPhone++;
            }
        }

        $withResponsible = $all->filter(fn (Contract $c): bool => $c->responsible_id !== null)->count();

        return [
            'total_contracts' => $all->count(),
            'total_students' => $uniqueStudents->count(),
            'with_whatsapp' => $withWhatsapp,
            'with_phone' => $withPhone,
            'with_responsible' => $withResponsible,
        ];
    }

    public function exportReport(): StreamedResponse
    {
        $user = auth()->user();
        abort_unless(
            $user instanceof User && ($user->hasRole('super_admin') || $user->can('Export:StudentContactsReport')),
            403
        );

        $contracts = $this->getFilteredQuery()->get();
        $filename = 'contatos-alunos-'.today()->toDateString().'.csv';
        $weekdaysMap = $this->weekdays();

        return response()->streamDownload(function () use ($contracts, $weekdaysMap): void {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");

            fputcsv($stream, [
                'Aluno',
                'CPF Aluno',
                'Nascimento Aluno',
                'Bairro Aluno',
                'E-mail Aluno',
                'Telefone / Celular Aluno',
                'WhatsApp Aluno',
                'Outros Contatos Aluno',
                'Contrato Nº',
                'Situação Contrato',
                'Início Contrato',
                'Término Previsto',
                'Responsável Financeiro',
                'Parentesco Responsável',
                'Contatos Responsável',
                'Formações',
                'Cursos',
                'Dias de Aula',
                'Horários de Aula',
                'Salas de Aula',
            ], ';', '"', '');

            foreach ($contracts as $contract) {
                $student = $contract->student;
                $contacts = $student?->contacts ?? collect();

                $whatsappList = $contacts
                    ->where('type', ContactTypeEnum::WhatsApp)
                    ->pluck('value')
                    ->implode(' / ');

                $phoneList = $contacts
                    ->whereIn('type', [ContactTypeEnum::Celular, ContactTypeEnum::Telefone])
                    ->pluck('value')
                    ->implode(' / ');

                $emailList = $contacts
                    ->where('type', ContactTypeEnum::Email)
                    ->pluck('value');

                if ($emailList->isEmpty() && filled($student?->email)) {
                    $emailList = collect([$student->email]);
                }

                $otherList = $contacts
                    ->reject(fn (Contact $c): bool => in_array($c->type, [
                        ContactTypeEnum::WhatsApp,
                        ContactTypeEnum::Celular,
                        ContactTypeEnum::Telefone,
                        ContactTypeEnum::Email,
                    ], true))
                    ->map(fn (Contact $c): string => $c->type->getLabel().': '.$c->value)
                    ->implode(' | ');

                // Responsável
                $responsible = $contract->responsible;
                $respPerson = $responsible?->person;
                $respName = $respPerson?->name ?? '';
                $respRel = $responsible?->relationship?->value ?? '';
                $respContacts = $respPerson?->contacts
                    ? $respPerson->contacts->map(fn (Contact $c): string => $c->type->getLabel().': '.$c->value)->implode(' / ')
                    : '';

                // Formações e Horários
                $formations = $contract->items->map(fn ($item) => $item->formation?->name)->filter()->unique()->implode(', ');

                $schedules = $contract->items->flatMap(fn ($item) => $item->schedules)->filter();

                $days = $schedules->map(fn (StudentSchedule $s): string => $weekdaysMap[(string) $s->weekday] ?? '')->filter()->unique()->implode(', ');
                $hours = $schedules->map(fn (StudentSchedule $s): string => $s->formattedStartHour().' às '.$s->formattedEndHour())->unique()->implode(', ');
                $rooms = $schedules->map(fn (StudentSchedule $s): string => $s->classroom?->name ?? '')->filter()->unique()->implode(', ');

                $statusLabel = match ($contract->status) {
                    'active' => 'Ativo',
                    'draft' => 'Rascunho',
                    'suspended' => 'Suspenso',
                    'canceled' => 'Cancelado',
                    'finished' => 'Finalizado',
                    default => $contract->status,
                };

                $studentNeighborhood = $student?->addresses->sortByDesc('is_primary')->first()?->neighborhood ?? '';
                $coursesStr = $contract->items->flatMap(fn ($item) => $item->contractCourses->map(fn ($cc) => $cc->course?->name))->filter()->unique()->implode(', ');

                fputcsv($stream, [
                    $student?->name ?? '—',
                    $student?->cpf ?? '—',
                    $student?->birth_date?->format('d/m/Y') ?? '—',
                    $studentNeighborhood ?: '—',
                    $emailList->implode(' / ') ?: '—',
                    $phoneList ?: '—',
                    $whatsappList ?: '—',
                    $otherList ?: '—',
                    $contract->contract_number ?? '—',
                    $statusLabel,
                    $contract->start_date?->format('d/m/Y') ?? '—',
                    $contract->end_date?->format('d/m/Y') ?? '—',
                    $respName ?: '—',
                    $respRel ?: '—',
                    $respContacts ?: '—',
                    $formations ?: '—',
                    $coursesStr ?: '—',
                    $days ?: '—',
                    $hours ?: '—',
                    $rooms ?: '—',
                ], ';', '"', '');
            }

            fclose($stream);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}

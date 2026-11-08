<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Classroom;
use App\Models\Collaborator;
use App\Models\Contract;
use App\Models\Course;
use App\Models\School;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class SchoolOnboardingWidget extends Widget
{
    protected static ?int $sort = -10;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.school-onboarding-widget';

    public bool $isDismissed = false;

    public function getColumnSpan(): int|string|array
    {
        return 'full';
    }

    public function mount(): void
    {
        $this->isDismissed = (bool) session('onboarding_widget_dismissed', false);
    }

    public static function canView(): bool
    {
        if (! Auth::check()) {
            return false;
        }

        if (session('onboarding_widget_dismissed', false)) {
            return false;
        }

        // Exibido para administradores e gestores
        return Auth::user()->hasRole('super_admin') || Auth::user()->canAccessPanel(filament()->getCurrentPanel());
    }

    public function dismiss(): void
    {
        $this->isDismissed = true;
        session(['onboarding_widget_dismissed' => true]);
    }

    public function getStepsProperty(): array
    {
        $hasSchoolInfo = School::query()->withoutGlobalScopes()->whereNotNull('cnpj')->whereNotNull('trade_name')->exists();
        $hasCourse = Course::count() > 0;
        $hasClassroom = Classroom::count() > 0;
        $hasCollaborator = Collaborator::count() > 0;
        $hasContract = Contract::count() > 0;

        $steps = [
            [
                'id' => 'school',
                'title' => 'Identificação da Escola',
                'description' => 'Nome da instituição, CNPJ e contatos cadastrados.',
                'done' => $hasSchoolInfo,
                'url' => url('/admin/schools'),
            ],
            [
                'id' => 'course',
                'title' => 'Cadastrar Primeiro Curso',
                'description' => 'Crie as opções de cursos ou modalidades oferecidas.',
                'done' => $hasCourse,
                'url' => url('/admin/courses/create'),
            ],
            [
                'id' => 'classroom',
                'title' => 'Criar Primeira Turma / Sala',
                'description' => 'Defina os horários e salas de aula da instituição.',
                'done' => $hasClassroom,
                'url' => url('/admin/classrooms/create'),
            ],
            [
                'id' => 'collaborator',
                'title' => 'Cadastrar Equipe / Educador',
                'description' => 'Cadastre os professores, recepcionistas e equipe escolar.',
                'done' => $hasCollaborator,
                'url' => url('/admin/collaborators/create'),
            ],
            [
                'id' => 'enrollment',
                'title' => 'Matricular Primeiro Aluno',
                'description' => 'Gere o primeiro contrato acadêmico e financeiro.',
                'done' => $hasContract,
                'url' => url('/admin/contracts/create'),
            ],
        ];

        return $steps;
    }

    public function getProgressPercentProperty(): int
    {
        $steps = $this->steps;
        $completed = count(array_filter($steps, fn ($s) => $s['done']));
        $total = count($steps);

        return (int) round(($completed / $total) * 100);
    }
}

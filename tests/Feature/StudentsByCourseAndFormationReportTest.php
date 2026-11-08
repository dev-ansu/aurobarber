<?php

namespace Tests\Feature;

use App\Filament\Pages\StudentsByCourseAndFormationReport;
use App\Models\Contract;
use App\Models\ContractCourse;
use App\Models\ContractItem;
use App\Models\Course;
use App\Models\Formation;
use App\Models\Person;
use App\Models\School;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StudentsByCourseAndFormationReportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private School $school;
    private Formation $formationWeb;
    private Formation $formationDesign;
    private Course $coursePhp;
    private Course $courseFigma;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        $this->school = School::create([
            'legal_name' => 'Aurosoft Treinamentos Ltda',
            'trade_name' => 'Aurosoft Cursos',
            'cnpj' => '11.222.333/0001-44',
            'is_main' => true,
        ]);

        $person = Person::create([
            'name' => 'Admin Teste',
            'email' => 'admin@teste.com',
        ]);

        $this->user = User::factory()->create([
            'person_id' => $person->id,
            'email' => 'admin@teste.com',
        ]);
        $this->user->assignRole('super_admin');
        $this->actingAs($this->user);

        Filament::setTenant($this->school);

        // Criar Formações e Cursos
        $this->formationWeb = Formation::create([
            'name' => 'Desenvolvedor Full Stack',
            'description' => 'Formação de programação web completa',
            'active' => true,
        ]);

        $this->formationDesign = Formation::create([
            'name' => 'Designer Gráfico Pro',
            'description' => 'Formação de design e UI',
            'active' => true,
        ]);

        $this->coursePhp = Course::create([
            'name' => 'PHP & Laravel Moderno',
            'workload' => 40,
            'active' => true,
        ]);

        $this->courseFigma = Course::create([
            'name' => 'Figma UI/UX Design',
            'workload' => 30,
            'active' => true,
        ]);

        $this->formationWeb->courses()->attach($this->coursePhp->id, ['order' => 1]);
        $this->formationDesign->courses()->attach($this->courseFigma->id, ['order' => 1]);

        // Criar Contratos e Alunos
        $student1 = Person::create(['name' => 'Lucas Web', 'cpf' => '11111111111']);
        $contract1 = Contract::create([
            'school_id' => $this->school->id,
            'student_id' => $student1->id,
            'contract_number' => 'CTR-WEB-001',
            'status' => 'active',
            'total_value' => 2500.00,
            'start_date' => '2026-02-01',
        ]);
        $item1 = ContractItem::create([
            'contract_id' => $contract1->id,
            'formation_id' => $this->formationWeb->id,
            'order' => 1,
        ]);
        ContractCourse::create([
            'contract_item_id' => $item1->id,
            'course_id' => $this->coursePhp->id,
            'order' => 1,
            'status' => 'in_progress',
        ]);

        $student2 = Person::create(['name' => 'Ana Web', 'cpf' => '22222222222']);
        $contract2 = Contract::create([
            'school_id' => $this->school->id,
            'student_id' => $student2->id,
            'contract_number' => 'CTR-WEB-002',
            'status' => 'concluded',
            'total_value' => 2500.00,
            'start_date' => '2026-01-10',
        ]);
        $item2 = ContractItem::create([
            'contract_id' => $contract2->id,
            'formation_id' => $this->formationWeb->id,
            'order' => 1,
            'formation_completed_at' => now(),
        ]);
        ContractCourse::create([
            'contract_item_id' => $item2->id,
            'course_id' => $this->coursePhp->id,
            'order' => 1,
            'status' => 'completed',
            'grade' => 9.5,
            'completed_at' => now(),
        ]);

        $student3 = Person::create(['name' => 'Carlos Design', 'cpf' => '33333333333']);
        $contract3 = Contract::create([
            'school_id' => $this->school->id,
            'student_id' => $student3->id,
            'contract_number' => 'CTR-DSG-001',
            'status' => 'active',
            'total_value' => 1800.00,
            'start_date' => '2026-03-01',
        ]);
        $item3 = ContractItem::create([
            'contract_id' => $contract3->id,
            'formation_id' => $this->formationDesign->id,
            'order' => 1,
        ]);
        ContractCourse::create([
            'contract_item_id' => $item3->id,
            'course_id' => $this->courseFigma->id,
            'order' => 1,
            'status' => 'in_progress',
        ]);
    }

    public function test_super_admin_can_access_students_by_course_and_formation_report_page(): void
    {
        $this->actingAs($this->user);

        Livewire::test(StudentsByCourseAndFormationReport::class)
            ->assertSuccessful()
            ->assertSee('Alunos por Formação')
            ->assertSee('Alunos por Curso')
            ->assertSee('Desenvolvedor Full Stack')
            ->assertSee('Designer Gráfico Pro');
    }

    public function test_formation_statistics_rank_top_formation_and_count_enrollments(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(StudentsByCourseAndFormationReport::class);

        $stats = $component->instance()->getFormationStats();
        expect($stats['top_formation_name'])->toBe('Desenvolvedor Full Stack')
            ->and($stats['top_formation_count'])->toBe(2)
            ->and($stats['total_enrollments'])->toBe(3)
            ->and($stats['total_active'])->toBe(2)
            ->and((float) $stats['total_revenue'])->toBe(6800.00);

        $formations = $component->instance()->getFormationsData();
        expect($formations)->toHaveCount(2)
            ->and($formations[0]['name'])->toBe('Desenvolvedor Full Stack')
            ->and($formations[0]['rank'])->toBe(1)
            ->and($formations[0]['total_contracts'])->toBe(2)
            ->and($formations[0]['active_count'])->toBe(1)
            ->and($formations[0]['concluded_count'])->toBe(1)
            ->and($formations[1]['name'])->toBe('Designer Gráfico Pro')
            ->and($formations[1]['rank'])->toBe(2);
    }

    public function test_courses_tab_statistics_and_completion_rates(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(StudentsByCourseAndFormationReport::class)
            ->call('setTab', 'courses')
            ->assertSet('activeTab', 'courses')
            ->assertSee('PHP & Laravel Moderno')
            ->assertSee('Figma UI/UX Design');

        $stats = $component->instance()->getCourseStats();
        expect($stats['top_course_name'])->toBe('PHP & Laravel Moderno')
            ->and($stats['top_course_count'])->toBe(2)
            ->and($stats['total_enrollments'])->toBe(3)
            ->and($stats['total_in_progress'])->toBe(2)
            ->and($stats['total_completed'])->toBe(1);

        $courses = $component->instance()->getCoursesData();
        expect($courses)->toHaveCount(2)
            ->and($courses[0]['name'])->toBe('PHP & Laravel Moderno')
            ->and($courses[0]['total_enrollments'])->toBe(2)
            ->and($courses[0]['in_progress_count'])->toBe(1)
            ->and($courses[0]['completed_count'])->toBe(1)
            ->and($courses[0]['completion_rate'])->toBe(50.0)
            ->and((float) $courses[0]['average_grade'])->toBe(9.5);
    }

    public function test_can_open_details_modal_for_formation_students(): void
    {
        $this->actingAs($this->user);

        Livewire::test(StudentsByCourseAndFormationReport::class)
            ->call('openFormationDetails', $this->formationWeb->id)
            ->assertSet('viewingFormationId', $this->formationWeb->id)
            ->assertSee('Detalhamento de Alunos')
            ->assertSee('Lucas Web')
            ->assertSee('Ana Web')
            ->assertSee('CTR-WEB-001')
            ->assertSee('CTR-WEB-002')
            ->call('closeDetailsModal')
            ->assertSet('viewingFormationId', null);
    }

    public function test_can_open_details_modal_for_course_students(): void
    {
        $this->actingAs($this->user);

        Livewire::test(StudentsByCourseAndFormationReport::class)
            ->call('setTab', 'courses')
            ->call('openCourseDetails', $this->coursePhp->id)
            ->assertSet('viewingCourseId', $this->coursePhp->id)
            ->assertSee('Detalhamento de Alunos')
            ->assertSee('Lucas Web')
            ->assertSee('Ana Web')
            ->assertSee('Cursando')
            ->assertSee('Concluído')
            ->assertSee('9,5')
            ->call('closeDetailsModal')
            ->assertSet('viewingCourseId', null);
    }

    public function test_csv_export_streams_for_formations_and_courses(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(StudentsByCourseAndFormationReport::class);

        $responseFormations = $component->instance()->exportFormationsCsv();
        expect($responseFormations->getStatusCode())->toBe(200)
            ->and($responseFormations->headers->get('content-type'))->toContain('text/csv');

        $responseCourses = $component->instance()->exportCoursesCsv();
        expect($responseCourses->getStatusCode())->toBe(200)
            ->and($responseCourses->headers->get('content-type'))->toContain('text/csv');
    }

    public function test_filter_by_status_and_search(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(StudentsByCourseAndFormationReport::class)
            ->set('statusFilter', 'active');

        $formations = $component->instance()->getFormationsData();
        // Formação Web teve 1 contrato ativo (o outro é concluído)
        expect($formations[0]['active_count'])->toBe(1);

        $component->set('search', 'Full Stack');
        $filtered = $component->instance()->getFormationsData();
        expect($filtered)->toHaveCount(1)
            ->and($filtered[0]['name'])->toBe('Desenvolvedor Full Stack');

        $component->call('resetFilters');
        expect($component->get('search'))->toBe('')
            ->and($component->get('statusFilter'))->toBe('all');
    }
}

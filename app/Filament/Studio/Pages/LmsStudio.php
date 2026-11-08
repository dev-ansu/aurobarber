<?php

declare(strict_types=1);

namespace App\Filament\Studio\Pages;

use App\Models\Course;
use App\Models\LmsLesson;
use App\Models\LmsLessonStep;
use App\Models\LmsModule;
use App\Services\LmsContentBackupService;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class LmsStudio extends Page implements HasForms
{
    use InteractsWithForms;
    use WithFileUploads;
    use WithPagination;

    public ?array $stepFormData = [];

    public string $ratingsFilter = 'all';

    public string $ratingsSearch = '';

    public int $ratingsPerPage = 10;
    protected static string|UnitEnum|null $navigationGroup = 'Pedagógico';

    protected static ?string $navigationLabel = 'Estúdio de Aulas (LMS)';

    protected static ?string $title = 'Estúdio de Aulas Interativas Aurosoft';

    protected static ?string $slug = 'lms-studio';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.lms-studio';

    // Estado da seleção
    public ?int $selectedCourseId = null;

    public ?int $selectedModuleId = null;

    public ?int $selectedLessonId = null;

    // Modais
    public bool $showModuleModal = false;

    public ?int $editingModuleId = null;

    public string $moduleTitle = '';

    public string $moduleDescription = '';

    public bool $showLessonModal = false;

    public ?int $editingLessonId = null;

    public string $lessonTitle = '';

    public string $lessonDescription = '';

    public int $lessonDuration = 90;

    public float $lessonPassingScore = 80.0;

    public bool $showStepModal = false;

    public string $stepPhase = 'praelectio';

    public string $stepType = 'theory';

    public string $stepTitle = '';

    public string $stepBody = '';

    public string $stepInstructions = '';

    public string $stepQuestion = '';

    public string $stepOptionA = '';

    public string $stepOptionB = '';

    public string $stepOptionC = '';

    public string $stepCorrectOption = 'A';

    public string $stepExplanation = '';

    public string $stepMediaType = 'none'; // none, image, audio, video

    public string $stepMediaUrl = '';

    public $mediaUpload = null;

    public string $uploadedFileName = '';

    public string $mediaSourceType = 'upload';

    // Banco de Questões & Anti-Cola (Quiz)
    public bool $quizRandomize = false;

    public int $quizQuestionsToDraw = 1;

    public bool $quizShuffleOptions = true;

    /** @var array<int, array<string, mixed>> */
    public array $quizQuestionPool = [];

    // Simulador Interativo Guiado Multi-Passos (Sub-steps)
    /** @var array<int, array<string, mixed>> */
    public array $simulationSubSteps = [];

    // Trabalho Prático / Upload de Exercício (Assignment)
    public string $assignmentAllowedExtensions = 'xlsx,docx,pdf,png,jpg,zip';

    public bool $assignmentRequiresEducatorApproval = false;

    // Coordenadas do Hotspot no Simulador de Imagem
    public string $hotspotInteractionType = 'click'; // click, select_text, double_click, right_click

    public ?float $hotspotX = null;

    public ?float $hotspotY = null;

    public ?float $hotspotEndX = null;

    public ?float $hotspotEndY = null;

    public string $hotspotDirection = 'left_to_right'; // left_to_right, right_to_left, any

    public float $hotspotRadius = 6.0;

    public string $hotspotLabel = '';

    public string $hotspotExpectedText = '';

    public bool $hotspotCaseSensitive = false;

    public bool $hotspotTrimWhitespace = true;

    public float $hotspotInputWidth = 35.0;

    public ?int $editingStepId = null;

    // Subpassos e páginas hierárquicas (1, 1.1, 1.2...)
    public bool $stepIsSubstep = false;

    public ?int $parentStepIdForSubstep = null;

    // Tempo de tela / permanência obrigatória (segundos)
    public int $stepMinStaySeconds = 0;

    // Exportação e Importação de Pacote de Curso
    public bool $showImportPackageModal = false;

    public $packageFile = null;

    public bool $isStepFullscreen = false;

    public bool $showTutorialModal = false;

    public bool $showRatingsModal = false;

    // Assistente IA
    public bool $showAiModal = false;

    public string $aiTopic = '';

    public string $aiLevel = 'Iniciante';

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof \App\Models\User && (
            $user->can('View:LmsStudio')
            || $user->can('page_LmsStudio')
            || $user->can('view_lms_studio')
            || $user->can('View:LmsLesson')
        );
    }

    public function mount(): void
    {
        $requestedCourseId = request()->query('course_id');
        if ($requestedCourseId) {
            $this->selectedCourseId = (int) $requestedCourseId;
        } else {
            $firstLocalCourse = Course::query()->whereNull('ouro_moderno_id')->first();
            $this->selectedCourseId = $firstLocalCourse?->id;
        }

        $requestedLessonId = request()->query('lesson_id');
        if ($requestedLessonId) {
            $lesson = LmsLesson::with('module')->find((int) $requestedLessonId);
            if ($lesson) {
                $this->selectedCourseId = $lesson->module?->course_id ?? $this->selectedCourseId;
                $this->selectedModuleId = $lesson->module_id;
                $this->selectedLessonId = $lesson->id;
            }
        }

        $this->syncSelection();

        $this->form->fill([
            'body' => $this->stepBody,
        ]);

        if ($editStepId = request()->query('edit_step')) {
            $step = LmsLessonStep::with('lesson.module')->find((int) $editStepId);
            if ($step) {
                $this->selectedCourseId = $step->lesson?->module?->course_id ?? $this->selectedCourseId;
                $this->selectedModuleId = $step->lesson?->module_id ?? $this->selectedModuleId;
                $this->selectedLessonId = $step->lesson_id;
                $this->openEditStepModal($step->id);
            }
        } elseif ($createPhase = request()->query('create_step_phase')) {
            $this->openCreateStepModal((string) $createPhase);
        } elseif (request()->boolean('open_question_bank')) {
            $this->openQuestionBankBuilder();
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('stepFormData')
            ->schema([
                RichEditor::make('body')
                    ->label('Conteúdo Explicativo (Teoria & Conceito)')
                    ->placeholder('Escreva ou cole a explicação teórica aqui...')
                    ->toolbarButtons([
                        'bold',
                        'italic',
                        'strike',
                        'underline',
                        'bulletList',
                        'orderedList',
                        'h2',
                        'h3',
                        'undo',
                        'redo',
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public function openQuestionBankBuilder(): void
    {
        if (! $this->selectedLessonId) {
            $this->syncSelection();
        }

        if ($this->selectedLessonId) {
            $existingQuizStep = LmsLessonStep::query()
                ->where('lesson_id', $this->selectedLessonId)
                ->where('step_type', 'quiz')
                ->orderBy('sort_order')
                ->first();

            if ($existingQuizStep) {
                $this->openEditStepModal($existingQuizStep->id);
                $this->quizRandomize = true;
                $this->quizShuffleOptions = true;
                if ($this->quizQuestionPool === []) {
                    $this->addQuizPoolQuestion();
                    $this->addQuizPoolQuestion();
                }

                return;
            }
        }

        $this->openCreateStepModal('examen');
        $this->stepType = 'quiz';
        $this->stepTitle = 'Avaliação com Sorteio do Banco de Questões';
        $this->quizRandomize = true;
        $this->quizShuffleOptions = true;
        $this->quizQuestionsToDraw = 2;
        $this->addQuizPoolQuestion();
        $this->addQuizPoolQuestion();
    }

    public function updatedSelectedCourseId(): void
    {
        $this->selectedModuleId = null;
        $this->selectedLessonId = null;
        $this->syncSelection();
    }

    public function updatedSelectedModuleId(): void
    {
        $this->selectedLessonId = null;
        $this->syncSelection();
    }

    protected function syncSelection(): void
    {
        if ($this->selectedCourseId && ! $this->selectedModuleId) {
            $firstModule = LmsModule::query()
                ->where('course_id', $this->selectedCourseId)
                ->orderBy('sort_order')
                ->first();
            $this->selectedModuleId = $firstModule?->id;
        }

        if ($this->selectedModuleId && ! $this->selectedLessonId) {
            $firstLesson = LmsLesson::query()
                ->where('module_id', $this->selectedModuleId)
                ->orderBy('sort_order')
                ->first();
            $this->selectedLessonId = $firstLesson?->id;
        }
    }

    /**
     * @return Collection<int, Course>
     */
    public function getCoursesProperty(): Collection
    {
        return Course::query()
            ->whereNull('ouro_moderno_id')
            ->orderBy('name')
            ->get();
    }

    public function getSelectedCourseProperty(): ?Course
    {
        return $this->selectedCourseId ? Course::find($this->selectedCourseId) : null;
    }

    public function getSelectedModuleProperty(): ?LmsModule
    {
        return $this->selectedModuleId ? LmsModule::find($this->selectedModuleId) : null;
    }

    public function getSelectedLessonProperty(): ?LmsLesson
    {
        return $this->selectedLessonId ? LmsLesson::with('steps')->find($this->selectedLessonId) : null;
    }

    public function openCreateModuleModal(): void
    {
        if (! $this->selectedCourseId) {
            Notification::make()->title('Selecione um curso primeiro')->warning()->send();

            return;
        }

        $this->editingModuleId = null;
        $this->moduleTitle = '';
        $this->moduleDescription = '';
        $this->showModuleModal = true;
    }

    public function openEditModuleModal(int $id): void
    {
        $module = LmsModule::find($id);
        if (! $module) {
            return;
        }

        $this->editingModuleId = $module->id;
        $this->moduleTitle = $module->title;
        $this->moduleDescription = $module->description ?? '';
        $this->showModuleModal = true;
    }

    public function saveModule(): void
    {
        $this->validate([
            'moduleTitle' => ['required', 'string', 'min:3', 'max:255'],
        ], [
            'moduleTitle.required' => 'Informe o título da seção.',
        ]);

        if ($this->editingModuleId) {
            $module = LmsModule::find($this->editingModuleId);
            if ($module) {
                $module->update([
                    'title' => $this->moduleTitle,
                    'description' => $this->moduleDescription,
                ]);
                $this->selectedModuleId = $module->id;
                $this->showModuleModal = false;
                Notification::make()->title('Seção atualizada com sucesso!')->success()->send();

                return;
            }
        }

        $maxOrder = LmsModule::where('course_id', $this->selectedCourseId)->max('sort_order') ?? 0;

        $module = LmsModule::create([
            'course_id' => $this->selectedCourseId,
            'title' => $this->moduleTitle,
            'description' => $this->moduleDescription,
            'sort_order' => $maxOrder + 1,
            'is_active' => true,
        ]);

        $this->selectedModuleId = $module->id;
        $this->showModuleModal = false;

        Notification::make()->title('Seção criada com sucesso!')->success()->send();
    }

    public function deleteModule(int $id): void
    {
        $module = LmsModule::find($id);
        if ($module) {
            $module->delete();
            $this->selectedModuleId = null;
            $this->selectedLessonId = null;
            $this->syncSelection();
            Notification::make()->title('Seção removida')->success()->send();
        }
    }

    public function openCreateLessonModal(?int $moduleId = null): void
    {
        if ($moduleId) {
            $this->selectedModuleId = $moduleId;
        }

        if (! $this->selectedCourseId) {
            Notification::make()->title('Selecione um curso primeiro')->warning()->send();

            return;
        }

        // Se o curso ainda não tiver nenhuma seção, cria automaticamente a Seção 1
        if (! $this->selectedModuleId) {
            $firstModule = LmsModule::where('course_id', $this->selectedCourseId)->first();
            if ($firstModule) {
                $this->selectedModuleId = $firstModule->id;
            } else {
                $newModule = LmsModule::create([
                    'course_id' => $this->selectedCourseId,
                    'title' => 'Seção 1: Conteúdo Principal',
                    'description' => 'Aulas e atividades principais do curso.',
                    'sort_order' => 1,
                    'is_active' => true,
                ]);
                $this->selectedModuleId = $newModule->id;
            }
        }

        $this->editingLessonId = null;
        $this->lessonTitle = '';
        $this->lessonDescription = '';
        $this->lessonDuration = 90;
        $this->lessonPassingScore = 80.0;
        $this->showLessonModal = true;
    }

    public function openEditLessonModal(int $id): void
    {
        $lesson = LmsLesson::find($id);
        if (! $lesson) {
            return;
        }

        $this->editingLessonId = $lesson->id;
        $this->selectedModuleId = $lesson->module_id;
        $this->lessonTitle = $lesson->title;
        $this->lessonDescription = $lesson->description ?? '';
        $this->lessonDuration = $lesson->estimated_duration_minutes;
        $this->lessonPassingScore = (float) $lesson->passing_score;
        $this->showLessonModal = true;
    }

    public function saveLesson(): void
    {
        $this->validate([
            'selectedModuleId' => ['required', 'exists:lms_modules,id'],
            'lessonTitle' => ['required', 'string', 'min:3', 'max:255'],
            'lessonDuration' => ['required', 'integer', 'min:30', 'max:240'],
            'lessonPassingScore' => ['required', 'numeric', 'min:50', 'max:100'],
        ], [
            'selectedModuleId.required' => 'Selecione a seção da aula.',
            'lessonTitle.required' => 'Informe o título da aula.',
        ]);

        if ($this->editingLessonId) {
            $lesson = LmsLesson::find($this->editingLessonId);
            if ($lesson) {
                $lesson->update([
                    'module_id' => $this->selectedModuleId,
                    'title' => $this->lessonTitle,
                    'description' => $this->lessonDescription,
                    'estimated_duration_minutes' => $this->lessonDuration,
                    'passing_score' => $this->lessonPassingScore,
                ]);
                $this->selectedLessonId = $lesson->id;
                $this->showLessonModal = false;
                Notification::make()->title('Aula atualizada com sucesso!')->success()->send();

                return;
            }
        }

        $maxOrder = LmsLesson::where('module_id', $this->selectedModuleId)->max('sort_order') ?? 0;

        $lesson = LmsLesson::create([
            'module_id' => $this->selectedModuleId,
            'title' => $this->lessonTitle,
            'description' => $this->lessonDescription,
            'estimated_duration_minutes' => $this->lessonDuration,
            'passing_score' => $this->lessonPassingScore,
            'sort_order' => $maxOrder + 1,
            'is_active' => true,
        ]);

        $this->selectedLessonId = $lesson->id;
        $this->showLessonModal = false;

        Notification::make()->title('Aula criada com sucesso!')->success()->send();
    }

    public function deleteLesson(int $id): void
    {
        $lesson = LmsLesson::find($id);
        if ($lesson) {
            $lesson->delete();
            $this->selectedLessonId = null;
            $this->syncSelection();
            Notification::make()->title('Aula removida')->success()->send();
        }
    }

    public function openTutorialModal(): void
    {
        $this->showTutorialModal = true;
    }

    public function openCreateStepModal(string $phase = 'praelectio'): void
    {
        if (! $this->selectedLessonId) {
            Notification::make()->title('Selecione ou crie uma aula primeiro')->warning()->send();

            return;
        }

        $this->editingStepId = null;
        $this->parentStepIdForSubstep = null;
        $this->stepIsSubstep = false;
        $this->stepMinStaySeconds = 0;
        $this->stepPhase = $phase;
        $this->stepType = match ($phase) {
            'praelectio' => 'theory',
            'practice' => 'hotspot_simulation',
            'repetitio' => 'quiz',
            'concertatio' => 'challenge',
            'examen' => 'quiz',
            default => 'theory',
        };
        $this->stepTitle = '';
        $this->stepBody = '';
        $this->stepInstructions = '';
        $this->stepQuestion = '';
        $this->stepOptionA = '';
        $this->stepOptionB = '';
        $this->stepOptionC = '';
        $this->stepCorrectOption = 'A';
        $this->stepExplanation = '';
        $this->stepMediaType = 'none';
        $this->stepMediaUrl = '';
        $this->mediaUpload = null;
        $this->uploadedFileName = '';
        $this->mediaSourceType = 'upload';
        $this->quizRandomize = false;
        $this->quizQuestionsToDraw = 1;
        $this->quizShuffleOptions = true;
        $this->quizQuestionPool = [];
        $this->simulationSubSteps = [];
        $this->assignmentAllowedExtensions = 'xlsx,docx,pdf,png,jpg,zip';
        $this->assignmentRequiresEducatorApproval = false;
        $this->hotspotInteractionType = 'click';
        $this->hotspotX = null;
        $this->hotspotY = null;
        $this->hotspotEndX = null;
        $this->hotspotEndY = null;
        $this->hotspotDirection = 'left_to_right';
        $this->hotspotRadius = 6.0;
        $this->hotspotLabel = '';
        $this->hotspotExpectedText = '';
        $this->hotspotCaseSensitive = false;
        $this->hotspotTrimWhitespace = true;
        $this->hotspotInputWidth = 35.0;
        $this->form->fill(['body' => '']);
        $this->showStepModal = true;
    }

    public function openCreateSubStepModal(int $parentStepId): void
    {
        $parent = LmsLessonStep::find($parentStepId);
        if (! $parent) {
            return;
        }

        $this->editingStepId = null;
        $this->parentStepIdForSubstep = $parentStepId;
        $this->stepIsSubstep = true;
        $this->stepPhase = $parent->phase;
        $this->stepType = $parent->step_type;
        $this->stepTitle = $parent->title;
        $this->stepMinStaySeconds = (int) ($parent->content['min_stay_seconds'] ?? 0);
        $this->stepBody = '';
        $this->stepInstructions = '';
        $this->stepQuestion = '';
        $this->stepOptionA = '';
        $this->stepOptionB = '';
        $this->stepOptionC = '';
        $this->stepCorrectOption = 'A';
        $this->stepExplanation = '';
        $this->stepMediaType = 'none';
        $this->stepMediaUrl = '';
        $this->mediaUpload = null;
        $this->uploadedFileName = '';
        $this->mediaSourceType = 'upload';
        $this->quizRandomize = false;
        $this->quizQuestionsToDraw = 1;
        $this->quizShuffleOptions = true;
        $this->quizQuestionPool = [];
        $this->simulationSubSteps = [];
        $this->assignmentAllowedExtensions = 'xlsx,docx,pdf,png,jpg,zip';
        $this->assignmentRequiresEducatorApproval = false;
        $this->hotspotInteractionType = 'click';
        $this->hotspotX = null;
        $this->hotspotY = null;
        $this->hotspotEndX = null;
        $this->hotspotEndY = null;
        $this->hotspotDirection = 'left_to_right';
        $this->hotspotRadius = 6.0;
        $this->hotspotLabel = '';
        $this->hotspotExpectedText = '';
        $this->hotspotCaseSensitive = false;
        $this->hotspotTrimWhitespace = true;
        $this->hotspotInputWidth = 35.0;
        $this->form->fill(['body' => '']);
        $this->showStepModal = true;
    }

    public function addQuizPoolQuestion(): void
    {
        $this->quizQuestionPool[] = [
            'question' => '',
            'option_a' => '',
            'option_b' => '',
            'option_c' => '',
            'correct_option' => 'A',
            'explanation_on_error' => '',
        ];
    }

    public function removeQuizPoolQuestion(int $index): void
    {
        unset($this->quizQuestionPool[$index]);
        $this->quizQuestionPool = array_values($this->quizQuestionPool);
    }

    public function addSimulationSubStep(): void
    {
        $this->simulationSubSteps[] = [
            'instruction' => '',
            'media_url' => $this->stepMediaUrl ?: '',
            'interaction_type' => 'click',
            'x' => 50.0,
            'y' => 50.0,
            'radius' => 6.0,
            'expected_text' => '',
            'label' => '',
        ];
    }

    public function removeSimulationSubStep(int $index): void
    {
        unset($this->simulationSubSteps[$index]);
        $this->simulationSubSteps = array_values($this->simulationSubSteps);
    }

    public function setHotspotCoordinates(float $x, float $y): void
    {
        if ($this->hotspotInteractionType === 'select_text') {
            if ($this->hotspotX === null) {
                $this->hotspotX = round($x, 2);
                $this->hotspotY = round($y, 2);
                $this->hotspotEndX = null;
                $this->hotspotEndY = null;
            } elseif ($this->hotspotEndX === null) {
                $this->hotspotEndX = round($x, 2);
                $this->hotspotEndY = round($y, 2);
            } else {
                $this->hotspotX = round($x, 2);
                $this->hotspotY = round($y, 2);
                $this->hotspotEndX = null;
                $this->hotspotEndY = null;
            }
        } else {
            $this->hotspotX = round($x, 2);
            $this->hotspotY = round($y, 2);
        }
    }

    public function setHotspotSelection(float $startX, float $startY, float $endX, float $endY): void
    {
        $this->hotspotX = round($startX, 2);
        $this->hotspotY = round($startY, 2);
        $this->hotspotEndX = round($endX, 2);
        $this->hotspotEndY = round($endY, 2);
    }

    public function clearHotspot(): void
    {
        $this->hotspotX = null;
        $this->hotspotY = null;
        $this->hotspotEndX = null;
        $this->hotspotEndY = null;
    }

    public function getStepPreviewImageUrlProperty(): ?string
    {
        if ($this->mediaUpload && method_exists($this->mediaUpload, 'temporaryUrl')) {
            try {
                return $this->mediaUpload->temporaryUrl();
            } catch (\Throwable) {
                return null;
            }
        }

        if ($this->stepMediaUrl && $this->stepMediaType === 'image') {
            return $this->stepMediaUrl;
        }

        return null;
    }

    public function openEditStepModal(int $id): void
    {
        $step = LmsLessonStep::find($id);
        if (! $step) {
            return;
        }

        $this->editingStepId = $step->id;
        $this->stepPhase = $step->phase;
        $this->stepType = $step->step_type;
        $this->stepTitle = $step->title;
        $this->stepMediaType = $step->content['media_type'] ?? 'none';
        $this->stepMediaUrl = $step->content['media_url'] ?? '';
        $this->mediaUpload = null;
        $this->uploadedFileName = '';
        $this->stepBody = $step->content['body'] ?? ($step->content['scenario'] ?? ($step->content['instructions'] ?? ''));
        $this->stepInstructions = ! empty($step->content['instructions'])
            ? $step->content['instructions']
            : ($step->content['body'] ?? ($step->content['scenario'] ?? ''));
        $this->stepExplanation = $step->content['explanation_on_error'] ?? '';
        $this->stepQuestion = $step->content['question'] ?? '';
        $this->form->fill(['body' => $this->stepBody]);

        $hotspot = $step->content['hotspot'] ?? [];
        $this->hotspotInteractionType = $hotspot['interaction_type'] ?? 'click';
        $this->hotspotX = isset($hotspot['x']) ? (float) $hotspot['x'] : null;
        $this->hotspotY = isset($hotspot['y']) ? (float) $hotspot['y'] : null;
        $this->hotspotEndX = isset($hotspot['end_x']) ? (float) $hotspot['end_x'] : null;
        $this->hotspotEndY = isset($hotspot['end_y']) ? (float) $hotspot['end_y'] : null;
        $this->hotspotDirection = $hotspot['direction'] ?? 'left_to_right';
        $this->hotspotRadius = isset($hotspot['radius']) ? (float) $hotspot['radius'] : 6.0;
        $this->hotspotLabel = $hotspot['label'] ?? '';
        $this->hotspotExpectedText = $hotspot['expected_text'] ?? '';
        $this->hotspotCaseSensitive = (bool) ($hotspot['case_sensitive'] ?? false);
        $this->hotspotTrimWhitespace = (bool) ($hotspot['trim_whitespace'] ?? true);
        $this->hotspotInputWidth = isset($hotspot['input_width']) ? (float) $hotspot['input_width'] : 35.0;

        $options = $step->content['options'] ?? [];
        $this->stepOptionA = $options[0]['text'] ?? '';
        $this->stepOptionB = $options[1]['text'] ?? '';
        $this->stepOptionC = $options[2]['text'] ?? '';
        $this->stepCorrectOption = 'A';

        foreach ($options as $opt) {
            if (! empty($opt['is_correct'])) {
                $this->stepCorrectOption = $opt['id'] ?? 'A';
                break;
            }
        }

        // Carrega Banco de Questões (se houver questões adicionais além da primeira)
        $this->quizRandomize = (bool) ($step->content['randomize'] ?? false);
        $this->quizQuestionsToDraw = (int) ($step->content['questions_to_draw'] ?? 1);
        $this->quizShuffleOptions = (bool) ($step->content['shuffle_options'] ?? true);
        $this->quizQuestionPool = [];

        $rawPool = $step->content['question_pool'] ?? [];
        if (is_array($rawPool) && count($rawPool) > 1) {
            foreach (array_slice($rawPool, 1) as $poolItem) {
                $poolOpts = $poolItem['options'] ?? [];
                $correctOpt = 'A';
                foreach ($poolOpts as $po) {
                    if (! empty($po['is_correct'])) {
                        $correctOpt = $po['id'] ?? 'A';
                        break;
                    }
                }
                $this->quizQuestionPool[] = [
                    'question' => $poolItem['question'] ?? '',
                    'option_a' => $poolOpts[0]['text'] ?? '',
                    'option_b' => $poolOpts[1]['text'] ?? '',
                    'option_c' => $poolOpts[2]['text'] ?? '',
                    'correct_option' => $correctOpt,
                    'explanation_on_error' => $poolItem['explanation_on_error'] ?? '',
                ];
            }
        }

        // Carrega Sub-passos da Simulação Guiada
        $this->simulationSubSteps = [];
        $rawSubSteps = $step->content['sub_steps'] ?? [];
        if (is_array($rawSubSteps)) {
            foreach ($rawSubSteps as $sub) {
                $subHotspot = $sub['hotspot'] ?? $sub;
                $this->simulationSubSteps[] = [
                    'instruction' => $sub['instruction'] ?? ($sub['instructions'] ?? ''),
                    'media_url' => $sub['media_url'] ?? $this->stepMediaUrl,
                    'interaction_type' => $subHotspot['interaction_type'] ?? 'click',
                    'x' => (float) ($subHotspot['x'] ?? 50.0),
                    'y' => (float) ($subHotspot['y'] ?? 50.0),
                    'radius' => (float) ($subHotspot['radius'] ?? 6.0),
                    'expected_text' => (string) ($subHotspot['expected_text'] ?? ''),
                    'label' => (string) ($subHotspot['label'] ?? ''),
                ];
            }
        }

        // Carrega configurações de Trabalho Prático (Assignment)
        $this->assignmentAllowedExtensions = (string) ($step->content['allowed_extensions'] ?? 'xlsx,docx,pdf,png,jpg,zip');
        $this->assignmentRequiresEducatorApproval = (bool) ($step->content['requires_educator_approval'] ?? false);

        // Subpasso e tempo de permanência
        $this->parentStepIdForSubstep = null;
        $this->stepIsSubstep = (bool) ($step->content['is_substep'] ?? false);
        $this->stepMinStaySeconds = (int) ($step->content['min_stay_seconds'] ?? 0);

        $this->showStepModal = true;
    }

    /**
     * Recebe partes (chunks de 768KB em base64) de arquivos grandes (até 250MB),
     * contornando limitações de upload_max_filesize=2M e post_max_size=8M do PHP CLI embutido.
     *
     * @return array<string, mixed>
     */
    public function uploadMediaChunk(
        string $uploadId,
        string $fileName,
        int $chunkIndex,
        int $totalChunks,
        string $base64Data
    ): array {
        $safeId = preg_replace('/[^a-zA-Z0-9_-]/', '', $uploadId) ?: 'upload';
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedVideo = ['mp4', 'webm', 'ogg', 'mov', 'mkv'];
        $allowedPdf = ['pdf'];
        $allowedAudio = ['mp3', 'wav', 'ogg', 'm4a'];
        $allowedImage = ['png', 'jpg', 'jpeg', 'webp', 'gif'];
        $allAllowed = array_unique(array_merge($allowedVideo, $allowedPdf, $allowedAudio, $allowedImage));

        if (! in_array($extension, $allAllowed, true)) {
            $message = "Formato de arquivo (.{$extension}) não permitido. Use MP4, WebM, MOV, MKV, PDF, MP3, WAV, PNG ou JPG.";
            $this->addError('mediaUpload', $message);

            return ['error' => $message];
        }

        $tmpDir = storage_path('app/livewire-tmp');
        if (! is_dir($tmpDir)) {
            @mkdir($tmpDir, 0775, true);
        }

        $tempFilePath = $tmpDir . '/chunk_' . $safeId . '.' . $extension;

        if ($chunkIndex === 0 && file_exists($tempFilePath)) {
            @unlink($tempFilePath);
        }

        if (str_contains($base64Data, ',')) {
            $base64Data = substr($base64Data, strpos($base64Data, ',') + 1);
        }

        $binaryChunk = base64_decode($base64Data, true);
        if ($binaryChunk === false) {
            return ['error' => 'Falha ao decodificar pacote de dados do arquivo.'];
        }

        file_put_contents($tempFilePath, $binaryChunk, FILE_APPEND | LOCK_EX);

        if ($chunkIndex + 1 < $totalChunks) {
            return [
                'completed' => false,
                'chunk' => $chunkIndex + 1,
                'total' => $totalChunks,
            ];
        }

        // Último chunk recebido: move para storage/app/public/lms-media
        $finalFileName = \Illuminate\Support\Str::ulid() . '.' . $extension;
        Storage::disk('public')->putFileAs('lms-media', new \Illuminate\Http\File($tempFilePath), $finalFileName);
        @unlink($tempFilePath);

        $this->stepMediaUrl = Storage::url('lms-media/' . $finalFileName);
        $this->uploadedFileName = $fileName;
        $this->mediaUpload = null;
        $this->resetValidation('mediaUpload');

        if (in_array($extension, $allowedVideo, true)) {
            $this->stepMediaType = 'video';
        } elseif (in_array($extension, $allowedPdf, true)) {
            $this->stepMediaType = 'pdf';
        } elseif (in_array($extension, $allowedAudio, true)) {
            $this->stepMediaType = 'audio';
        } elseif (in_array($extension, $allowedImage, true)) {
            $this->stepMediaType = 'image';
        }

        Notification::make()
            ->title('Arquivo enviado com sucesso!')
            ->body("{$fileName} foi carregado e vinculado a este passo.")
            ->success()
            ->send();

        return [
            'completed' => true,
            'url' => $this->stepMediaUrl,
            'mediaType' => $this->stepMediaType,
            'fileName' => $fileName,
        ];
    }

    public function updatedMediaUpload(): void
    {
        $this->validateMediaUpload();
    }

    protected function validateMediaUpload(): void
    {
        if (! $this->mediaUpload) {
            return;
        }

        $extension = strtolower((string) $this->mediaUpload->getClientOriginalExtension());
        $mime = (string) $this->mediaUpload->getMimeType();

        // 1. Vídeo
        if ($this->stepMediaType === 'video' || in_array($extension, ['mp4', 'webm', 'ogg', 'mov', 'mkv', 'avi']) || str_starts_with($mime, 'video/')) {
            $this->validate([
                'mediaUpload' => ['file', 'mimes:mp4,webm,ogg,mov,mkv', 'max:256000'],
            ], [
                'mediaUpload.mimes' => 'A videoaula deve estar no formato MP4, WebM, OGG, MOV ou MKV.',
                'mediaUpload.max' => 'A videoaula não pode ultrapassar o limite de 250MB. Para vídeos maiores, utilize link do YouTube ou Vimeo.',
            ]);
            $this->stepMediaType = 'video';

            return;
        }

        // 2. Apresentação / Documento PDF
        if ($this->stepMediaType === 'pdf' || $extension === 'pdf' || str_contains($mime, 'pdf')) {
            $this->validate([
                'mediaUpload' => ['file', 'mimes:pdf', 'max:51200'],
            ], [
                'mediaUpload.mimes' => 'O arquivo da apresentação/slides deve ser um PDF.',
                'mediaUpload.max' => 'O arquivo PDF não pode ultrapassar 50MB.',
            ]);
            $this->stepMediaType = 'pdf';

            return;
        }

        // 3. Áudio
        if ($this->stepMediaType === 'audio' || in_array($extension, ['mp3', 'wav', 'ogg', 'm4a']) || str_starts_with($mime, 'audio/')) {
            $this->validate([
                'mediaUpload' => ['file', 'mimes:mp3,wav,ogg,m4a', 'max:25600'],
            ], [
                'mediaUpload.mimes' => 'O áudio deve estar no formato MP3, WAV, OGG ou M4A.',
                'mediaUpload.max' => 'O arquivo de áudio não pode ultrapassar 25MB.',
            ]);
            $this->stepMediaType = 'audio';

            return;
        }

        // 4. Imagem
        $this->validate([
            'mediaUpload' => ['file', 'image', 'mimes:png,jpg,jpeg,webp,gif', 'max:15360'],
        ], [
            'mediaUpload.image' => 'O arquivo enviado deve ser uma imagem válida.',
            'mediaUpload.mimes' => 'A imagem deve estar no formato PNG, JPG, JPEG, WebP ou GIF.',
            'mediaUpload.max' => 'A imagem não pode ultrapassar 15MB.',
        ]);
        $this->stepMediaType = 'image';
    }

    public function clearMediaUpload(): void
    {
        $this->mediaUpload = null;
        $this->uploadedFileName = '';

        Notification::make()
            ->title('Arquivo desmarcado.')
            ->info()
            ->send();
    }

    public function removeStepMedia(?int $stepId = null): void
    {
        $targetStepId = $stepId ?? $this->editingStepId;

        if ($targetStepId) {
            $step = LmsLessonStep::find($targetStepId);
            if ($step) {
                $oldMediaUrl = $step->content['media_url'] ?? null;
                if ($oldMediaUrl && str_contains($oldMediaUrl, 'lms-media')) {
                    $oldPath = 'lms-media/' . basename(parse_url($oldMediaUrl, PHP_URL_PATH));
                    if (Storage::disk('public')->exists($oldPath)) {
                        Storage::disk('public')->delete($oldPath);
                    }
                }

                $content = $step->content ?? [];
                unset($content['media_type'], $content['media_url'], $content['document_url']);
                $step->update(['content' => $content]);
            }
        }

        if (! $stepId || $stepId === $this->editingStepId) {
            $this->stepMediaUrl = '';
            $this->mediaUpload = null;
            $this->uploadedFileName = '';
            $this->stepMediaType = 'none';
        }

        Notification::make()
            ->title('Arquivo de mídia removido com sucesso.')
            ->success()
            ->send();
    }

    public function saveStep(): void
    {
        $this->validate([
            'stepTitle' => ['required', 'string', 'min:3', 'max:255'],
        ]);

        $formData = $this->form->getState();
        if (isset($formData['body']) && filled($formData['body'])) {
            $this->stepBody = $formData['body'];
        }

        $content = [];

        // Upload direto de arquivo do computador com validação estrita
        if ($this->mediaSourceType === 'upload' && $this->mediaUpload) {
            $this->validateMediaUpload();

            $path = $this->mediaUpload->store('lms-media', 'public');
            $this->stepMediaUrl = Storage::url($path);
        }

        if ($this->stepMediaType !== 'none' && ! empty($this->stepMediaUrl)) {
            $content['media_type'] = $this->stepMediaType;
            $content['media_url'] = $this->stepMediaUrl;
            if ($this->stepMediaType === 'pdf') {
                $content['document_url'] = $this->stepMediaUrl;
            }
        }

        if ($this->stepType === 'theory') {
            $content['body'] = $this->stepBody;
        } elseif ($this->stepType === 'quiz') {
            $primaryQuestion = [
                'question' => $this->stepQuestion ?: $this->stepTitle,
                'options' => [
                    ['id' => 'A', 'text' => $this->stepOptionA, 'is_correct' => $this->stepCorrectOption === 'A'],
                    ['id' => 'B', 'text' => $this->stepOptionB, 'is_correct' => $this->stepCorrectOption === 'B'],
                    ['id' => 'C', 'text' => $this->stepOptionC, 'is_correct' => $this->stepCorrectOption === 'C'],
                ],
                'explanation_on_error' => $this->stepExplanation,
            ];

            $content['question'] = $primaryQuestion['question'];
            $content['options'] = $primaryQuestion['options'];
            $content['explanation_on_error'] = $primaryQuestion['explanation_on_error'];
            $content['randomize'] = $this->quizRandomize;
            $content['questions_to_draw'] = max(1, (int) $this->quizQuestionsToDraw);
            $content['shuffle_options'] = $this->quizShuffleOptions;

            if (! empty($this->quizQuestionPool)) {
                $pool = [$primaryQuestion];
                foreach ($this->quizQuestionPool as $extra) {
                    if (empty(trim((string) ($extra['question'] ?? '')))) {
                        continue;
                    }
                    $correctOpt = $extra['correct_option'] ?? 'A';
                    $pool[] = [
                        'question' => $extra['question'],
                        'options' => [
                            ['id' => 'A', 'text' => $extra['option_a'] ?? '', 'is_correct' => $correctOpt === 'A'],
                            ['id' => 'B', 'text' => $extra['option_b'] ?? '', 'is_correct' => $correctOpt === 'B'],
                            ['id' => 'C', 'text' => $extra['option_c'] ?? '', 'is_correct' => $correctOpt === 'C'],
                        ],
                        'explanation_on_error' => $extra['explanation_on_error'] ?? $this->stepExplanation,
                    ];
                }
                $content['question_pool'] = $pool;
            }
        } elseif (in_array($this->stepType, ['hotspot_simulation', 'guided_simulation'], true)) {
            $instructionText = ! empty(trim($this->stepInstructions)) ? $this->stepInstructions : $this->stepBody;
            $content['instructions'] = $instructionText;
            $content['body'] = $instructionText;
            $content['explanation_on_error'] = $this->stepExplanation;
            if ($this->hotspotX !== null && $this->hotspotY !== null) {
                $content['hotspot'] = [
                    'interaction_type' => $this->hotspotInteractionType,
                    'x' => $this->hotspotX,
                    'y' => $this->hotspotY,
                    'end_x' => $this->hotspotEndX,
                    'end_y' => $this->hotspotEndY,
                    'direction' => $this->hotspotDirection,
                    'radius' => $this->hotspotRadius,
                    'label' => $this->hotspotLabel,
                    'expected_text' => $this->hotspotExpectedText,
                    'case_sensitive' => $this->hotspotCaseSensitive,
                    'trim_whitespace' => $this->hotspotTrimWhitespace,
                    'input_width' => $this->hotspotInputWidth,
                ];
            }

            if (! empty($this->simulationSubSteps)) {
                $subStepsFormatted = [];
                foreach ($this->simulationSubSteps as $sub) {
                    if (empty(trim((string) ($sub['instruction'] ?? '')))) {
                        continue;
                    }
                    $subStepsFormatted[] = [
                        'instruction' => $sub['instruction'],
                        'media_url' => ! empty($sub['media_url']) ? $sub['media_url'] : $this->stepMediaUrl,
                        'hotspot' => [
                            'interaction_type' => $sub['interaction_type'] ?? 'click',
                            'x' => (float) ($sub['x'] ?? 50.0),
                            'y' => (float) ($sub['y'] ?? 50.0),
                            'radius' => (float) ($sub['radius'] ?? 6.0),
                            'expected_text' => (string) ($sub['expected_text'] ?? ''),
                            'label' => (string) ($sub['label'] ?? ''),
                        ],
                    ];
                }
                if (! empty($subStepsFormatted)) {
                    $content['sub_steps'] = $subStepsFormatted;
                }
            }
        } elseif ($this->stepType === 'challenge') {
            $content['scenario'] = ! empty(trim($this->stepBody)) ? $this->stepBody : $this->stepInstructions;
            $content['instructions'] = ! empty(trim($this->stepInstructions)) ? $this->stepInstructions : $this->stepBody;
            $content['body'] = $content['instructions'];
            $content['explanation_on_error'] = $this->stepExplanation;
        } elseif ($this->stepType === 'assignment') {
            $content['instructions'] = ! empty(trim($this->stepInstructions)) ? $this->stepInstructions : $this->stepBody;
            $content['body'] = $content['instructions'];
            $content['allowed_extensions'] = $this->assignmentAllowedExtensions ?: 'xlsx,docx,pdf,png,jpg,zip';
            $content['requires_educator_approval'] = $this->assignmentRequiresEducatorApproval;
            $content['explanation_on_error'] = $this->stepExplanation;
        }

        // Metadados de subpasso e tempo de tela
        $content['is_substep'] = $this->stepIsSubstep;
        $content['min_stay_seconds'] = max(0, (int) $this->stepMinStaySeconds);

        if ($this->editingStepId) {
            $step = LmsLessonStep::find($this->editingStepId);
            if ($step) {
                $oldMediaUrl = $step->content['media_url'] ?? null;
                $newMediaUrl = $content['media_url'] ?? null;
                if ($oldMediaUrl && $oldMediaUrl !== $newMediaUrl && str_contains($oldMediaUrl, 'lms-media')) {
                    $oldPath = 'lms-media/' . basename(parse_url($oldMediaUrl, PHP_URL_PATH));
                    if (Storage::disk('public')->exists($oldPath)) {
                        Storage::disk('public')->delete($oldPath);
                    }
                }

                $step->update([
                    'phase' => $this->stepPhase,
                    'step_type' => $this->stepType,
                    'title' => $this->stepTitle,
                    'content' => $content,
                ]);
            }
        } else {
            $sortOrder = 999;
            if ($this->parentStepIdForSubstep) {
                $parent = LmsLessonStep::find($this->parentStepIdForSubstep);
                if ($parent) {
                    LmsLessonStep::where('lesson_id', $this->selectedLessonId)
                        ->where('phase', $this->stepPhase)
                        ->where('sort_order', '>', $parent->sort_order)
                        ->increment('sort_order');
                    $sortOrder = $parent->sort_order + 1;
                }
            }

            LmsLessonStep::create([
                'lesson_id' => $this->selectedLessonId,
                'phase' => $this->stepPhase,
                'step_type' => $this->stepType,
                'title' => $this->stepTitle,
                'content' => $content,
                'sort_order' => $sortOrder,
            ]);
        }

        $this->resequenceSteps($this->selectedLessonId);

        $this->showStepModal = false;
        $this->editingStepId = null;
        $this->parentStepIdForSubstep = null;

        Notification::make()->title('Passo interativo salvo com sucesso!')->success()->send();
    }

    public function exportSelectedCourse(LmsContentBackupService $backupService): BinaryFileResponse
    {
        $course = Course::find($this->selectedCourseId);
        if (! $course) {
            Notification::make()->title('Selecione um curso para exportar.')->warning()->send();
            throw new \RuntimeException('Nenhum curso selecionado para exportação.');
        }

        $zipPath = $backupService->exportCourseToZip($course);
        $fileName = basename($zipPath);

        return response()->download($zipPath, $fileName, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    public function exportAllCoursesPackage(LmsContentBackupService $backupService): BinaryFileResponse
    {
        $zipPath = $backupService->exportAllToZip();
        $fileName = basename($zipPath);

        return response()->download($zipPath, $fileName, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    public function openImportPackageModal(): void
    {
        $this->packageFile = null;
        $this->showImportPackageModal = true;
    }

    public function importPackage(LmsContentBackupService $backupService): void
    {
        $this->validate([
            'packageFile' => ['required', 'file', 'max:512000'],
        ]);

        try {
            $stats = $backupService->importPackageFile($this->packageFile->getRealPath());

            $this->packageFile = null;
            $this->showImportPackageModal = false;
            $this->syncSelection();

            $mediaMsg = ($stats['media_files'] ?? 0) > 0
                ? " e {$stats['media_files']} arquivos de mídia restaurados"
                : '';

            Notification::make()
                ->title('Pacote LMS importado com sucesso!')
                ->body("Processado: {$stats['courses']} cursos, {$stats['modules']} módulos, {$stats['lessons']} aulas e {$stats['steps']} passos interativos{$mediaMsg}.")
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Erro ao importar pacote')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int, LmsLessonStep>|\Illuminate\Database\Eloquent\Collection<int, LmsLessonStep>  $steps
     * @return array<int, string>
     */
    public function getPhaseStepNumbers($steps): array
    {
        $numbers = [];
        $mainIndex = 0;
        $subIndex = 0;
        $lastMainTitle = null;

        foreach ($steps as $step) {
            $isSub = ! empty($step->content['is_substep']);
            $currentTitle = trim((string) $step->title);
            $isSameTitle = ($lastMainTitle !== null && strcasecmp($currentTitle, trim((string) $lastMainTitle)) === 0 && $mainIndex > 0);

            if ($isSub || $isSameTitle) {
                $subIndex++;
                $numbers[$step->id] = "{$mainIndex}.{$subIndex}";
            } else {
                $mainIndex++;
                $subIndex = 0;
                $lastMainTitle = $currentTitle;
                $numbers[$step->id] = (string) $mainIndex;
            }
        }

        return $numbers;
    }

    public function deleteStep(int $id): void
    {
        $step = LmsLessonStep::find($id);
        if ($step) {
            $lessonId = $step->lesson_id;
            $step->delete();
            $this->resequenceSteps($lessonId);
            Notification::make()->title('Passo removido')->success()->send();
        }
    }

    protected function resequenceSteps(int $lessonId): void
    {
        $steps = LmsLessonStep::where('lesson_id', $lessonId)
            ->orderByRaw("CASE phase 
                WHEN 'praelectio' THEN 1 
                WHEN 'practice' THEN 2 
                WHEN 'repetitio' THEN 3 
                WHEN 'concertatio' THEN 4 
                WHEN 'examen' THEN 5 
                ELSE 6 END")
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        foreach ($steps as $index => $step) {
            $step->update(['sort_order' => $index + 1]);
        }
    }

    public function moveModuleUp(int $id): void
    {
        $module = LmsModule::find($id);
        if (! $module) {
            return;
        }

        $siblings = LmsModule::where('course_id', $module->course_id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->values();

        $index = $siblings->search(fn (LmsModule $m): bool => $m->id === $module->id);
        if ($index !== false && $index > 0) {
            $prev = $siblings[$index - 1];
            $siblings[$index - 1] = $module;
            $siblings[$index] = $prev;

            foreach ($siblings as $idx => $item) {
                $item->update(['sort_order' => $idx + 1]);
            }
        }
    }

    public function moveModuleDown(int $id): void
    {
        $module = LmsModule::find($id);
        if (! $module) {
            return;
        }

        $siblings = LmsModule::where('course_id', $module->course_id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->values();

        $index = $siblings->search(fn (LmsModule $m): bool => $m->id === $module->id);
        if ($index !== false && $index < $siblings->count() - 1) {
            $next = $siblings[$index + 1];
            $siblings[$index + 1] = $module;
            $siblings[$index] = $next;

            foreach ($siblings as $idx => $item) {
                $item->update(['sort_order' => $idx + 1]);
            }
        }
    }

    public function moveLessonUp(int $id): void
    {
        $lesson = LmsLesson::find($id);
        if (! $lesson) {
            return;
        }

        $siblings = LmsLesson::where('module_id', $lesson->module_id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->values();

        $index = $siblings->search(fn (LmsLesson $l): bool => $l->id === $lesson->id);
        if ($index !== false && $index > 0) {
            $prev = $siblings[$index - 1];
            $siblings[$index - 1] = $lesson;
            $siblings[$index] = $prev;

            foreach ($siblings as $idx => $item) {
                $item->update(['sort_order' => $idx + 1]);
            }
        }
    }

    public function moveLessonDown(int $id): void
    {
        $lesson = LmsLesson::find($id);
        if (! $lesson) {
            return;
        }

        $siblings = LmsLesson::where('module_id', $lesson->module_id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->values();

        $index = $siblings->search(fn (LmsLesson $l): bool => $l->id === $lesson->id);
        if ($index !== false && $index < $siblings->count() - 1) {
            $next = $siblings[$index + 1];
            $siblings[$index + 1] = $lesson;
            $siblings[$index] = $next;

            foreach ($siblings as $idx => $item) {
                $item->update(['sort_order' => $idx + 1]);
            }
        }
    }

    public function duplicateLesson(int $id): void
    {
        $lesson = LmsLesson::with('steps')->find($id);
        if (! $lesson) {
            return;
        }

        $maxOrder = LmsLesson::where('module_id', $lesson->module_id)->max('sort_order') ?? 0;

        $copy = LmsLesson::create([
            'module_id' => $lesson->module_id,
            'title' => $lesson->title.' (Cópia)',
            'description' => $lesson->description,
            'estimated_duration_minutes' => $lesson->estimated_duration_minutes,
            'passing_score' => $lesson->passing_score,
            'sort_order' => $maxOrder + 1,
            'is_active' => $lesson->is_active,
        ]);

        foreach ($lesson->steps as $step) {
            LmsLessonStep::create([
                'lesson_id' => $copy->id,
                'phase' => $step->phase,
                'step_type' => $step->step_type,
                'title' => $step->title,
                'content' => $step->content,
                'sort_order' => $step->sort_order,
                'is_required' => $step->is_required ?? true,
            ]);
        }

        $this->selectedLessonId = $copy->id;

        Notification::make()
            ->title('Aula duplicada com sucesso!')
            ->body("A aula '{$copy->title}' foi criada com todos os seus passos.")
            ->success()
            ->send();
    }

    public function moveStepUp(int $id): void
    {
        $step = LmsLessonStep::find($id);
        if (! $step) {
            return;
        }

        $siblings = LmsLessonStep::where('lesson_id', $step->lesson_id)
            ->where('phase', $step->phase)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->values();

        $index = $siblings->search(fn (LmsLessonStep $s): bool => $s->id === $step->id);
        if ($index !== false && $index > 0) {
            $prev = $siblings[$index - 1];
            $tempOrder = $prev->sort_order;
            $prev->update(['sort_order' => $step->sort_order]);
            $step->update(['sort_order' => $tempOrder]);
            $this->resequenceSteps($step->lesson_id);
        }
    }

    public function moveStepDown(int $id): void
    {
        $step = LmsLessonStep::find($id);
        if (! $step) {
            return;
        }

        $siblings = LmsLessonStep::where('lesson_id', $step->lesson_id)
            ->where('phase', $step->phase)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->values();

        $index = $siblings->search(fn (LmsLessonStep $s): bool => $s->id === $step->id);
        if ($index !== false && $index < $siblings->count() - 1) {
            $next = $siblings[$index + 1];
            $tempOrder = $next->sort_order;
            $next->update(['sort_order' => $step->sort_order]);
            $step->update(['sort_order' => $tempOrder]);
            $this->resequenceSteps($step->lesson_id);
        }
    }

    public function duplicateStep(int $id): void
    {
        $step = LmsLessonStep::find($id);
        if (! $step) {
            return;
        }

        LmsLessonStep::create([
            'lesson_id' => $step->lesson_id,
            'phase' => $step->phase,
            'step_type' => $step->step_type,
            'title' => $step->title.' (Cópia)',
            'content' => $step->content,
            'sort_order' => $step->sort_order + 1,
            'is_required' => $step->is_required ?? true,
        ]);

        $this->resequenceSteps($step->lesson_id);

        Notification::make()
            ->title('Passo duplicado!')
            ->success()
            ->send();
    }

    public function openRatingsModal(): void
    {
        $this->resetPage('ratingsPage');
        $this->showRatingsModal = true;
    }

    public function setRatingsFilter(string $filter): void
    {
        $this->ratingsFilter = in_array($filter, ['all', 'alerts', 'positive'], true) ? $filter : 'all';
        $this->resetPage('ratingsPage');
    }

    public function updatedRatingsSearch(): void
    {
        $this->resetPage('ratingsPage');
    }

    /**
     * Estatísticas consolidadas das avaliações do curso selecionado
     *
     * @return array{avg: float, total: int, alerts: int, positive: int}
     */
    public function getRatingsStatsProperty(): array
    {
        if (! $this->selectedCourseId) {
            return ['avg' => 0.0, 'total' => 0, 'alerts' => 0, 'positive' => 0];
        }

        $base = \App\Models\LmsStudentLessonProgress::query()
            ->whereNotNull('student_rating')
            ->whereHas('lesson.module', fn ($q) => $q->where('course_id', $this->selectedCourseId));

        $total = (clone $base)->count();
        $avg = $total > 0 ? round((float) (clone $base)->avg('student_rating'), 1) : 0.0;
        $alerts = (clone $base)->where('student_rating', '<=', 3)->count();
        $positive = (clone $base)->where('student_rating', '>=', 4)->count();

        return [
            'avg' => $avg,
            'total' => $total,
            'alerts' => $alerts,
            'positive' => $positive,
        ];
    }

    /**
     * Mapa [lesson_id => ['avg' => float, 'count' => int]] para exibir estrelas nas aulas do curso selecionado
     *
     * @return array<int, array{avg: float, count: int}>
     */
    public function getLessonRatingsMapProperty(): array
    {
        if (! $this->selectedCourseId) {
            return [];
        }

        $rows = \App\Models\LmsStudentLessonProgress::query()
            ->whereNotNull('student_rating')
            ->whereHas('lesson.module', fn ($q) => $q->where('course_id', $this->selectedCourseId))
            ->selectRaw('lesson_id, AVG(student_rating) as avg_rating, COUNT(*) as total_ratings')
            ->groupBy('lesson_id')
            ->get();

        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r->lesson_id] = [
                'avg' => round((float) $r->avg_rating, 1),
                'count' => (int) $r->total_ratings,
            ];
        }

        return $map;
    }

    /**
     * Lista detalhada de avaliações e comentários dos alunos com paginação, busca e filtros
     */
    public function getCourseFeedbacksProperty(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        if (! $this->selectedCourseId) {
            return \App\Models\LmsStudentLessonProgress::query()->whereRaw('1=0')->paginate($this->ratingsPerPage, ['*'], 'ratingsPage');
        }

        return \App\Models\LmsStudentLessonProgress::query()
            ->with(['lesson.module', 'contract.student'])
            ->whereNotNull('student_rating')
            ->whereHas('lesson.module', fn ($q) => $q->where('course_id', $this->selectedCourseId))
            ->when($this->ratingsFilter === 'alerts', fn ($q) => $q->where('student_rating', '<=', 3))
            ->when($this->ratingsFilter === 'positive', fn ($q) => $q->where('student_rating', '>=', 4))
            ->when(filled($this->ratingsSearch), function ($q): void {
                $term = '%' . trim($this->ratingsSearch) . '%';
                $likeOp = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
                $q->where(function ($sub) use ($term, $likeOp): void {
                    $sub->where('student_rating_comment', $likeOp, $term)
                        ->orWhereHas('lesson', fn ($l) => $l->where('title', $likeOp, $term))
                        ->orWhereHas('contract.student', fn ($s) => $s->where('name', $likeOp, $term));
                });
            })
            ->orderByDesc('student_rated_at')
            ->orderByDesc('updated_at')
            ->paginate($this->ratingsPerPage, ['*'], 'ratingsPage');
    }

    public function openAiModal(): void
    {
        if (! $this->selectedModuleId) {
            Notification::make()->title('Selecione ou crie um módulo primeiro')->warning()->send();

            return;
        }

        $this->aiTopic = '';
        $this->aiLevel = 'Iniciante';
        $this->showAiModal = true;
    }

    /**
     * Gera uma aula completa de 90min com os 5 blocos do Método Jesuítico
     */
    public function generateWithAi(): void
    {
        $this->validate([
            'aiTopic' => ['required', 'string', 'min:4', 'max:255'],
        ], [
            'aiTopic.required' => 'Informe o tema ou título da aula a ser gerada.',
        ]);

        $maxOrder = LmsLesson::where('module_id', $this->selectedModuleId)->max('sort_order') ?? 0;

        $lesson = LmsLesson::create([
            'module_id' => $this->selectedModuleId,
            'title' => $this->aiTopic,
            'description' => "Aula completa de 90 minutos gerada com base na metodologia jesuítica sobre {$this->aiTopic}.",
            'estimated_duration_minutes' => 90,
            'passing_score' => 80.00,
            'sort_order' => $maxOrder + 1,
            'is_active' => true,
        ]);

        // Bloco 1: Praelectio (Contexto & Apresentação)
        LmsLessonStep::create([
            'lesson_id' => $lesson->id,
            'phase' => 'praelectio',
            'step_type' => 'theory',
            'title' => '1. Conceituação e Importância Prática',
            'content' => [
                'body' => "Bem-vindo ao estudo de {$this->aiTopic}. Nesta etapa, compreenda o propósito central: por que esta ferramenta existe no mercado de trabalho e qual problema real ela resolve para a empresa.",
            ],
            'sort_order' => 1,
        ]);

        // Bloco 2: Experientia (Prática Guiada no Simulador)
        LmsLessonStep::create([
            'lesson_id' => $lesson->id,
            'phase' => 'practice',
            'step_type' => 'hotspot_simulation',
            'title' => '2. Prática Guiada: Execução dos Comandos',
            'content' => [
                'instructions' => "Identifique os elementos na tela e acione a ferramenta principal para aplicar **{$this->aiTopic}** com precisão.",
                'body' => "Identifique os elementos na tela e acione a ferramenta principal para aplicar **{$this->aiTopic}** com precisão.",
                'explanation_on_error' => 'Atenção aos detalhes da interface. Clique exatamente na ferramenta indicada pelo roteiro da aula.',
            ],
            'sort_order' => 2,
        ]);

        // Bloco 3: Repetitio (Fixação e Pergunta de Causa/Efeito)
        LmsLessonStep::create([
            'lesson_id' => $lesson->id,
            'phase' => 'repetitio',
            'step_type' => 'quiz',
            'title' => '3. Fixação: Por que funciona desta maneira?',
            'content' => [
                'question' => "Ao aplicar {$this->aiTopic}, qual é o resultado imediato esperado no sistema?",
                'options' => [
                    ['id' => 'A', 'text' => 'Gera o cálculo automatizado e padroniza os dados corretamente.', 'is_correct' => true],
                    ['id' => 'B', 'text' => 'Apenas altera a cor visual sem impacto nas fórmulas.', 'is_correct' => false],
                    ['id' => 'C', 'text' => 'Apaga os registros anteriores permanentemente.', 'is_correct' => false],
                ],
                'explanation_on_error' => 'Lembre-se: o objetivo central é automatizar e garantir a consistência das informações.',
            ],
            'sort_order' => 3,
        ]);

        // Bloco 4: Concertatio (Desafio Autônomo sem Ajuda)
        LmsLessonStep::create([
            'lesson_id' => $lesson->id,
            'phase' => 'concertatio',
            'step_type' => 'challenge',
            'title' => '4. Desafio Prático Autônomo (Sem Auxílio)',
            'content' => [
                'scenario' => 'Você foi contratado por uma empresa e precisa resolver uma planilha com urgência.',
                'instructions' => "Aplique agora tudo o que foi praticado em {$this->aiTopic} do zero, demonstrando autonomia completa.",
                'explanation_on_error' => 'Se você hesitou, revise a Prática Guiada antes de submeter a resolução final.',
            ],
            'sort_order' => 4,
        ]);

        // Bloco 5: Examen (Avaliação Somativa de Maestria)
        LmsLessonStep::create([
            'lesson_id' => $lesson->id,
            'phase' => 'examen',
            'step_type' => 'quiz',
            'title' => '5. Avaliação Final de Maestria (80% Mínimo)',
            'content' => [
                'question' => 'Para consolidar sua aprovação nesta aula, confirme o critério de boas práticas aplicável:',
                'options' => [
                    ['id' => 'A', 'text' => 'Validar os parâmetros, conferir os intervalos e documentar as alterações.', 'is_correct' => true],
                    ['id' => 'B', 'text' => 'Ignorar a formatação e salvar sem revisar.', 'is_correct' => false],
                    ['id' => 'C', 'text' => 'Compartilhar arquivos sem testar previamente.', 'is_correct' => false],
                ],
                'explanation_on_error' => 'A excelência profissional exige conferência prévia e rigor metodológico.',
            ],
            'sort_order' => 5,
        ]);

        $this->selectedLessonId = $lesson->id;
        $this->showAiModal = false;

        Notification::make()
            ->title('✨ Aula de 90 minutos gerada com sucesso!')
            ->body('Os 5 blocos do Método Jesuítico foram criados com rigor pedagógico e já estão disponíveis no estúdio.')
            ->success()
            ->send();
    }
}


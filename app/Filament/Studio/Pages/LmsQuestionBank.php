<?php

declare(strict_types=1);

namespace App\Filament\Studio\Pages;

use App\Models\Course;
use App\Models\LmsLesson;
use App\Models\LmsLessonStep;
use App\Models\LmsModule;
use App\Models\User;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class LmsQuestionBank extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static ?string $navigationLabel = 'Banco de Questões (LMS)';

    protected static ?string $title = 'Banco de Questões & Anti-Cola (LMS)';

    protected static string|UnitEnum|null $navigationGroup = 'Pedagógico';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.lms-question-bank';

    public ?int $selectedCourseId = null;

    public ?int $selectedModuleId = null;

    public ?int $selectedLessonId = null;

    public ?int $selectedQuizStepId = null;

    // Configurações Anti-Cola do Quiz
    public string $quizTitle = '5. Examen: Avaliação de Domínio';

    public bool $randomizeQuestions = true;

    public bool $shuffleOptions = true;

    public int $questionsToDraw = 3;

    /**
     * Lista unificada de questões do banco desta aula
     *
     * @var array<int, array{id: string, question: string, option_a: string, option_b: string, option_c: string, correct_option: string, explanation_on_error: string}>
     */
    public array $questions = [];

    // Formulário de Nova Questão / Edição Rápida
    public ?int $editingIndex = null;

    public string $formQuestion = '';

    public string $formOptionA = '';

    public string $formOptionB = '';

    public string $formOptionC = '';

    public string $formCorrectOption = 'A';

    public string $formExplanation = '';

    public bool $showQuestionModal = false;

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && (
            $user->can('View:LmsStudio')
            || $user->can('page_LmsStudio')
            || $user->can('view_lms_studio')
            || $user->can('View:LmsLesson')
        );
    }

    public function mount(): void
    {
        $requestedCourseId = request()->query('course_id');
        $requestedLessonId = request()->query('lesson_id');

        if ($requestedLessonId) {
            $lesson = LmsLesson::with('module')->find((int) $requestedLessonId);
            if ($lesson) {
                $this->selectedLessonId = $lesson->id;
                $this->selectedModuleId = $lesson->module_id;
                $this->selectedCourseId = $lesson->module?->course_id;
            }
        } elseif ($requestedCourseId) {
            $this->selectedCourseId = (int) $requestedCourseId;
        } else {
            $firstCourse = Course::query()->whereNull('ouro_moderno_id')->orderBy('name')->first();
            $this->selectedCourseId = $firstCourse?->id;
        }

        $this->syncSelection();
        $this->loadQuestionsFromLesson();
    }

    public function updatedSelectedCourseId(): void
    {
        $this->selectedModuleId = null;
        $this->selectedLessonId = null;
        $this->syncSelection();
        $this->loadQuestionsFromLesson();
    }

    public function updatedSelectedModuleId(): void
    {
        $this->selectedLessonId = null;
        $this->syncSelection();
        $this->loadQuestionsFromLesson();
    }

    public function updatedSelectedLessonId(): void
    {
        $this->loadQuestionsFromLesson();
    }

    public function selectLesson(int $lessonId): void
    {
        $lesson = LmsLesson::find($lessonId);
        if (! $lesson) {
            return;
        }

        $this->selectedModuleId = $lesson->module_id;
        $this->selectedLessonId = $lesson->id;
        $this->loadQuestionsFromLesson();
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

    public function loadQuestionsFromLesson(): void
    {
        $this->questions = [];
        $this->selectedQuizStepId = null;

        if (! $this->selectedLessonId) {
            return;
        }

        $quizStep = LmsLessonStep::query()
            ->where('lesson_id', $this->selectedLessonId)
            ->where('step_type', 'quiz')
            ->orderByDesc('sort_order')
            ->first();

        if (! $quizStep) {
            $this->quizTitle = '5. Examen: Avaliação com Sorteio do Banco';
            $this->randomizeQuestions = true;
            $this->shuffleOptions = true;
            $this->questionsToDraw = 3;

            return;
        }

        $this->selectedQuizStepId = $quizStep->id;
        $this->quizTitle = $quizStep->title;
        $content = $quizStep->content ?? [];

        $this->randomizeQuestions = (bool) ($content['randomize'] ?? $content['randomize_pool'] ?? true);
        $this->shuffleOptions = (bool) ($content['shuffle_options'] ?? true);

        $rawPool = $content['question_pool'] ?? [];
        $parsedQuestions = [];

        if (! empty($rawPool) && is_array($rawPool)) {
            foreach ($rawPool as $idx => $item) {
                $opts = $item['options'] ?? [];
                $optA = '';
                $optB = '';
                $optC = '';
                $correct = 'A';

                foreach ($opts as $opt) {
                    $id = strtoupper((string) ($opt['id'] ?? ''));
                    if ($id === 'A') {
                        $optA = (string) ($opt['text'] ?? '');
                    } elseif ($id === 'B') {
                        $optB = (string) ($opt['text'] ?? '');
                    } elseif ($id === 'C') {
                        $optC = (string) ($opt['text'] ?? '');
                    }
                    if (! empty($opt['is_correct'])) {
                        $correct = $id ?: 'A';
                    }
                }

                $parsedQuestions[] = [
                    'id' => (string) ($item['id'] ?? ('q_' . ($idx + 1))),
                    'question' => (string) ($item['question'] ?? ''),
                    'option_a' => $optA,
                    'option_b' => $optB,
                    'option_c' => $optC,
                    'correct_option' => $correct,
                    'explanation_on_error' => (string) ($item['explanation_on_error'] ?? ''),
                ];
            }
        } elseif (! empty($content['question'])) {
            $opts = $content['options'] ?? [];
            $optA = '';
            $optB = '';
            $optC = '';
            $correct = 'A';

            foreach ($opts as $opt) {
                $id = strtoupper((string) ($opt['id'] ?? ''));
                if ($id === 'A') {
                    $optA = (string) ($opt['text'] ?? '');
                } elseif ($id === 'B') {
                    $optB = (string) ($opt['text'] ?? '');
                } elseif ($id === 'C') {
                    $optC = (string) ($opt['text'] ?? '');
                }
                if (! empty($opt['is_correct'])) {
                    $correct = $id ?: 'A';
                }
            }

            $parsedQuestions[] = [
                'id' => 'q_1',
                'question' => (string) $content['question'],
                'option_a' => $optA,
                'option_b' => $optB,
                'option_c' => $optC,
                'correct_option' => $correct,
                'explanation_on_error' => (string) ($content['explanation_on_error'] ?? ''),
            ];
        }

        $this->questions = $parsedQuestions;
        $this->questionsToDraw = (int) ($content['questions_to_draw'] ?? max(1, min(5, count($this->questions))));
    }

    public function openCreateQuestionModal(): void
    {
        if (! $this->selectedLessonId) {
            Notification::make()->title('Selecione uma aula primeiro')->warning()->send();

            return;
        }

        $this->editingIndex = null;
        $this->formQuestion = '';
        $this->formOptionA = '';
        $this->formOptionB = '';
        $this->formOptionC = '';
        $this->formCorrectOption = 'A';
        $this->formExplanation = '';
        $this->showQuestionModal = true;
    }

    public function openEditQuestionModal(int $index): void
    {
        if (! isset($this->questions[$index])) {
            return;
        }

        $q = $this->questions[$index];
        $this->editingIndex = $index;
        $this->formQuestion = $q['question'];
        $this->formOptionA = $q['option_a'];
        $this->formOptionB = $q['option_b'];
        $this->formOptionC = $q['option_c'];
        $this->formCorrectOption = $q['correct_option'] ?: 'A';
        $this->formExplanation = $q['explanation_on_error'] ?? '';
        $this->showQuestionModal = true;
    }

    public function saveQuestionFromModal(): void
    {
        $this->validate([
            'formQuestion' => ['required', 'string', 'min:5'],
            'formOptionA' => ['required', 'string', 'min:1'],
            'formOptionB' => ['required', 'string', 'min:1'],
            'formOptionC' => ['required', 'string', 'min:1'],
            'formCorrectOption' => ['required', 'in:A,B,C'],
        ], [
            'formQuestion.required' => 'Digite o enunciado da questão.',
            'formOptionA.required' => 'Preencha a Alternativa A.',
            'formOptionB.required' => 'Preencha a Alternativa B.',
            'formOptionC.required' => 'Preencha a Alternativa C.',
        ]);

        $entry = [
            'id' => 'q_' . (count($this->questions) + 1) . '_' . substr(md5((string) microtime(true)), 0, 4),
            'question' => trim($this->formQuestion),
            'option_a' => trim($this->formOptionA),
            'option_b' => trim($this->formOptionB),
            'option_c' => trim($this->formOptionC),
            'correct_option' => $this->formCorrectOption,
            'explanation_on_error' => trim($this->formExplanation),
        ];

        if ($this->editingIndex !== null && isset($this->questions[$this->editingIndex])) {
            $entry['id'] = $this->questions[$this->editingIndex]['id'];
            $this->questions[$this->editingIndex] = $entry;
        } else {
            $this->questions[] = $entry;
        }

        $this->showQuestionModal = false;
        $this->persistQuestionBank();
    }

    public function duplicateQuestion(int $index): void
    {
        if (! isset($this->questions[$index])) {
            return;
        }

        $copy = $this->questions[$index];
        $copy['id'] = 'q_' . (count($this->questions) + 1) . '_' . substr(md5((string) microtime(true)), 0, 4);
        $copy['question'] .= ' (Variação)';
        $this->questions[] = $copy;

        $this->persistQuestionBank();
    }

    public function removeQuestion(int $index): void
    {
        if (! isset($this->questions[$index])) {
            return;
        }

        unset($this->questions[$index]);
        $this->questions = array_values($this->questions);
        $this->persistQuestionBank();
    }

    public function generateStarterQuestionPack(): void
    {
        $lesson = $this->selectedLesson;
        if (! $lesson) {
            Notification::make()->title('Selecione uma aula para gerar questões')->warning()->send();

            return;
        }

        $topic = $lesson->title;
        $courseName = $this->selectedCourse?->name ?? 'Informática';

        $pack = [
            [
                'id' => 'q_gen_1_' . rand(100, 999),
                'question' => "Sobre o tema \"{$topic}\" no curso de {$courseName}, qual é a melhor prática recomendada?",
                'option_a' => 'Seguir o fluxo estruturado apresentado na aula e validar o resultado a cada etapa.',
                'option_b' => 'Ignorar as configurações iniciais e executar comandos aleatórios sem revisão.',
                'option_c' => 'Fechar o aplicativo sem salvar o arquivo de trabalho.',
                'correct_option' => 'A',
                'explanation_on_error' => 'A execução estruturada e a revisão garantem precisão técnica no procedimento.',
            ],
            [
                'id' => 'q_gen_2_' . rand(100, 999),
                'question' => "Ao realizar a prática de \"{$topic}\", qual atalho ou recurso otimiza a produtividade do profissional?",
                'option_a' => 'Refazer todo o documento manualmente do zero a cada alteração.',
                'option_b' => 'Utilizar as ferramentas nativas da barra de comandos e atalhos padrão ensinados na aula.',
                'option_c' => 'Desativar a verificação e o salvamento automático.',
                'correct_option' => 'B',
                'explanation_on_error' => 'O uso dos recursos nativos e atalhos de teclado reduz erros e acelera o trabalho.',
            ],
            [
                'id' => 'q_gen_3_' . rand(100, 999),
                'question' => "Qual é o objetivo principal do método aplicado em \"{$topic}\"?",
                'option_a' => 'Apenas decorar nomes de menus sem aplicação prática.',
                'option_b' => 'Restringir o uso de recursos visuais do sistema.',
                'option_c' => 'Dominar a execução prática com autonomia para resolver problemas reais do dia a dia.',
                'correct_option' => 'C',
                'explanation_on_error' => 'O foco do método é o domínio prático e a resolução autônoma de cenários reais.',
            ],
            [
                'id' => 'q_gen_4_' . rand(100, 999),
                'question' => "Se ocorrer um comportamento inesperado durante o exercício de \"{$topic}\", qual a ação correta?",
                'option_a' => 'Analisar o parâmetro configurado, usar o recurso Desfazer (Ctrl+Z) se necessário e ajustar a propriedade correta.',
                'option_b' => 'Desligar o computador imediatamente.',
                'option_c' => 'Apagar todas as pastas do sistema operacional.',
                'correct_option' => 'A',
                'explanation_on_error' => 'O diagnóstico calmo e o uso do Ctrl+Z permitem corrigir qualquer etapa com segurança.',
            ],
            [
                'id' => 'q_gen_5_' . rand(100, 999),
                'question' => "Antes de finalizar e entregar a atividade de \"{$topic}\", o que deve ser conferido?",
                'option_a' => 'Se os dados, formatação e requisitos solicitados no desafio estão completos e salvos.',
                'option_b' => 'Apenas a cor do papel de parede da área de trabalho.',
                'option_c' => 'Nada, pois arquivos temporários não precisam ser salvos.',
                'correct_option' => 'A',
                'explanation_on_error' => 'Sempre revise os requisitos propostos e salve o arquivo antes de concluir a etapa.',
            ],
        ];

        foreach ($pack as $q) {
            $this->questions[] = $q;
        }

        $this->questionsToDraw = min(count($this->questions), max(3, $this->questionsToDraw));
        $this->persistQuestionBank();

        Notification::make()
            ->title('✨ 5 Questões Adicionadas ao Banco!')
            ->body("O banco de questões da aula \"{$topic}\" agora possui " . count($this->questions) . ' questões prontas para sorteio anti-cola.')
            ->success()
            ->send();
    }

    public function persistQuestionBank(): void
    {
        if (! $this->selectedLessonId) {
            return;
        }

        if ($this->questions === []) {
            Notification::make()
                ->title('Adicione pelo menos 1 questão ao banco antes de salvar.')
                ->warning()
                ->send();

            return;
        }

        $first = $this->questions[0];
        $formattedPool = [];

        foreach ($this->questions as $idx => $q) {
            $correct = strtoupper((string) ($q['correct_option'] ?? 'A'));
            $formattedPool[] = [
                'id' => $q['id'] ?? ('q_' . ($idx + 1)),
                'question' => $q['question'],
                'options' => [
                    ['id' => 'A', 'text' => $q['option_a'], 'is_correct' => $correct === 'A'],
                    ['id' => 'B', 'text' => $q['option_b'], 'is_correct' => $correct === 'B'],
                    ['id' => 'C', 'text' => $q['option_c'], 'is_correct' => $correct === 'C'],
                ],
                'explanation_on_error' => $q['explanation_on_error'] ?? '',
            ];
        }

        $this->questionsToDraw = max(1, min(count($formattedPool), $this->questionsToDraw));

        $firstCorrect = strtoupper((string) ($first['correct_option'] ?? 'A'));
        $contentPayload = [
            'question' => $first['question'],
            'options' => [
                ['id' => 'A', 'text' => $first['option_a'], 'is_correct' => $firstCorrect === 'A'],
                ['id' => 'B', 'text' => $first['option_b'], 'is_correct' => $firstCorrect === 'B'],
                ['id' => 'C', 'text' => $first['option_c'], 'is_correct' => $firstCorrect === 'C'],
            ],
            'explanation_on_error' => $first['explanation_on_error'] ?? '',
            'randomize' => $this->randomizeQuestions,
            'randomize_pool' => $this->randomizeQuestions,
            'shuffle_options' => $this->shuffleOptions,
            'questions_to_draw' => $this->questionsToDraw,
            'question_pool' => $formattedPool,
        ];

        $step = $this->selectedQuizStepId ? LmsLessonStep::find($this->selectedQuizStepId) : null;

        if ($step) {
            $oldContent = $step->content ?? [];
            $step->update([
                'title' => $this->quizTitle ?: $step->title,
                'content' => array_merge($oldContent, $contentPayload),
            ]);
        } else {
            $maxOrder = LmsLessonStep::where('lesson_id', $this->selectedLessonId)->max('sort_order') ?? 0;
            $step = LmsLessonStep::create([
                'lesson_id' => $this->selectedLessonId,
                'phase' => 'examen',
                'step_type' => 'quiz',
                'title' => $this->quizTitle ?: '5. Examen: Avaliação com Sorteio do Banco',
                'content' => $contentPayload,
                'sort_order' => $maxOrder + 1,
            ]);
            $this->selectedQuizStepId = $step->id;
        }

        Notification::make()
            ->title('Banco de Questões salvo com sucesso!')
            ->body(count($formattedPool) . " questões configuradas (sorteando {$this->questionsToDraw} por tentativa).")
            ->success()
            ->send();
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
        return $this->selectedCourseId
            ? Course::with(['lmsModules.lessons.steps'])->find($this->selectedCourseId)
            : null;
    }

    public function getSelectedLessonProperty(): ?LmsLesson
    {
        return $this->selectedLessonId
            ? LmsLesson::with(['module', 'steps'])->find($this->selectedLessonId)
            : null;
    }

    /**
     * Estatísticas rápidas do Banco de Questões do curso selecionado
     *
     * @return array{total_questions: int, active_pools: int, total_lessons: int}
     */
    public function getCourseStatsProperty(): array
    {
        $course = $this->selectedCourse;
        if (! $course) {
            return ['total_questions' => 0, 'active_pools' => 0, 'total_lessons' => 0];
        }

        $totalQuestions = 0;
        $activePools = 0;
        $totalLessons = 0;

        foreach ($course->lmsModules as $module) {
            foreach ($module->lessons as $lesson) {
                $totalLessons++;
                foreach ($lesson->steps as $step) {
                    if ($step->step_type === 'quiz') {
                        $pool = $step->content['question_pool'] ?? [];
                        $count = is_array($pool) && count($pool) > 0 ? count($pool) : (! empty($step->content['question']) ? 1 : 0);
                        $totalQuestions += $count;
                        if ($count > 1) {
                            $activePools++;
                        }
                    }
                }
            }
        }

        return [
            'total_questions' => $totalQuestions,
            'active_pools' => $activePools,
            'total_lessons' => $totalLessons,
        ];
    }
}

<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class UserGuide extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Ajuda e suporte';

    protected static ?string $navigationLabel = 'Manual e Tutoriais';

    protected static ?string $title = 'Manual do Sistema & Tutoriais';

    protected static ?string $slug = 'user-guide';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.user-guide';

    public string $activeCategory = 'all';

    public string $search = '';

    public static function canAccess(): bool
    {
        return Auth::check();
    }

    /**
     * Documentation sections configuration
     */
    public function getSectionsProperty(): array
    {
        return [
            'ciclo' => [
                'categories' => ['all', 'contratos', 'comercial'],
                'terms' => ['ciclo', 'aluno', 'jornada', 'etapas', 'fluxo', 'matricula', 'visao', 'geral'],
            ],
            'comercial' => [
                'categories' => ['all', 'comercial'],
                'terms' => ['comercial', 'crm', 'vendas', 'leads', 'interessados', 'kanban', 'comissao', 'comissoes', 'visita', 'visitas'],
            ],
            'contratos' => [
                'categories' => ['all', 'contratos'],
                'terms' => ['contrato', 'contratos', 'matricula', 'matriculas', 'aluno', 'responsavel', 'grade', 'horario', 'sala', 'carne', 'parcela'],
            ],
            'pedagogico' => [
                'categories' => ['all', 'pedagogico'],
                'terms' => ['pedagogico', 'chamada', 'presenca', 'falta', 'agenda', 'frequencia', 'evasao', 'risco', 'acompanhamento', 'ponto'],
            ],
            'kiosk' => [
                'categories' => ['all', 'kiosk'],
                'terms' => ['totem', 'kiosk', 'autoatendimento', 'ponto', 'cracha', 'nascimento', 'aviso', 'avisos', 'nps', 'satisfacao', 'pesquisa', 'contrato'],
            ],
            'financeiro' => [
                'categories' => ['all', 'financeiro'],
                'terms' => ['financeiro', 'pix', 'carne', 'carnes', 'parcela', 'parcelas', 'baixa', 'caixa', 'dre', 'recibo', 'pagamento', 'inadimplencia'],
            ],
            'gestao' => [
                'categories' => ['all', 'gestao'],
                'terms' => ['meta', 'metas', 'gestao', 'auditoria', 'agenda', 'compromissos', 'reuniao', 'tarefas', 'trello', 'cancelamento', 'fraude', 'assiduidade'],
            ],
            'lms' => [
                'categories' => ['all', 'lms', 'pedagogico'],
                'terms' => ['lms', 'ouro', 'moderno', 'cursos', 'interativo', 'aulas', 'computador', 'sincronizacao', 'notas', 'estudio', 'jesuitico', 'simulador', 'hotspot', 'selecionar', 'digitar', 'pacing', 'liberar', 'monitor', 'laboratorio', 'certificado'],
            ],
            'seguranca' => [
                'categories' => ['all', 'seguranca'],
                'terms' => ['seguranca', 'backup', 'backups', 'restaurar', 'restauracao', 'permissao', 'permissoes', 'papel', 'shield', 'senha', 'zero-trust'],
            ],
            'faq' => [
                'categories' => ['all', 'faq'],
                'terms' => ['faq', 'duvidas', 'pergunta', 'perguntas', 'como', 'ajuda', 'suporte', 'trocar senha', 'cracha', 'esquecer'],
            ],
        ];
    }

    /**
     * Check if section is visible based on active category and search
     */
    public function isSectionVisible(string $sectionKey): bool
    {
        $sections = $this->sections;
        if (! isset($sections[$sectionKey])) {
            return false;
        }

        $config = $sections[$sectionKey];

        if (! in_array($this->activeCategory, $config['categories'], true)) {
            return false;
        }

        return $this->matchesSearch(...$config['terms']);
    }

    /**
     * Count of visible sections
     */
    public function getVisibleSectionsCountProperty(): int
    {
        $count = 0;
        foreach (array_keys($this->sections) as $key) {
            if ($this->isSectionVisible($key)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Check if section content matches search term
     */
    public function matchesSearch(string ...$terms): bool
    {
        if (trim($this->search) === '') {
            return true;
        }

        $needle = mb_strtolower(trim($this->search));
        foreach ($terms as $term) {
            if (str_contains(mb_strtolower($term), $needle)) {
                return true;
            }
        }

        return false;
    }
}

<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class PermissionsGuide extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Administrativo';

    protected static ?string $navigationLabel = 'Manual de Permissões';

    protected static ?string $title = 'Manual de Gestão de Permissões e Perfis da Equipe';

    protected static ?string $slug = 'permissions-guide';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.permissions-guide';

    public string $activeCategory = 'all';

    public string $search = '';

    public function updatedSearch(): void
    {
        if (trim($this->search) !== '') {
            $this->activeCategory = 'all';
        }
    }

    /**
     * Apenas usuários com a função 'super_admin' podem visualizar no menu e acessar a página.
     */
    public static function canAccess(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return true;
        }

        return method_exists($user, 'hasRole') && $user->hasRole('super_admin');
    }

    /**
     * Categorias de navegação do manual
     */
    public function getCategoriesProperty(): array
    {
        return [
            'all' => ['label' => 'Todos os Tópicos', 'icon' => 'heroicon-m-squares-2x2'],
            'conceito' => ['label' => 'Conceito: Cargos vs Chaves', 'icon' => 'heroicon-m-key'],
            'cargos' => ['label' => 'Modelos Prontos por Função', 'icon' => 'heroicon-m-user-group'],
            'interligacoes' => ['label' => 'Herança Automática (LMS/Matrículas)', 'icon' => 'heroicon-m-arrow-path-rounded-square'],
            'dicionario' => ['label' => 'Dicionário de Permissões', 'icon' => 'heroicon-m-book-open'],
            'seguranca' => ['label' => 'Ações Críticas & Segurança', 'icon' => 'heroicon-m-exclamation-triangle'],
            'faq' => ['label' => 'Dúvidas & Solução de Problemas', 'icon' => 'heroicon-m-question-mark-circle'],
        ];
    }

    public function isVisibleSection(string $sectionKey, array $sectionData): bool
    {
        if ($this->activeCategory !== 'all' && ! in_array($this->activeCategory, $sectionData['categories'] ?? [], true)) {
            return false;
        }

        if (trim($this->search) === '') {
            return true;
        }

        $term = mb_strtolower(trim($this->search));
        $terms = array_map('mb_strtolower', $sectionData['terms'] ?? []);

        foreach ($terms as $t) {
            if (str_contains($t, $term) || str_contains($term, $t)) {
                return true;
            }
        }

        return false;
    }
}

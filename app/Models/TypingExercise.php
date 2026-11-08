<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TypingExercise extends Model
{
    use HasFactory;

    protected $table = 'typing_exercises';

    protected $fillable = [
        'title',
        'level',
        'category',
        'target_text',
        'instructions',
        'keyboard_layout',
        'min_wpm',
        'min_accuracy',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'min_wpm' => 'integer',
            'min_accuracy' => 'decimal:2',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function records(): HasMany
    {
        return $this->hasMany(StudentTypingRecord::class, 'typing_exercise_id');
    }

    /**
     * Retorna o Ranking dos melhores alunos neste exercício específico
     *
     * @return Collection<int, StudentTypingRecord>
     */
    public function topRankings(int $limit = 10): Collection
    {
        return $this->records()
            ->where('passed', true)
            ->whereDoesntHave('student.collaborators', function ($q) {
                $q->where('is_educator', true);
            })
            ->with(['student', 'contract'])
            ->orderByDesc('wpm')
            ->orderByDesc('accuracy_percentage')
            ->limit($limit)
            ->get();
    }

    public function levelLabel(): string
    {
        return match ($this->level) {
            1 => 'Nível 1: Teclas Guias & Dedos Individuais (ASDF JKLÇ)',
            2 => 'Nível 2: Alfabeto Completo & Pangramas (Todas as Letras A-Z)',
            3 => 'Nível 3: Palavras Completas & Vocabulário de Informática',
            4 => 'Nível 4: Acentuação ABNT2 (Agudo, Circunflexo, Til, Crase e Cedilha)',
            5 => 'Nível 5: Pontuação, Aspas, Parênteses e Símbolos (@ # $ % & *)',
            6 => 'Nível 6: Textos Curtos (Ergonomia e Boas Práticas)',
            7 => 'Nível 7: Textos Médios (Sistemas Operacionais e Informática)',
            8 => 'Nível 8: Textos Longos (Redação Empresarial e Produtividade)',
            default => "Nível {$this->level}",
        };
    }

    public function categoryLabel(): string
    {
        return match ($this->category) {
            'home_row' => 'Teclas Guias (Home Row)',
            'all_letters', 'letters' => 'Todas as Letras (Pangramas A-Z)',
            'words' => 'Palavras Completas e Vocabulário',
            'accents' => 'Acentuação e Cedilha',
            'symbols' => 'Pontuação, Aspas e Símbolos',
            'short_text' => 'Textos Curtos (30 a 60 palavras)',
            'medium_text' => 'Textos Médios (80 a 140 palavras)',
            'long_text', 'texts' => 'Textos Longos (150+ palavras)',
            default => ucfirst(str_replace('_', ' ', $this->category)),
        };
    }
}

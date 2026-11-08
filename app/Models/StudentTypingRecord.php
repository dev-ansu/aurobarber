<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class StudentTypingRecord extends Model
{
    use HasFactory;

    protected $table = 'student_typing_records';

    protected $fillable = [
        'contract_id',
        'student_id',
        'typing_exercise_id',
        'lesson_id',
        'wpm',
        'cpm',
        'accuracy_percentage',
        'total_keystrokes',
        'correct_keystrokes',
        'error_keystrokes',
        'time_spent_seconds',
        'stars',
        'passed',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'wpm' => 'decimal:2',
            'cpm' => 'decimal:2',
            'accuracy_percentage' => 'decimal:2',
            'total_keystrokes' => 'integer',
            'correct_keystrokes' => 'integer',
            'error_keystrokes' => 'integer',
            'time_spent_seconds' => 'integer',
            'stars' => 'integer',
            'passed' => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'student_id');
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(TypingExercise::class, 'typing_exercise_id');
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(LmsLesson::class, 'lesson_id');
    }

    /**
     * Calcula as estrelas de maestria (gamificação)
     */
    public static function calculateStars(float $wpm, float $accuracy, int $minWpm): int
    {
        if ($accuracy >= 95.0 && $wpm >= ($minWpm * 1.3)) {
            return 3;
        }

        if ($accuracy >= 90.0 && $wpm >= $minWpm) {
            return 2;
        }

        return 1;
    }

    /**
     * Retorna o Ranking Geral de Digitação da Escola (agrupado por aluno)
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    public static function globalSchoolRankings(int $limit = 20): \Illuminate\Support\Collection
    {
        return self::query()
            ->where('student_typing_records.passed', true)
            ->join('people', 'student_typing_records.student_id', '=', 'people.id')
            ->join('contracts', 'student_typing_records.contract_id', '=', 'contracts.id')
            ->whereNotExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('collaborators')
                    ->whereColumn('collaborators.person_id', 'student_typing_records.student_id')
                    ->where('collaborators.is_educator', true);
            })
            ->select([
                'student_typing_records.student_id',
                'student_typing_records.contract_id',
                'people.name as student_name',
                'contracts.contract_number',
                DB::raw('COUNT(DISTINCT student_typing_records.typing_exercise_id) as completed_exercises_count'),
                DB::raw('MAX(student_typing_records.wpm) as max_wpm'),
                DB::raw('ROUND(AVG(student_typing_records.wpm), 2) as avg_wpm'),
                DB::raw('ROUND(AVG(student_typing_records.accuracy_percentage), 2) as avg_accuracy'),
                DB::raw('SUM(student_typing_records.stars) as total_stars'),
            ])
            ->groupBy([
                'student_typing_records.student_id',
                'student_typing_records.contract_id',
                'people.name',
                'contracts.contract_number',
            ])
            ->orderByDesc('max_wpm')
            ->orderByDesc('avg_accuracy')
            ->limit($limit)
            ->get();
    }
}

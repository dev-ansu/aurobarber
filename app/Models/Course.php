<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('courses')]
#[Fillable(['name', 'description', 'workload', 'active', 'ouro_moderno_id'])]
class Course extends Model
{
    protected static bool $syncingFromOuroModerno = false;

    public static function syncingFromOuroModerno(callable $callback): mixed
    {
        static::$syncingFromOuroModerno = true;

        try {
            return $callback();
        } finally {
            static::$syncingFromOuroModerno = false;
        }
    }

    protected static function booted(): void
    {
        static::updating(function (Course $course) {
            if (
                $course->getOriginal('ouro_moderno_id') !== null
                && ! static::$syncingFromOuroModerno
            ) {
                // Impede alterar o identificador de integração, o nome ou a carga horária de cursos sincronizados
                if ($course->isDirty('ouro_moderno_id') || $course->isDirty('name') || $course->isDirty('workload')) {
                    throw new \LogicException(
                        'O nome, carga horária e ID de integração da Ouro Moderno não podem ser alterados manualmente.'
                    );
                }
            }
        });

        static::deleting(function (Course $course) {
            if ($course->ouro_moderno_id !== null) {
                throw new \LogicException(
                    'Cursos da Ouro Moderno não podem ser excluídos.'
                );
            }

            foreach ($course->lmsModules as $module) {
                $module->delete();
            }
        });

        static::updated(function (Course $course): void {
            if (! $course->wasChanged('workload')) {
                return;
            }

            $course->formations()
                ->with('contractItems.contract')
                ->get()
                ->each(function (Formation $formation): void {
                    $formation->contractItems
                        ->each(fn (ContractItem $contractItem) => $contractItem->contract->recalculateEstimatedEndDate());
                });
        });
    }

    public function formations(): BelongsToMany
    {
        return $this->belongsToMany(Formation::class, 'formation_course')
            ->withPivot('order');
    }

    public function contractCourses(): HasMany
    {
        return $this->hasMany(ContractCourse::class);
    }

    public function isFromOuroModerno(): bool
    {
        return $this->ouro_moderno_id !== null;
    }

    public function isAurosoftLms(): bool
    {
        return $this->ouro_moderno_id === null;
    }

    public function lmsModules(): HasMany
    {
        return $this->hasMany(LmsModule::class)->orderBy('sort_order');
    }
}

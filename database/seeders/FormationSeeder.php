<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Formation;
use Illuminate\Database\Seeder;

class FormationSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CourseSeeder::class);

        $formations = [
            [
                'name' => 'Formação em Tecnologia',
                'description' => 'Trilha de desenvolvimento web e design gráfico.',
                'courses' => ['Programação Web', 'Design Gráfico'],
            ],
            [
                'name' => 'Formação em Negócios',
                'description' => 'Trilha de gestão empresarial e marketing digital.',
                'courses' => ['Gestão Empresarial', 'Marketing Digital'],
            ],
        ];

        foreach ($formations as $formationData) {
            $formation = Formation::firstOrCreate(
                ['name' => $formationData['name']],
                [
                    'description' => $formationData['description'],
                    'active' => true,
                ],
            );

            foreach ($formationData['courses'] as $order => $courseName) {
                $course = Course::query()
                    ->where('name', $courseName)
                    ->firstOrFail();

                $formation->courses()->syncWithoutDetaching([
                    $course->id => ['order' => $order + 1],
                ]);
            }
        }
    }
}

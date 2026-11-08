<?php

namespace Database\Seeders;

use App\Enums\RelationshipEnum;
use App\Models\Classroom;
use App\Models\Collaborator;
use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\ContractType;
use App\Models\FinancialType;
use App\Models\Formation;
use App\Models\Installment;
use App\Models\PaymentMethod;
use App\Models\Person;
use App\Models\Responsible;
use Illuminate\Database\Seeder;

class ActiveContractSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CourseSeeder::class,
            FormationSeeder::class,
            ContractTypeSeeder::class,
        ]);

        $contractType = ContractType::query()
            ->where('name', 'Contrato de Prestação de Serviços Educacionais - Demonstração')
            ->where('version', now()->format('Y').'.1-demo')
            ->firstOrFail();

        $collaborator = $this->collaborator();
        $financialType = FinancialType::updateOrCreate(
            ['slug' => 'mensalidade_demo'],
            [
                'name' => 'Mensalidade (Demonstração)',
                'is_active' => true,
            ],
        );
        $paymentMethod = PaymentMethod::updateOrCreate(
            ['slug' => 'pix_demo'],
            [
                'name' => 'Pix (Demonstração)',
                'is_active' => true,
            ],
        );

        $rooms = [
            'Sala de Demonstração 1' => $this->classroom('Sala de Demonstração 1'),
            'Sala de Demonstração 2' => $this->classroom('Sala de Demonstração 2'),
            'Sala de Demonstração 3' => $this->classroom('Sala de Demonstração 3'),
        ];
        $demoYear = now()->format('Y');

        $contracts = [
            [
                'number' => "DEMO-{$demoYear}-001",
                'student_name' => 'Ana Souza (Demonstração)',
                'student_email' => 'ana.demo@aurosoft.test',
                'responsible_name' => 'Mariana Souza (Demonstração)',
                'responsible_email' => 'mariana.demo@aurosoft.test',
                'formation_name' => 'Formação em Tecnologia',
                'contract_total_value' => 2400,
                'installments' => 6,
                'room' => 'Sala de Demonstração 1',
                'schedules' => [
                    ['weekday' => 1, 'start_hour' => '18:00:00', 'end_hour' => '19:00:00'],
                    ['weekday' => 3, 'start_hour' => '18:00:00', 'end_hour' => '19:00:00'],
                ],
            ],
            [
                'number' => "DEMO-{$demoYear}-002",
                'student_name' => 'Pedro Lima (Demonstração)',
                'student_email' => 'pedro.demo@aurosoft.test',
                'responsible_name' => 'Claudia Lima (Demonstração)',
                'responsible_email' => 'claudia.demo@aurosoft.test',
                'formation_name' => 'Formação em Tecnologia',
                'contract_total_value' => 3600,
                'installments' => 12,
                'room' => 'Sala de Demonstração 2',
                'schedules' => [
                    ['weekday' => 2, 'start_hour' => '19:00:00', 'end_hour' => '20:00:00'],
                    ['weekday' => 4, 'start_hour' => '19:00:00', 'end_hour' => '20:00:00'],
                ],
            ],
            [
                'number' => "DEMO-{$demoYear}-003",
                'student_name' => 'Lucas Alves (Demonstração)',
                'student_email' => 'lucas.demo@aurosoft.test',
                'responsible_name' => 'Renata Alves (Demonstração)',
                'responsible_email' => 'renata.demo@aurosoft.test',
                'formation_name' => 'Formação em Negócios',
                'contract_total_value' => 1800,
                'installments' => 6,
                'room' => 'Sala de Demonstração 3',
                'schedules' => [
                    ['weekday' => 1, 'start_hour' => '19:00:00', 'end_hour' => '20:00:00'],
                    ['weekday' => 5, 'start_hour' => '18:00:00', 'end_hour' => '19:00:00'],
                ],
            ],
        ];

        foreach ($contracts as $contractData) {
            $student = $this->person(
                $contractData['student_name'],
                $contractData['student_email'],
            );
            $responsiblePerson = $this->person(
                $contractData['responsible_name'],
                $contractData['responsible_email'],
            );
            $responsible = Responsible::updateOrCreate(
                [
                    'person_id' => $responsiblePerson->id,
                    'relationship' => RelationshipEnum::Mother,
                ],
                ['financial_responsible' => true],
            );

            $contract = Contract::updateOrCreate(
                ['contract_number' => $contractData['number']],
                [
                    'contract_type_id' => $contractType->id,
                    'student_id' => $student->id,
                    'responsible_id' => $responsible->id,
                    'collaborator_id' => $collaborator->id,
                    'start_date' => now()->startOfMonth()->toDateString(),
                    'end_date' => now()->addYear()->startOfMonth()->toDateString(),
                    'total_value' => $contractData['contract_total_value'],
                    'discount_value' => 0,
                    'status' => 'active',
                    'observations' => 'Contrato de demonstração gerado pelo DemoDataSeeder.',
                ],
            );

            $formation = Formation::query()
                ->with('courses')
                ->where('name', $contractData['formation_name'])
                ->firstOrFail();

            $item = ContractItem::updateOrCreate(
                [
                    'contract_id' => $contract->id,
                    'formation_id' => $formation->id,
                ],
                [],
            );

            foreach ($formation->courses as $course) {
                $item->contractCourses()->firstOrCreate([
                    'course_id' => $course->id,
                ]);
            }

            $room = $rooms[$contractData['room']];

            foreach ($contractData['schedules'] as $scheduleData) {
                $item->schedules()->firstOrCreate(
                    [
                        'weekday' => $scheduleData['weekday'],
                        'start_hour' => $scheduleData['start_hour'],
                        'end_hour' => $scheduleData['end_hour'],
                    ],
                    ['classroom_id' => $room->id],
                );
            }

            $installmentAmount = $contractData['contract_total_value'] / $contractData['installments'];
            $firstDueDate = now()->startOfMonth()->subMonth()->addDays(4);

            for ($installmentNumber = 1; $installmentNumber <= $contractData['installments']; $installmentNumber++) {
                $dueDate = $firstDueDate->copy()->addMonths($installmentNumber - 1);
                $isPaid = $installmentNumber === 1;

                Installment::updateOrCreate(
                    [
                        'contract_id' => $contract->id,
                        'installment_number' => $installmentNumber,
                    ],
                    [
                        'financial_type_id' => $financialType->id,
                        'payment_method_id' => $isPaid ? $paymentMethod->id : null,
                        'total_installments' => $contractData['installments'],
                        'amount' => $installmentAmount,
                        'discount' => 0,
                        'fine' => 0,
                        'amount_paid' => $isPaid ? $installmentAmount : null,
                        'due_date' => $dueDate->toDateString(),
                        'paid_at' => $isPaid ? $dueDate->toDateString() : null,
                        'status' => $isPaid ? 'paid' : 'pending',
                    ],
                );
            }
        }
    }

    private function person(string $name, string $email): Person
    {
        return Person::firstOrCreate(
            ['email' => $email],
            ['name' => $name],
        );
    }

    private function collaborator(): Collaborator
    {
        $person = $this->person(
            'Consultor Aurosoft (Demonstração)',
            'consultor.demo@aurosoft.test',
        );

        return Collaborator::updateOrCreate(
            ['person_id' => $person->id],
            [
                'admission_date' => now()->subYear()->toDateString(),
                'role' => 'Consultor comercial',
                'active' => true,
            ],
        );
    }

    private function classroom(string $name): Classroom
    {
        return Classroom::updateOrCreate(
            ['name' => $name],
            [
                'description' => 'Sala de demonstração para testes locais.',
                'capacity' => 20,
                'active' => true,
            ],
        );
    }
}

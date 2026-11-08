<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Installment;
use App\Models\Person;
use App\Models\School;
use App\Services\FinancialCalculationService;
use App\Services\PixService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolGlobalInheritanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_filial_inherits_financial_policy_from_headquarters_when_enabled(): void
    {
        $matriz = School::create([
            'id' => 1,
            'legal_name' => 'Matriz Cursos Ltda',
            'trade_name' => 'ITEP Matriz',
            'cnpj' => '11.111.111/0001-11',
            'is_headquarters' => true,
            'late_fine_percentage' => 3.50,
            'daily_interest_percentage' => 0.05,
            'grace_days' => 5,
            'punctuality_discount_enabled' => true,
            'punctuality_discount_type' => 'fixed',
            'punctuality_discount_value' => 25.00,
            'punctuality_discount_days_tolerance' => 2,
        ]);

        $filial = School::create([
            'id' => 2,
            'legal_name' => 'Filial Cursos Ltda',
            'trade_name' => 'ITEP Filial',
            'cnpj' => '22.222.222/0001-22',
            'is_headquarters' => false,
            'inherit_financial_policy' => true,
            // Valores próprios que NÃO devem ser usados porque está herdando
            'late_fine_percentage' => 1.00,
            'daily_interest_percentage' => 0.01,
            'grace_days' => 0,
        ]);

        $policy = $filial->resolveFinancialPolicy();

        $this->assertTrue($policy['is_inherited']);
        $this->assertEquals(3.50, $policy['late_fine_percentage']);
        $this->assertEquals(0.05, $policy['daily_interest_percentage']);
        $this->assertEquals(5, $policy['grace_days']);
        $this->assertTrue($policy['punctuality_discount_enabled']);
        $this->assertEquals(25.00, $policy['punctuality_discount_value']);

        $student = Person::create(['name' => 'Aluno Teste']);
        $contract = Contract::create([
            'contract_number' => 'CTR-INHERIT-01',
            'student_id' => $student->id,
            'school_id' => $filial->id,
            'start_date' => now()->toDateString(),
            'amount' => 100.00,
            'payment_day' => 10,
        ]);

        $installment = Installment::create([
            'contract_id' => $contract->id,
            'school_id' => $filial->id,
            'installment_number' => 1,
            'amount' => 100.00,
            'due_date' => now()->subDays(10)->toDateString(),
            'status' => 'pending',
        ]);

        $calcService = app(FinancialCalculationService::class);
        $charges = $calcService->calculateOverdueCharges($installment, now(), $filial);

        // 10 dias de atraso, com 5 dias de carência da Matriz -> atraso reconhecido e calculado
        $this->assertTrue($charges['is_overdue']);
        $this->assertEquals(3.50, $charges['fine_amount']); // Multa de 3.50% da Matriz
    }

    public function test_filial_uses_custom_policy_when_inheritance_is_disabled(): void
    {
        $matriz = School::create([
            'id' => 1,
            'legal_name' => 'Matriz Cursos Ltda',
            'trade_name' => 'ITEP Matriz',
            'cnpj' => '11.111.111/0001-11',
            'is_headquarters' => true,
            'late_fine_percentage' => 5.00,
        ]);

        $filial = School::create([
            'id' => 2,
            'legal_name' => 'Filial Cursos Ltda',
            'trade_name' => 'ITEP Filial',
            'cnpj' => '22.222.222/0001-22',
            'is_headquarters' => false,
            'inherit_financial_policy' => false,
            'late_fine_percentage' => 1.50,
            'daily_interest_percentage' => 0.02,
            'grace_days' => 1,
        ]);

        $policy = $filial->resolveFinancialPolicy();

        $this->assertFalse($policy['is_inherited']);
        $this->assertEquals(1.50, $policy['late_fine_percentage']);
    }

    public function test_filial_inherits_pix_settings_from_headquarters(): void
    {
        $matriz = School::create([
            'id' => 1,
            'legal_name' => 'Matriz Cursos Ltda',
            'trade_name' => 'ITEP Matriz',
            'cnpj' => '11.111.111/0001-11',
            'is_headquarters' => true,
            'pix_enabled' => true,
            'pix_key_type' => 'cnpj',
            'pix_key' => '11111111000111',
            'pix_merchant_name' => 'ITEP MATRIZ CENTRO',
            'pix_merchant_city' => 'FORTALEZA',
        ]);

        $filial = School::create([
            'id' => 2,
            'legal_name' => 'Filial Cursos Ltda',
            'trade_name' => 'ITEP Filial',
            'cnpj' => '22.222.222/0001-22',
            'is_headquarters' => false,
            'inherit_pix_settings' => true,
            'pix_key' => 'chave_filial_que_nao_deve_aparecer',
        ]);

        $pix = $filial->resolvePixSettings();

        $this->assertTrue($pix['is_inherited']);
        $this->assertEquals('11111111000111', $pix['pix_key']);
        $this->assertEquals('ITEP MATRIZ CENTRO', $pix['pix_merchant_name']);

        $student = Person::create(['name' => 'Aluno Teste']);
        $contract = Contract::create([
            'contract_number' => 'CTR-PIX-01',
            'student_id' => $student->id,
            'school_id' => $filial->id,
            'start_date' => now()->toDateString(),
            'amount' => 100.00,
            'payment_day' => 10,
        ]);

        $installment = Installment::create([
            'contract_id' => $contract->id,
            'school_id' => $filial->id,
            'installment_number' => 1,
            'amount' => 100.00,
            'due_date' => now()->addDays(5)->toDateString(),
            'status' => 'pending',
        ]);

        $pixData = app(PixService::class)->generateForInstallment($installment, $filial);

        $this->assertNotNull($pixData);
        $this->assertStringContainsString('ITEP MATRIZ CENTRO', $pixData['copy_paste']);
    }
}

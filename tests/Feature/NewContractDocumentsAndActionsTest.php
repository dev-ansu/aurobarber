<?php

namespace Tests\Feature;

use App\Filament\Resources\Contracts\Actions\CancelContractWithFeeAction;
use App\Filament\Resources\Contracts\Actions\FinishContractAction;
use App\Filament\Resources\Contracts\Pages\EditContracts;
use App\Filament\Resources\Contracts\Pages\ViewContracts;
use App\Models\Contract;
use App\Models\FinancialType;
use App\Models\Installment;
use App\Models\Person;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NewContractDocumentsAndActionsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private School $school;
    private Contract $contract;
    private FinancialType $typeMulta;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        $this->school = School::create([
            'legal_name' => 'Aurosoft Cursos Profissionais LTDA',
            'trade_name' => 'Aurosoft Idiomas & Tecnologia',
            'cnpj' => '12.345.678/0001-90',
            'is_main' => true,
        ]);

        $person = Person::create([
            'name' => 'Coordenador Teste',
            'email' => 'coord@aurosoft.com',
        ]);

        $this->user = User::factory()->create([
            'person_id' => $person->id,
            'email' => 'coord@aurosoft.com',
        ]);
        $this->user->assignRole('super_admin');

        $studentPerson = Person::create([
            'name' => 'Maria Silva Sauro',
            'email' => 'maria@teste.com',
        ]);

        $this->contract = Contract::create([
            'school_id' => $this->school->id,
            'student_id' => $studentPerson->id,
            'contract_number' => 'CTR-2026-999',
            'status' => 'active',
            'start_date' => '2026-02-01',
            'total_value' => 2400.00,
        ]);

        $this->typeMulta = FinancialType::create([
            'code' => '1.2.1',
            'name' => 'Multa de Rescisão',
            'slug' => 'multa_de_rescisao',
            'type' => 'revenue',
            'is_synthetic' => false,
            'is_active' => true,
        ]);

        // Cria 2 parcelas pendentes
        Installment::create([
            'school_id' => $this->school->id,
            'contract_id' => $this->contract->id,
            'financial_type_id' => $this->typeMulta->id,
            'installment_number' => 1,
            'total_installments' => 2,
            'amount' => 200.00,
            'due_date' => '2026-11-10',
            'status' => 'pending',
        ]);

        Installment::create([
            'school_id' => $this->school->id,
            'contract_id' => $this->contract->id,
            'financial_type_id' => $this->typeMulta->id,
            'installment_number' => 2,
            'total_installments' => 2,
            'amount' => 200.00,
            'due_date' => '2026-12-10',
            'status' => 'pending',
        ]);
    }

    public function test_all_new_documents_render_successfully(): void
    {
        $this->actingAs($this->user);

        // 1. Declaração de Comparecimento
        $response = $this->get(route('contracts.declaracao-comparecimento', [
            'contract' => $this->contract,
            'date' => '2026-10-31',
            'start_time' => '14:00',
            'end_time' => '17:00',
            'purpose' => 'Avaliação Final de Informática',
        ]));
        $response->assertOk();
        $response->assertSee('Declaração de Comparecimento');
        $response->assertSee('Maria Silva Sauro');
        $response->assertSee('Avaliação Final de Informática');

        // 2. Termo de Rescisão / Distrato
        $response = $this->get(route('contracts.termo-cancelamento', [
            'contract' => $this->contract,
            'cancellation_reason' => 'Mudança de endereço residencial',
            'fee_amount' => 150.00,
        ]));
        $response->assertOk();
        $response->assertSee('Termo de Rescisão e Distrato Contratual');
        $response->assertSee('Mudança de endereço residencial');
        $response->assertSee('150,00');

        // 3. Termo de Trancamento Provisório
        $response = $this->get(route('contracts.termo-trancamento', [
            'contract' => $this->contract,
            'days' => 60,
            'reason' => 'Tratamento de saúde',
        ]));
        $response->assertOk();
        $response->assertSee('Termo de Trancamento Provisório de Matrícula');
        $response->assertSee('60 dias');
        $response->assertSee('Tratamento de saúde');

        // 4. Termo de Autorização de Imagem e Voz (LGPD)
        $response = $this->get(route('contracts.autorizacao-imagem', [
            'contract' => $this->contract,
        ]));
        $response->assertOk();
        $response->assertSee('Autorização de Uso de Imagem e Voz');
        $response->assertSee('LGPD');
        $response->assertSee('Maria Silva Sauro');

        // 5. Carteirinha Estudantil
        $response = $this->get(route('contracts.carteirinha-estudantil', [
            'contract' => $this->contract,
        ]));
        $response->assertOk();
        $response->assertSee('Identificação Estudantil');
        $response->assertSee('CTR-2026-999');
    }

    public function test_cancel_contract_with_fee_action_in_view_and_edit(): void
    {
        $this->actingAs($this->user);

        // Testa no ViewContracts
        Livewire::test(ViewContracts::class, ['record' => $this->contract->id])
            ->assertActionExists('cancelContractWithFee')
            ->assertActionExists('finishContract')
            ->assertActionExists('adjustFinancial')
            ->callAction('cancelContractWithFee', [
                'fee_amount' => 120.00,
                'financial_type_id' => $this->typeMulta->id,
                'cancellation_reason' => 'Incompatibilidade de horários escolares',
            ])
            ->assertHasNoActionErrors();

        $this->contract->refresh();
        $this->assertEquals('canceled', $this->contract->status);
        $this->assertEquals('Incompatibilidade de horários escolares', $this->contract->cancellation_reason);

        // As parcelas pendentes antigas foram removidas e a de multa criada
        $this->assertCount(1, $this->contract->installments);
        $this->assertEquals(120.00, (float) $this->contract->installments->first()->amount);
    }

    public function test_finish_contract_action(): void
    {
        $this->actingAs($this->user);

        Livewire::test(ViewContracts::class, ['record' => $this->contract->id])
            ->assertActionExists('finishContract')
            ->callAction('finishContract', [
                'reason' => 'Conclusão regular com todas as provas aprovadas.',
            ])
            ->assertHasNoActionErrors();

        $this->contract->refresh();
        $this->assertEquals('finished', $this->contract->status);
    }

    public function test_custom_contract_types_per_document_type_persists_and_replaces_clauses(): void
    {
        $this->actingAs($this->user);

        // 1. Cria minuta personalizada para Rescisão
        $customCancellation = \App\Models\ContractType::create([
            'name' => 'Minuta Customizada Rescisão Escola Modelo',
            'document_type' => \App\Models\ContractType::TYPE_CANCELLATION,
            'version' => '2026.2',
            'content' => '<p>CLÁUSULA ESPECIAL RESCISÃO CUSTOM: O aluno {aluno_nome} rescindiu o contrato {numero_contrato} com taxa de {valor_multa}.</p>',
            'is_active' => true,
        ]);

        // 2. Cria minuta personalizada para Trancamento
        $customSuspension = \App\Models\ContractType::create([
            'name' => 'Minuta Customizada Trancamento Escola Modelo',
            'document_type' => \App\Models\ContractType::TYPE_SUSPENSION,
            'version' => '2026.2',
            'content' => '<p>CLÁUSULA ESPECIAL TRANCAMENTO CUSTOM: Aluno {aluno_nome} trancado por {dias_trancamento} dias.</p>',
            'is_active' => true,
        ]);

        // Verifica que criar o template de trancamento ativo NÃO desativou o template de rescisão
        $customCancellation->refresh();
        $this->assertTrue($customCancellation->is_active);
        $this->assertTrue($customSuspension->is_active);

        // 3. Testa emissão do Termo de Rescisão com a minuta customizada da escola
        $response = $this->get(route('contracts.termo-cancelamento', [
            'contract' => $this->contract,
            'fee_amount' => 180.00,
        ]));
        $response->assertOk();
        $response->assertSee('CLÁUSULA ESPECIAL RESCISÃO CUSTOM: O aluno Maria Silva Sauro rescindiu o contrato CTR-2026-999 com taxa de R$ 180,00.');

        // 4. Testa emissão do Termo de Trancamento com a minuta customizada da escola
        $response = $this->get(route('contracts.termo-trancamento', [
            'contract' => $this->contract,
            'days' => 45,
        ]));
        $response->assertOk();
        $response->assertSee('CLÁUSULA ESPECIAL TRANCAMENTO CUSTOM: Aluno Maria Silva Sauro trancado por 45 dias.');
    }

    public function test_unified_actions_in_edit_and_view_contracts(): void
    {
        $this->actingAs($this->user);

        // Testa no EditContracts que todas as ações e documentos estão disponíveis
        Livewire::test(EditContracts::class, ['record' => $this->contract->id])
            ->assertActionExists('adjustFinancial')
            ->assertActionExists('renegotiateDebt')
            ->assertActionExists('importOuroModernoCourses')
            ->assertActionExists('finishContract')
            ->assertActionExists('cancelContractWithFee')
            ->assertActionExists('termoCancelamento')
            ->assertActionExists('termoTrancamento')
            ->assertActionExists('declaracaoMatricula')
            ->assertActionExists('declaracaoComparecimento')
            ->assertActionExists('autorizacaoImagem')
            ->assertActionExists('carteirinhaEstudantil');

        // Testa no ViewContracts que todas as ações e documentos estão disponíveis
        Livewire::test(ViewContracts::class, ['record' => $this->contract->id])
            ->assertActionExists('adjustFinancial')
            ->assertActionExists('renegotiateDebt')
            ->assertActionExists('importOuroModernoCourses')
            ->assertActionExists('finishContract')
            ->assertActionExists('suspendContract')
            ->assertActionExists('cancelContractWithFee')
            ->assertActionExists('termoCancelamento')
            ->assertActionExists('termoTrancamento')
            ->assertActionExists('declaracaoMatricula')
            ->assertActionExists('declaracaoComparecimento')
            ->assertActionExists('autorizacaoImagem')
            ->assertActionExists('carteirinhaEstudantil');
    }

    public function test_suspend_and_reactivate_contract_flow(): void
    {
        $this->actingAs($this->user);

        // 1. Trancar contrato
        Livewire::test(ViewContracts::class, ['record' => $this->contract->id])
            ->assertActionExists('suspendContract')
            ->callAction('suspendContract', [
                'start_date' => '2026-11-01',
                'days' => 30,
                'expected_return_date' => '2026-12-01',
                'reason' => 'Viagem de intercâmbio de 1 mês',
            ])
            ->assertHasNoActionErrors();

        $this->contract->refresh();
        $this->assertEquals('suspended', $this->contract->status);
        $this->assertEquals('2026-12-01', $this->contract->suspension_expected_return_date?->format('Y-m-d'));
        $this->assertEquals('Viagem de intercâmbio de 1 mês', $this->contract->suspension_reason);

        // 2. Reativar contrato
        Livewire::test(ViewContracts::class, ['record' => $this->contract->id])
            ->assertActionExists('reactivateContract')
            ->callAction('reactivateContract', [
                'notes' => 'Aluno retornou antes do prazo',
            ])
            ->assertHasNoActionErrors();

        $this->contract->refresh();
        $this->assertEquals('active', $this->contract->status);
        $this->assertNotNull($this->contract->suspension_returned_at);
        $this->assertStringContainsString('Aluno retornou antes do prazo', $this->contract->observations);
    }

    public function test_suspended_contracts_report_page(): void
    {
        $this->actingAs($this->user);

        // Deixa contrato suspenso para aparecer no relatório
        $this->contract->update([
            'status' => 'suspended',
            'suspended_at' => now(),
            'suspension_expected_return_date' => today()->addDays(10),
            'suspension_reason' => 'Motivo particular de teste',
        ]);

        $url = \App\Filament\Pages\SuspendedContractsReport::getUrl(['tenant' => $this->school]);
        $response = $this->get($url);
        $response->assertOk();
        $response->assertSee('Acompanhamento de Trancamentos de Matrícula');
        $response->assertSee('Total Trancados');
        $response->assertSee('Maria Silva Sauro');
        $response->assertSee('CTR-2026-999');
    }
}


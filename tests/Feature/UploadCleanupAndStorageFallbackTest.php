<?php

namespace Tests\Feature;

use App\Models\Installment;
use App\Models\Payable;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadCleanupAndStorageFallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_logo_replacement_deletes_previous_file_and_deletion_removes_file(): void
    {
        Storage::fake('public');

        $file1 = UploadedFile::fake()->image('logo1.png');
        $path1 = $file1->store('schools/logos', 'public');

        $school = School::create([
            'id' => 1,
            'legal_name' => 'Escola Teste',
            'trade_name' => 'Teste',
            'cnpj' => '11.222.333/0001-44',
            'logo_path' => $path1,
        ]);

        Storage::disk('public')->assertExists($path1);

        // Upload novo logotipo
        $file2 = UploadedFile::fake()->image('logo2.png');
        $path2 = $file2->store('schools/logos', 'public');

        $school->update(['logo_path' => $path2]);

        // Arquivo antigo deve ter sido excluído automaticamente
        Storage::disk('public')->assertMissing($path1);
        Storage::disk('public')->assertExists($path2);

        // Ao deletar a escola, o arquivo atual deve ser removido
        $school->delete();
        Storage::disk('public')->assertMissing($path2);
    }

    public function test_installment_and_payable_receipt_cleanup_on_update_and_delete(): void
    {
        Storage::fake('public');

        $school = School::create([
            'id' => 1,
            'legal_name' => 'Escola Teste',
            'trade_name' => 'Teste',
            'cnpj' => '11.222.333/0001-44',
        ]);

        $student = \App\Models\Person::create([
            'name' => 'Aluno Teste',
            'email' => 'aluno@teste.com',
        ]);

        $contract = \App\Models\Contract::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'contract_number' => 'CTR-TEST-01',
            'status' => 'draft',
            'start_date' => now()->toDateString(),
            'total_value' => 150.00,
        ]);

        $receipt1 = UploadedFile::fake()->create('comprovante1.pdf', 100);
        $path1 = $receipt1->store('receipts', 'public');

        $installment = Installment::create([
            'school_id' => $school->id,
            'contract_id' => $contract->id,
            'amount' => 150.00,
            'due_date' => now()->toDateString(),
            'status' => 'paid',
            'receipt_path' => $path1,
        ]);

        Storage::disk('public')->assertExists($path1);

        $receipt2 = UploadedFile::fake()->create('comprovante2.pdf', 100);
        $path2 = $receipt2->store('receipts', 'public');

        $installment->update(['receipt_path' => $path2]);

        Storage::disk('public')->assertMissing($path1);
        Storage::disk('public')->assertExists($path2);

        $installment->delete();
        Storage::disk('public')->assertMissing($path2);

        // Teste para Payable
        $payableFile1 = UploadedFile::fake()->create('payable1.pdf', 100);
        $payablePath1 = $payableFile1->store('payables/receipts', 'public');

        $payable = Payable::create([
            'school_id' => $school->id,
            'description' => 'Conta de Luz',
            'supplier' => 'Enel',
            'amount' => 200.00,
            'due_date' => now()->toDateString(),
            'status' => 'pending',
            'receipt_path' => $payablePath1,
        ]);

        Storage::disk('public')->assertExists($payablePath1);

        $payableFile2 = UploadedFile::fake()->create('payable2.pdf', 100);
        $payablePath2 = $payableFile2->store('payables/receipts', 'public');

        $payable->update(['receipt_path' => $payablePath2]);

        Storage::disk('public')->assertMissing($payablePath1);
        Storage::disk('public')->assertExists($payablePath2);

        $payable->delete();
        Storage::disk('public')->assertMissing($payablePath2);
    }

    public function test_storage_fallback_route_serves_files_when_symlink_bypassed(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('schools/logos/test-logo.png', 'fake image content');

        $response = $this->get('/storage/schools/logos/test-logo.png');
        $response->assertOk();
        $this->assertEquals('fake image content', $response->streamedContent());

        $missing = $this->get('/storage/schools/logos/non-existent.png');
        $missing->assertNotFound();
    }
}

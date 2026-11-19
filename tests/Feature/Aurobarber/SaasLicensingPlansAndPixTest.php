<?php

use App\Filament\Pages\ClientSubscriptionPage;
use App\Models\Barbershop;
use App\Models\SaasBillingSetting;
use App\Models\SaasPaymentReceipt;
use App\Models\SaasPaymentSubmission;
use App\Models\SaasPlan;
use App\Models\SaasPlanChangeRequest;
use App\Models\User;
use App\Services\PixService;
use App\Services\SaasPaymentReviewService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function createLicensedOwnerAndShop(array $shopOverrides = []): array
{
    $plan = SaasPlan::query()->where('slug', 'mensal')->firstOrFail();

    $shop = Barbershop::create(array_merge([
        'name' => 'Barbearia Prime',
        'slug' => 'barbearia-prime',
        'is_active' => true,
        'license_status' => 'active',
        'saas_plan_id' => $plan->id,
        'monthly_price' => $plan->monthly_price,
        'license_expires_at' => now()->addDays(20),
        'grace_period_days' => 5,
    ], $shopOverrides));

    $owner = User::create([
        'name' => 'Dono Prime',
        'email' => 'dono-prime@example.test',
        'password' => 'password',
        'role' => 'owner',
        'barbershop_id' => $shop->id,
        'is_active' => true,
    ]);

    $owner->barbershops()->attach($shop->id, [
        'role' => 'owner',
        'is_primary' => true,
    ]);

    return [$owner, $shop, $plan];
}

test('pix service generates valid br code payload and local svg qr code', function () {
    $pix = app(PixService::class);

    $payload = $pix->generatePayload(
        pixKey: 'financeiro@aurosoft.com.br',
        merchantName: 'AUROSOFT TECNOLOGIA',
        merchantCity: 'FORTALEZA',
        amount: 89.90,
        txid: 'AUROBARBER12611',
        description: 'Mensalidade AuroBarber',
    );

    expect($payload)->toStartWith('000201')
        ->toContain('br.gov.bcb.pix')
        ->toContain('financeiro@aurosoft.com.br')
        ->toContain('540589.90')
        ->toContain('6304');

    $qr = $pix->generateQrCodeDataUri($payload);
    expect($qr)->toStartWith('data:image/svg+xml');
});

test('client subscription page displays active plan, supports early payment and plan change request', function () {
    [$owner, $shop, $mensalPlan] = createLicensedOwnerAndShop();
    $anualPlan = SaasPlan::query()->where('slug', 'anual')->firstOrFail();

    SaasBillingSetting::current()->update([
        'pix_enabled' => true,
        'pix_key' => 'pix@aurosoft.com.br',
        'merchant_name' => 'AUROSOFT',
        'merchant_city' => 'FORTALEZA',
    ]);

    Filament::setTenant($shop, isQuiet: true);
    $this->actingAs($owner);

    Livewire::test(ClientSubscriptionPage::class)
        ->assertSee('Plano Mensal')
        ->assertSee('Antecipar pagamento')
        ->call('showEarlyPayment')
        ->assertSet('earlyPaymentRequested', true)
        ->assertSee('Pagar mensalidade via Pix')
        ->assertSee('pix@aurosoft.com.br')
        ->call('requestPlanChange', $anualPlan->id);

    $request = SaasPlanChangeRequest::query()->where('barbershop_id', $shop->id)->sole();
    expect($request->current_plan_id)->toBe($mensalPlan->id)
        ->and($request->requested_plan_id)->toBe($anualPlan->id)
        ->and($request->status)->toBe('pending');

    // Duplicate request is ignored gracefully
    Livewire::test(ClientSubscriptionPage::class)
        ->call('requestPlanChange', $anualPlan->id);

    expect(SaasPlanChangeRequest::query()->where('barbershop_id', $shop->id)->count())->toBe(1);
});

test('tenant can upload pix proof when payment window is open and cannot upload duplicate while pending', function () {
    Storage::fake('saas_private');

    [$owner, $shop] = createLicensedOwnerAndShop([
        'license_expires_at' => now()->addDays(3),
    ]);

    $this->actingAs($owner);

    $response = $this->post(route('tenant.billing.payments.store', ['tenant' => $shop->slug]), [
        'proof' => UploadedFile::fake()->create('comprovante.pdf', 120, 'application/pdf'),
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('status');

    $submission = SaasPaymentSubmission::query()->where('barbershop_id', $shop->id)->sole();
    expect($submission->status)->toBe('pending')
        ->and((float) $submission->amount)->toBe(89.90);
    Storage::disk('saas_private')->assertExists($submission->proof_path);

    // Second upload while pending should fail validation
    $second = $this->post(route('tenant.billing.payments.store', ['tenant' => $shop->slug]), [
        'proof' => UploadedFile::fake()->create('segundo.pdf', 120, 'application/pdf'),
    ]);

    $second->assertSessionHasErrors('proof');
    expect(SaasPaymentSubmission::query()->where('barbershop_id', $shop->id)->count())->toBe(1);
});

test('payment review service approves submission, renews barbershop license and records receipt', function () {
    [$owner, $shop] = createLicensedOwnerAndShop([
        'license_status' => 'suspended',
        'is_active' => false,
        'license_expires_at' => now()->subDays(10),
    ]);

    $master = User::create([
        'name' => 'Master SaaS',
        'email' => 'master@example.test',
        'password' => 'password',
        'role' => 'master_admin',
        'is_active' => true,
    ]);

    $submission = SaasPaymentSubmission::create([
        'barbershop_id' => $shop->id,
        'saas_plan_id' => $shop->saas_plan_id,
        'submitted_by_name' => $owner->name,
        'submitted_by_email' => $owner->email,
        'amount' => 89.90,
        'proof_path' => 'payment-proofs/'.$shop->id.'/proof.pdf',
        'status' => 'pending',
    ]);

    $this->actingAs($master);

    $review = app(SaasPaymentReviewService::class);
    $approved = $review->approve($submission, '89.90', now()->toDateString(), 30);

    expect($approved->status)->toBe('approved')
        ->and($approved->reviewed_by)->toBe($master->id);

    $shop->refresh();
    expect($shop->license_status)->toBe('active')
        ->and((bool) $shop->is_active)->toBeTrue()
        ->and($shop->license_expires_at->isFuture())->toBeTrue();

    $receipt = SaasPaymentReceipt::query()->where('payment_submission_id', $submission->id)->sole();
    expect((float) $receipt->amount)->toBe(89.90)
        ->and($receipt->barbershop_id)->toBe($shop->id);

    // Double approval is prevented
    expect(fn () => $review->approve($submission, '89.90', now()->toDateString(), 30))
        ->toThrow(ValidationException::class);
});

test('payment review service rejects submission with reason', function () {
    [$owner, $shop] = createLicensedOwnerAndShop();

    $master = User::create([
        'name' => 'Master SaaS',
        'email' => 'master-reject@example.test',
        'password' => 'password',
        'role' => 'master_admin',
        'is_active' => true,
    ]);

    $submission = SaasPaymentSubmission::create([
        'barbershop_id' => $shop->id,
        'saas_plan_id' => $shop->saas_plan_id,
        'submitted_by_name' => $owner->name,
        'submitted_by_email' => $owner->email,
        'amount' => 89.90,
        'proof_path' => 'payment-proofs/'.$shop->id.'/proof.pdf',
        'status' => 'pending',
    ]);

    $this->actingAs($master);

    $rejected = app(SaasPaymentReviewService::class)->reject($submission, 'Valor divergente da mensalidade.');

    expect($rejected->status)->toBe('rejected')
        ->and($rejected->review_note)->toBe('Valor divergente da mensalidade.');
});

test('suspended barbershop can still access client subscription page to pay via pix', function () {
    [$owner, $shop] = createLicensedOwnerAndShop([
        'license_status' => 'suspended',
        'is_active' => false,
        'license_expires_at' => now()->subDays(10),
    ]);

    $this->actingAs($owner);

    $response = $this->get('/admin/'.$shop->slug.'/planos-de-licenciamento');
    $response->assertOk()
        ->assertSee('Sua licença está suspensa');
});

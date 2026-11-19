<?php

declare(strict_types=1);

use App\Filament\Saas\Pages\SaasBackups;
use App\Models\Barbershop;
use App\Models\SaasRestoreTestRun;
use App\Models\User;
use App\Services\LocalBackupManager;
use App\Services\SaasRestoreTestService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function createTestBackupZip(): string
{
    $backupDir = storage_path('app/private/backups');
    \Illuminate\Support\Facades\File::ensureDirectoryExists($backupDir);
    $filename = 'aurobarber-backup-test-'.\Illuminate\Support\Str::uuid().'.zip';
    $zip = new ZipArchive;
    $zip->open($backupDir.'/'.$filename, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString('manifest.json', json_encode(['database_driver' => 'pgsql', 'scope' => 'application']));
    $zip->addFromString('database.dump', 'DUMMY_PG_DUMP_HEADER_AND_DATA');
    $zip->close();

    return $filename;
}

test('saas restore test service can create sandbox, validate, and clean up', function () {
    $admin = User::create([
        'name' => 'Master Auditor',
        'email' => 'auditor@aurobarber.com',
        'password' => bcrypt('password123'),
        'role' => 'master_admin',
        'is_active' => true,
    ]);

    $filename = createTestBackupZip();

    $service = app(SaasRestoreTestService::class);
    $run = $service->createSandbox($filename, $admin->id);

    expect($run)->toBeInstanceOf(SaasRestoreTestRun::class)
        ->and($run->status)->toBe('ready')
        ->and($run->target_schema)->toStartWith('restore_test_')
        ->and($run->automated_check_status)->toBe('passed')
        ->and($run->expires_at)->not->toBeNull()
        ->and($run->isActive())->toBeTrue();

    // Validação Humana
    $service->submitHumanValidation($run, 'approved', 'Backup validado com 100% de integridade', $admin->id);
    $run->refresh();

    expect($run->human_validation_status)->toBe('approved')
        ->and($run->human_validation_notes)->toBe('Backup validado com 100% de integridade')
        ->and($run->human_validated_by_user_id)->toBe($admin->id);

    // Destruição da Sandbox
    $service->cleanupSandbox($run);
    $run->refresh();

    expect($run->status)->toBe('cleaned');

    @unlink(storage_path('app/private/backups/'.$filename));
});

test('saas backups page integrates sandbox creation and audit modal correctly', function () {
    $admin = User::create([
        'name' => 'Master Admin',
        'email' => 'master-backup@aurobarber.com',
        'password' => bcrypt('password123'),
        'role' => 'master_admin',
        'is_active' => true,
    ]);

    $filename = createTestBackupZip();

    Filament::setCurrentPanel(Filament::getPanel('saas'));

    $this->actingAs($admin);

    $test = Livewire::test(SaasBackups::class)
        ->assertSuccessful()
        ->assertSee('Quadro Geral de Backups')
        ->call('createSandbox', $filename)
        ->assertHasNoErrors();

    $run = SaasRestoreTestRun::where('backup_file', $filename)->first();
    expect($run)->not->toBeNull();

    $test->call('openAuditModal', $run->id)
        ->assertSet('showAuditModal', true)
        ->assertSet('selectedSandboxId', $run->id)
        ->set('validationStatus', 'approved')
        ->set('validationNotes', 'Aprovado pelo teste automatizado')
        ->call('submitValidation')
        ->assertSet('showAuditModal', false);

    $run->refresh();
    expect($run->human_validation_status)->toBe('approved');

    $test->call('cleanupSandbox', $run->id)
        ->assertHasNoErrors();

    $run->refresh();
    expect($run->status)->toBe('cleaned');

    @unlink(storage_path('app/private/backups/'.$filename));
});

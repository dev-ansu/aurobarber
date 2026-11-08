<?php

use App\Filament\Pages\BackupManagement;
use App\Models\Person;
use App\Models\User;
use App\Services\LocalBackupManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('local backup manager status, retention policy, delete and copy to external path', function () {
    $manager = app(LocalBackupManager::class);
    $backupDir = storage_path('app/private/backups');
    File::ensureDirectoryExists($backupDir);

    // 1. Testa status sem backups
    // Limpa pasta temporariamente
    File::cleanDirectory($backupDir);
    $status = $manager->status();
    expect($status['total_backups'])->toBe(0)
        ->and($status['is_healthy'])->toBeFalse();

    // 2. Cria 12 arquivos de backup falsos com datas crescentes
    $externalDir = storage_path('app/testing_external_pendrive');
    File::ensureDirectoryExists($externalDir);

    Carbon::setTestNow('2026-10-16 13:00:00');

    for ($i = 1; $i <= 12; $i++) {
        $minute = str_pad((string) $i, 2, '0', STR_PAD_LEFT);
        $filename = "aurosoft-backup-20261016-12{$minute}00.zip";
        $filePath = "{$backupDir}/{$filename}";
        file_put_contents($filePath, "fake backup content {$i}");
        touch($filePath, strtotime("2026-10-16 12:{$minute}:00"));
    }

    $list = $manager->list();
    expect(count($list))->toBe(12);

    // 3. Testa status com backups recentes
    $status = $manager->status();
    expect($status['total_backups'])->toBe(12)
        ->and($status['is_healthy'])->toBeTrue();

    // 4. Testa retenção (mantendo apenas 10)
    $deleted = $manager->applyRetention(10);
    expect($deleted)->toBe(2)
        ->and(count($manager->list()))->toBe(10);

    // 5. Testa cópia para pen drive / caminho externo
    $newestBackup = $manager->list()[0]['name'];
    $copiedPath = $manager->copyToExternalPath($newestBackup, $externalDir);
    expect(file_exists($copiedPath))->toBeTrue()
        ->and(filesize($copiedPath))->toBe(filesize("{$backupDir}/{$newestBackup}"));

    // 6. Testa exclusão manual
    $manager->delete($newestBackup);
    expect(count($manager->list()))->toBe(9);

    // Limpeza
    File::deleteDirectory($externalDir);
    File::cleanDirectory($backupDir);
});

test('backup management page renders health status banner and actions', function () {
    $adminRole = Role::findOrCreate('super_admin', 'web');
    $person = Person::create(['name' => 'Admin Backup', 'email' => 'backup.admin@example.com']);
    $user = User::create([
        'person_id' => $person->id,
        'email' => 'backup.admin@example.com',
        'password' => 'secret',
    ]);
    $user->assignRole($adminRole);
    $user->givePermissionTo(
        Permission::findOrCreate('View:BackupManagement', 'web'),
        Permission::findOrCreate('Download:BackupManagement', 'web'),
        Permission::findOrCreate('Restore:BackupManagement', 'web'),
    );

    $this->actingAs($user);

    $backupDir = storage_path('app/private/backups');
    File::ensureDirectoryExists($backupDir);
    $fakeFile = 'aurosoft-backup-20261016-100000.zip';
    file_put_contents("{$backupDir}/{$fakeFile}", 'dummy content');

    Livewire::test(BackupManagement::class)
        ->assertSee('Proteção Local Ativa')
        ->assertSee($fakeFile)
        ->assertSee('Copiar p/ Pen Drive')
        ->call('openExportModal', $fakeFile)
        ->assertSet('showExportModal', true)
        ->assertSet('exportFilename', $fakeFile)
        ->call('closeExportModal')
        ->assertSet('showExportModal', false);

    File::cleanDirectory($backupDir);
});

test('authenticated user with permission can download backup file via direct route', function () {
    $adminRole = Role::findOrCreate('super_admin', 'web');
    $person = Person::create(['name' => 'Admin Download', 'email' => 'download.admin@example.com']);
    $user = User::create([
        'person_id' => $person->id,
        'email' => 'download.admin@example.com',
        'password' => 'secret',
    ]);
    $user->assignRole($adminRole);
    $user->givePermissionTo(
        Permission::findOrCreate('Download:BackupManagement', 'web'),
    );

    $backupDir = storage_path('app/private/backups');
    File::ensureDirectoryExists($backupDir);
    $fakeFile = 'aurosoft-backup-20261016-100000.zip';
    file_put_contents("{$backupDir}/{$fakeFile}", 'fake zip content for download');

    $response = $this->actingAs($user)->get(route('admin.backups.download', ['filename' => $fakeFile]));
    $response->assertSuccessful();
    $response->assertHeader('content-disposition', 'attachment; filename=aurosoft-backup-20261016-100000.zip');

    File::cleanDirectory($backupDir);
});

test('local backup manager imports valid external backup file', function () {
    $manager = app(LocalBackupManager::class);
    $backupDir = storage_path('app/private/backups');
    File::ensureDirectoryExists($backupDir);

    $tempExternalDir = storage_path('app/temp_pendrive');
    File::ensureDirectoryExists($tempExternalDir);
    $externalZip = $tempExternalDir.'/my-backup-usb.zip';

    $zip = new ZipArchive;
    $zip->open($externalZip, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString('manifest.json', json_encode(['database_driver' => 'pgsql']));
    $zip->addFromString('database.dump', 'fake pgsql dump');
    $zip->close();

    $imported = $manager->importFromExternalPath($externalZip);
    expect($imported)->toStartWith('aurosoft-backup-')
        ->and(file_exists("{$backupDir}/{$imported}"))->toBeTrue();

    File::deleteDirectory($tempExternalDir);
    File::cleanDirectory($backupDir);
});

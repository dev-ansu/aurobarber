<?php

declare(strict_types=1);

namespace Tests\Feature\Aurobarber;

use App\Models\SaasBackupSetting;
use App\Services\Backup\BackupEncryptionService;
use App\Services\SaasGoogleDriveBackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class GoogleDriveBackupSecurityTest extends TestCase
{
    use RefreshDatabase;
    protected string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = storage_path('app/private/tmp/test_backup_sec_'.uniqid());
        File::ensureDirectoryExists($this->tempDir);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->tempDir);
        parent::tearDown();
    }

    public function test_backup_encryption_service_encrypts_and_decrypts_with_tamper_validation(): void
    {
        $service = app(BackupEncryptionService::class);

        $plainFile = $this->tempDir.'/original.dump';
        $content = 'CONTEÚDO_SENSIVEL_DE_TESTE_SQL_'.str_repeat('AUROBARBER_DATA_', 500);
        file_put_contents($plainFile, $content);

        $encrypted = $service->encryptFile($plainFile);
        $this->assertFileExists($encrypted['encrypted_path']);
        $this->assertNotEquals($content, file_get_contents($encrypted['encrypted_path']));
        $this->assertStringStartsWith(BackupEncryptionService::MAGIC_PREFIX, file_get_contents($encrypted['encrypted_path']));

        // Descriptografar de volta
        $decryptedFile = $this->tempDir.'/decrypted.dump';
        $decrypted = $service->decryptFile($encrypted['encrypted_path'], $decryptedFile, expectedSha256: $encrypted['sha256']);

        $this->assertFileExists($decryptedFile);
        $this->assertEquals($content, file_get_contents($decryptedFile));
        $this->assertEquals($encrypted['sha256'], $decrypted['sha256']);

        // Teste de detecção de adulteração (Tampering)
        $tamperedPath = $this->tempDir.'/tampered.dump.enc';
        copy($encrypted['encrypted_path'], $tamperedPath);
        $fp = fopen($tamperedPath, 'r+b');
        fseek($fp, 50);
        fwrite($fp, "\xFF\xFF\xFF");
        fclose($fp);

        $this->expectException(\RuntimeException::class);
        $service->decryptFile($tamperedPath, $this->tempDir.'/should_fail.dump');
    }

    public function test_saas_backup_settings_and_service_status(): void
    {
        $settings = SaasBackupSetting::current();
        $settings->google_drive_enabled = true;
        $settings->google_drive_client_id = 'test_client_id';
        $settings->google_drive_client_secret = Crypt::encryptString('test_secret');
        $settings->google_drive_refresh_token = Crypt::encryptString('test_refresh_token');
        $settings->google_drive_folder_id = 'test_folder_id';
        $settings->save();

        $service = app(SaasGoogleDriveBackupService::class);
        $this->assertTrue($service->isConfigured());
        $this->assertTrue($service->isEnabled());

        // Desativa
        $settings->google_drive_enabled = false;
        $settings->save();
        $this->assertFalse($service->isEnabled());
    }
}

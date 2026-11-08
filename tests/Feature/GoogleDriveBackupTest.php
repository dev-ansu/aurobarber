<?php

namespace Tests\Feature;

use App\Services\GoogleDriveBackupService;
use App\Services\LocalBackupManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleDriveBackupTest extends TestCase
{
    use RefreshDatabase;
    public function test_google_drive_service_configuration_detection(): void
    {
        $service = app(GoogleDriveBackupService::class);
        $this->assertTrue($service->isConfigured());
    }

    public function test_google_drive_service_refreshes_token(): void
    {
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'fake-access-token-12345',
                'expires_in' => 3600,
            ], 200),
        ]);

        $service = app(GoogleDriveBackupService::class);
        $token = $service->getAccessToken();

        $this->assertEquals('fake-access-token-12345', $token);
    }

    public function test_system_backup_command_uploads_to_google_drive(): void
    {
        $localManagerMock = $this->createMock(LocalBackupManager::class);
        $localManagerMock->method('create')->willReturn('aurosoft-backup-test.zip');
        $this->app->instance(LocalBackupManager::class, $localManagerMock);

        $mock = $this->createMock(GoogleDriveBackupService::class);
        $mock->method('isConfigured')->willReturn(true);
        $mock->method('isEnabled')->willReturn(true);
        $mock->method('uploadFile')->willReturn([
            'success' => true,
            'file_id' => 'fake-drive-id-123',
            'name' => 'aurosoft-backup-test.zip',
            'size' => 1024,
        ]);
        $this->app->instance(GoogleDriveBackupService::class, $mock);

        $this->artisan('system:backup')
            ->assertSuccessful();
    }

    public function test_system_backup_is_scheduled_twice_daily(): void
    {
        $schedule = app(\Illuminate\Console\Scheduling\Schedule::class);
        $events = collect($schedule->events());

        $backupEvent = $events->first(function ($event) {
            return str_contains($event->command ?? '', 'system:backup');
        });

        $this->assertNotNull($backupEvent, 'Comando system:backup deve estar registrado no agendador.');
        $this->assertEquals('0 12,23 * * *', $backupEvent->expression);
    }

    public function test_google_drive_configuration_is_shared_across_schools(): void
    {
        $matriz = \App\Models\School::create([
            'id' => 1,
            'legal_name' => 'Escola Matriz',
            'trade_name' => 'Matriz',
            'google_drive_client_id' => 'matriz-client-id-xyz',
            'google_drive_folder_id' => 'matriz-folder-123',
            'google_drive_client_secret' => \Illuminate\Support\Facades\Crypt::encryptString('secret-123'),
            'google_drive_refresh_token' => \Illuminate\Support\Facades\Crypt::encryptString('refresh-123'),
        ]);

        $filialY = \App\Models\School::create([
            'id' => 2,
            'legal_name' => 'Escola Filial Y',
            'trade_name' => 'Filial Y',
        ]);

        // Filial Y automatically inherits Matriz backup configuration on creation
        $this->assertEquals('matriz-client-id-xyz', $filialY->google_drive_client_id);
        $this->assertEquals('matriz-folder-123', $filialY->google_drive_folder_id);

        // When Filament tenant is switched to Filial Y, service resolves Matriz credentials
        \Filament\Facades\Filament::setTenant($filialY, isQuiet: true);
        $service = app(GoogleDriveBackupService::class);
        $service->resolveCredentials();
        $this->assertTrue($service->isConfigured());
    }

    public function test_system_backup_updates_timestamp_for_all_schools(): void
    {
        $matriz = \App\Models\School::create([
            'id' => 1,
            'legal_name' => 'Escola Matriz',
            'trade_name' => 'Matriz',
        ]);

        $filial = \App\Models\School::create([
            'id' => 2,
            'legal_name' => 'Escola Filial',
            'trade_name' => 'Filial',
        ]);

        $localManagerMock = $this->createMock(LocalBackupManager::class);
        $localManagerMock->method('create')->willReturn('aurosoft-backup-test.zip');
        $this->app->instance(LocalBackupManager::class, $localManagerMock);

        $mock = $this->createMock(GoogleDriveBackupService::class);
        $mock->method('isConfigured')->willReturn(true);
        $mock->method('isEnabled')->willReturn(true);
        $mock->method('uploadFile')->willReturn([
            'success' => true,
            'file_id' => 'fake-drive-id-999',
            'name' => 'aurosoft-backup-test.zip',
            'size' => 2048,
        ]);
        $this->app->instance(GoogleDriveBackupService::class, $mock);

        $this->artisan('system:backup')
            ->assertSuccessful();

        $matriz->refresh();
        $filial->refresh();

        $this->assertNotNull($matriz->google_drive_last_backup_at);
        $this->assertNotNull($filial->google_drive_last_backup_at);
        $this->assertStringContainsString('fake-drive-id-999', $matriz->google_drive_last_backup_status);
        $this->assertStringContainsString('fake-drive-id-999', $filial->google_drive_last_backup_status);
    }
}

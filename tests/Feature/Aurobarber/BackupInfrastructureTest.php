<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\GoogleDriveBackupService;
use App\Services\LocalBackupManager;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BackupInfrastructureTest extends TestCase
{
    public function test_google_drive_configuration_comes_from_application_config(): void
    {
        config([
            'services.google_drive.enabled' => true,
            'services.google_drive.client_id' => 'client-id',
            'services.google_drive.client_secret' => 'client-secret',
            'services.google_drive.refresh_token' => 'refresh-token',
            'services.google_drive.folder_id' => 'folder-id',
        ]);

        $service = new GoogleDriveBackupService;

        $this->assertTrue($service->isConfigured());
        $this->assertTrue($service->isEnabled());
    }

    public function test_google_drive_service_refreshes_access_token(): void
    {
        config([
            'services.google_drive.client_id' => 'client-id',
            'services.google_drive.client_secret' => 'client-secret',
            'services.google_drive.refresh_token' => 'refresh-token',
            'services.google_drive.folder_id' => 'folder-id',
        ]);

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'fake-access-token',
                'expires_in' => 3600,
            ]),
        ]);

        $this->assertSame('fake-access-token', (new GoogleDriveBackupService)->getAccessToken());

        Http::assertSent(fn ($request): bool => $request->url() === 'https://oauth2.googleapis.com/token'
            && $request['client_id'] === 'client-id'
            && $request['refresh_token'] === 'refresh-token');
    }

    public function test_backup_command_uploads_using_the_configured_drive_service(): void
    {
        $filename = 'aurobarber-backup-20261109-120000.zip';
        $backups = $this->createMock(LocalBackupManager::class);
        $backups->expects($this->once())->method('create')->willReturn($filename);
        $this->app->instance(LocalBackupManager::class, $backups);

        $drive = $this->createMock(GoogleDriveBackupService::class);
        $drive->method('isConfigured')->willReturn(true);
        $drive->method('isEnabled')->willReturn(true);
        $drive->expects($this->once())
            ->method('uploadFile')
            ->with(storage_path('app/private/backups/'.$filename), $filename)
            ->willReturn(['file_id' => 'drive-id']);
        $this->app->instance(GoogleDriveBackupService::class, $drive);

        $this->artisan('system:backup')->assertSuccessful();
    }

    public function test_system_backup_remains_scheduled_twice_daily(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($event): bool => str_contains($event->command ?? '', 'system:backup'));

        $this->assertNotNull($event);
        $this->assertSame('0 12,23 * * *', $event->expression);
    }
}

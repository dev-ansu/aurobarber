<?php

use App\Filament\Pages\ExportHistory;
use App\Models\Person;
use App\Models\User;
use App\Policies\ExportPolicy;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

function exportHistoryUser(string $suffix, array $permissions = []): User
{
    $person = Person::create(['name' => "Usuário {$suffix}", 'email' => "export-{$suffix}@example.com"]);
    $user = User::create(['person_id' => $person->id, 'email' => "export-{$suffix}@example.com", 'password' => 'password']);

    foreach ($permissions as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }

    return $user;
}

test('export history downloads are restricted to their owner and require download permission', function () {
    $owner = exportHistoryUser('owner', ['Download:Export']);
    $otherUser = exportHistoryUser('other', ['Download:Export']);
    $export = Export::query()->create([
        'completed_at' => now(),
        'file_disk' => 'local',
        'file_name' => 'contracts-1',
        'exporter' => 'App\\Filament\\Exports\\ContractExporter',
        'processed_rows' => 1,
        'total_rows' => 1,
        'successful_rows' => 1,
        'user_id' => $owner->id,
    ]);
    $policy = app(ExportPolicy::class);

    expect($policy->view($owner, $export))->toBeTrue()
        ->and($policy->view($otherUser, $export))->toBeFalse()
        ->and($policy->view(exportHistoryUser('no-permission'), $export))->toBeFalse();
});

test('export history requires the dedicated navigation permission', function () {
    expect(ExportHistory::canAccess())->toBeFalse();

    $user = exportHistoryUser('viewer', ['ViewAny:Export']);
    $this->actingAs($user);

    expect(ExportHistory::canAccess())->toBeTrue();
});

test('export history page lists only files created by the signed-in user', function () {
    $owner = exportHistoryUser('history-owner', ['ViewAny:Export']);
    $other = exportHistoryUser('history-other', ['ViewAny:Export']);
    $ownExport = Export::query()->create([
        'file_disk' => 'local',
        'file_name' => 'own-file',
        'exporter' => 'App\\Filament\\Exports\\ContractExporter',
        'total_rows' => 1,
        'user_id' => $owner->id,
    ]);
    $otherExport = Export::query()->create([
        'file_disk' => 'local',
        'file_name' => 'other-file',
        'exporter' => 'App\\Filament\\Exports\\ContractExporter',
        'total_rows' => 1,
        'user_id' => $other->id,
    ]);
    $this->actingAs($owner);

    Livewire::test(ExportHistory::class)
        ->assertCanSeeTableRecords([$ownExport])
        ->assertCanNotSeeTableRecords([$otherExport]);
});

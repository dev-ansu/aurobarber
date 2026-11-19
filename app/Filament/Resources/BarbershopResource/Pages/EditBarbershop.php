<?php

namespace App\Filament\Resources\BarbershopResource\Pages;

use App\Filament\Resources\BarbershopResource;
use App\Models\Barbershop;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class EditBarbershop extends EditRecord
{
    protected static string $resource = BarbershopResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $ownerType = $data['owner_type'] ?? 'existing';

        if ($ownerType === 'new' && ! empty($data['owner_email'])) {
            $tempPassword = 'Barber#'.rand(1000, 9999).'!';

            $owner = User::create([
                'name' => $data['owner_name'],
                'email' => $data['owner_email'],
                'phone' => $data['owner_phone'] ?? null,
                'password' => Hash::make($tempPassword),
                'role' => 'owner',
                'must_change_password' => true,
                'email_verified_at' => now(),
                'is_active' => true,
            ]);

            $owner->assignRole('barbershop_owner');
            $data['owner_id'] = $owner->id;
        }

        unset($data['owner_type'], $data['owner_name'], $data['owner_email'], $data['owner_phone'], $data['send_welcome_email']);

        /** @var Barbershop $updated */
        $updated = parent::handleRecordUpdate($record, $data);

        if ($updated->owner_id) {
            $updated->users()->syncWithoutDetaching([
                $updated->owner_id => [
                    'role' => 'owner',
                    'is_primary' => true,
                ],
            ]);
        }

        return $updated;
    }
}

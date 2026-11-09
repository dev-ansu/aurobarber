<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $tenant = filament()->getTenant();
        if ($tenant) {
            $data['barbershop_id'] = $tenant->id;
        }
        $data['role'] = $data['role'] ?? 'barber';
        $data['email_verified_at'] = now();

        return $data;
    }

    protected function afterCreate(): void
    {
        $tenant = filament()->getTenant();
        if ($tenant && $this->record) {
            $tenant->users()->syncWithoutDetaching([
                $this->record->id => [
                    'role' => 'barber',
                    'is_primary' => true,
                ],
            ]);

            // Se não tiver nenhum cargo selecionado, atribui o cargo 'barber' por padrão
            if (method_exists($this->record, 'roles') && $this->record->roles()->count() === 0) {
                $barberRole = \Spatie\Permission\Models\Role::where('name', 'barber')->first();
                if ($barberRole) {
                    $this->record->assignRole($barberRole);
                }
            }

            // Se for um barbeiro restrito, vincula ou cria automaticamente a ficha de Barbeiro (Professional)
            if ($this->record->isRestrictedBarber()) {
                $existingProf = \App\Models\Professional::where('barbershop_id', $tenant->id)
                    ->whereNull('user_id')
                    ->where(function ($q) {
                        $q->whereRaw('LOWER(name) = ?', [ mb_strtolower(trim($this->record->name)) ]);
                        if (! empty($this->record->phone)) {
                            $q->orWhere('phone', $this->record->phone);
                        }
                    })
                    ->first();

                if ($existingProf) {
                    $existingProf->update(['user_id' => $this->record->id]);
                } else {
                    \App\Models\Professional::firstOrCreate(
                        [
                            'barbershop_id' => $tenant->id,
                            'user_id' => $this->record->id,
                        ],
                        [
                            'name' => $this->record->name,
                            'phone' => $this->record->phone,
                            'commission_rate' => 50.00,
                            'working_days' => ['mon', 'tue', 'wed', 'thu', 'fri', 'sat'],
                            'work_start_time' => '08:00:00',
                            'work_end_time' => '19:00:00',
                            'is_active' => true,
                        ]
                    );
                }
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

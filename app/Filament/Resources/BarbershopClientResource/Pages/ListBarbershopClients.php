<?php

namespace App\Filament\Resources\BarbershopClientResource\Pages;

use App\Filament\Resources\BarbershopClientResource;
use App\Models\Appointment;
use App\Models\BarbershopClient;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBarbershopClients extends ListRecords
{
    protected static string $resource = BarbershopClientResource::class;

    public function mount(): void
    {
        parent::mount();

        // Sincroniza automaticamente clientes de agendamentos anteriores na primeira abertura
        $tenant = filament()->getTenant();
        if ($tenant) {
            $appointments = Appointment::where('barbershop_id', $tenant->id)
                ->where('is_blocked_slot', false)
                ->whereNotNull('client_phone')
                ->get()
                ->groupBy(fn ($a) => preg_replace('/\D/', '', (string) $a->client_phone));

            $targetCuts = (int) ($tenant->loyalty_target_cuts ?: 10);

            foreach ($appointments as $cleanPhone => $group) {
                if (strlen((string) $cleanPhone) < 8) {
                    continue;
                }

                $latest = $group->sortByDesc('scheduled_at')->first();
                $completed = $group->where('status', 'completed');
                $completedCount = $completed->count();
                $spent = (float) $completed->sum('service_price') + (float) $completed->sum('products_total');
                $lastVisit = $completed->max('scheduled_at') ?? $latest->scheduled_at;

                $client = BarbershopClient::firstOrNew([
                    'barbershop_id' => $tenant->id,
                    'phone' => (string) $cleanPhone,
                ]);

                if (!$client->exists) {
                    $client->name = $latest->client_name ?: 'Cliente';
                    $client->total_visits = $completedCount;
                    $client->total_spent = $spent;
                    $client->loyalty_stamps = $completedCount % $targetCuts;
                    $client->last_visit_at = $lastVisit;
                    $client->saveQuietly();
                }
            }
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Novo Cliente')
                ->icon('heroicon-o-user-plus'),
        ];
    }
}

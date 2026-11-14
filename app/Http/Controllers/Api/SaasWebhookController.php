<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Barbershop;
use App\Models\BarbershopClient;
use App\Models\Professional;
use App\Models\User;
use App\Services\LocalBackupManager;
use App\Services\TenantService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class SaasWebhookController extends Controller
{
    public function __construct(
        protected TenantService $tenantService,
        protected LocalBackupManager $backupManager,
    ) {}

    /**
     * Roteador principal de eventos do Webhook SaaS Master.
     */
    public function handle(Request $request): JsonResponse
    {
        $action = (string) ($request->input('action') ?? $request->input('event') ?? $request->query('action'));

        return match ($action) {
            'provision' => $this->provision($request),
            'sync_license' => $this->syncLicense($request),
            'suspend' => $this->suspend($request),
            'activate', 'renew' => $this->activate($request),
            'telemetry' => $this->telemetry($request),
            'backup_full' => $this->backupFull($request),
            'backup_tenant' => $this->backupTenant($request),
            'list_backups' => $this->listBackups($request),
            'restore_backup' => $this->restoreBackup($request),
            default => response()->json([
                'success' => false,
                'message' => "Ação desconhecida: '{$action}'.",
            ], Response::HTTP_BAD_REQUEST),
        };
    }

    /**
     * 1. Provisiona uma nova barbearia no Aurobarber e cria seu schema nativo isolado.
     */
    protected function provision(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:100'],
            'owner_name' => ['required', 'string', 'max:255'],
            'owner_email' => ['required', 'email', 'max:255'],
            'owner_password' => ['nullable', 'string', 'min:6'],
            'phone' => ['nullable', 'string', 'max:30'],
            'license_status' => ['nullable', 'string'],
            'license_expires_at' => ['nullable', 'date'],
            'monthly_price' => ['nullable', 'numeric'],
            'grace_period_days' => ['nullable', 'integer'],
        ]);

        $slug = Str::slug($validated['slug']);

        try {
            DB::beginTransaction();

            // 1. Cria ou encontra o usuário proprietário da barbearia
            $owner = User::firstOrCreate(
                ['email' => $validated['owner_email']],
                [
                    'name' => $validated['owner_name'],
                    'password' => Hash::make($validated['owner_password'] ?? Str::random(12)),
                    'phone' => $validated['phone'] ?? null,
                    'is_active' => true,
                    'role' => 'barbershop_owner',
                ]
            );

            // Garante atribuição do cargo barbershop_owner
            if (method_exists($owner, 'assignRole') && ! $owner->hasRole('barbershop_owner')) {
                try {
                    $owner->assignRole('barbershop_owner');
                } catch (Throwable) {
                    // Ignora caso role já esteja associada
                }
            }

            $schemaName = 'tenant_' . str_replace('-', '_', $slug);

            // 2. Cria ou atualiza a Barbearia
            $barbershop = Barbershop::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $validated['name'],
                    'owner_id' => $owner->id,
                    'phone' => $validated['phone'] ?? null,
                    'schema_name' => $schemaName,
                    'license_status' => $validated['license_status'] ?? 'active',
                    'license_expires_at' => ! empty($validated['license_expires_at'])
                        ? Carbon::parse($validated['license_expires_at'])
                        : now()->addDays(30),
                    'monthly_price' => $validated['monthly_price'] ?? 0,
                    'grace_period_days' => $validated['grace_period_days'] ?? 5,
                    'is_active' => ($validated['license_status'] ?? 'active') === 'active',
                ]
            );

            // Vincula o proprietário à barbearia
            if (! $barbershop->users()->where('users.id', $owner->id)->exists()) {
                $barbershop->users()->attach($owner->id, ['role' => 'owner', 'is_primary' => true]);
            }

            // Atualiza o barbershop_id do usuário se não tiver
            if (empty($owner->barbershop_id)) {
                $owner->update(['barbershop_id' => $barbershop->id]);
            }

            // 3. Cria o schema PostgreSQL isolado e migra as tabelas
            $this->tenantService->createTenant($schemaName);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Barbearia '{$barbershop->name}' provisionada com sucesso no schema '{$schemaName}'.",
                'data' => [
                    'barbershop_id' => $barbershop->id,
                    'name' => $barbershop->name,
                    'slug' => $barbershop->slug,
                    'schema_name' => $schemaName,
                    'owner_email' => $owner->email,
                    'login_url' => url("/admin/{$barbershop->slug}/login"),
                    'license_expires_at' => $barbershop->license_expires_at?->toIso8601String(),
                ],
            ], Response::HTTP_CREATED);

        } catch (Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erro ao provisionar barbearia: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * 2. Sincroniza dados da licença (status, data de renovação, carência e mensalidade).
     */
    protected function syncLicense(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'slug' => ['required', 'string'],
            'license_status' => ['nullable', 'string', 'in:active,suspended,cancelled,trial'],
            'license_expires_at' => ['nullable', 'date'],
            'grace_period_days' => ['nullable', 'integer'],
            'monthly_price' => ['nullable', 'numeric'],
            'license_notes' => ['nullable', 'string'],
        ]);

        $barbershop = Barbershop::where('slug', $validated['slug'])->first();

        if (! $barbershop) {
            return response()->json([
                'success' => false,
                'message' => "Barbearia com slug '{$validated['slug']}' não encontrada.",
            ], Response::HTTP_NOT_FOUND);
        }

        $updates = [];

        if (isset($validated['license_status'])) {
            $updates['license_status'] = $validated['license_status'];
            $updates['is_active'] = $validated['license_status'] === 'active';
            if ($validated['license_status'] === 'suspended') {
                $updates['suspended_at'] = now();
                $updates['suspension_reason'] = $validated['license_notes'] ?? 'Suspensão sincronizada pelo SaaS Master';
            } elseif ($validated['license_status'] === 'active') {
                $updates['suspended_at'] = null;
                $updates['suspension_reason'] = null;
            }
        }

        if (! empty($validated['license_expires_at'])) {
            $updates['license_expires_at'] = Carbon::parse($validated['license_expires_at']);
        }

        if (isset($validated['grace_period_days'])) {
            $updates['grace_period_days'] = (int) $validated['grace_period_days'];
        }

        if (isset($validated['monthly_price'])) {
            $updates['monthly_price'] = (float) $validated['monthly_price'];
        }

        if (isset($validated['license_notes'])) {
            $updates['license_notes'] = $validated['license_notes'];
        }

        $barbershop->update($updates);

        return response()->json([
            'success' => true,
            'message' => "Licença da barbearia '{$barbershop->name}' sincronizada com sucesso.",
            'data' => [
                'slug' => $barbershop->slug,
                'license_status' => $barbershop->license_status,
                'license_expires_at' => $barbershop->license_expires_at?->toIso8601String(),
                'is_active' => (bool) $barbershop->is_active,
                'days_remaining' => $barbershop->daysRemaining(),
            ],
        ]);
    }

    /**
     * 3. Suspende imediatamente o acesso da barbearia.
     */
    protected function suspend(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'slug' => ['required', 'string'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $barbershop = Barbershop::where('slug', $validated['slug'])->first();

        if (! $barbershop) {
            return response()->json(['success' => false, 'message' => 'Barbearia não encontrada.'], Response::HTTP_NOT_FOUND);
        }

        $barbershop->suspend($validated['reason'] ?? 'Suspensão administrativa via SaaS Master');

        return response()->json([
            'success' => true,
            'message' => "Acesso da barbearia '{$barbershop->name}' suspenso com sucesso.",
            'data' => [
                'slug' => $barbershop->slug,
                'license_status' => 'suspended',
                'is_active' => false,
            ],
        ]);
    }

    /**
     * 4. Reativa / Renova o acesso da barbearia.
     */
    protected function activate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'slug' => ['required', 'string'],
            'days' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $barbershop = Barbershop::where('slug', $validated['slug'])->first();

        if (! $barbershop) {
            return response()->json(['success' => false, 'message' => 'Barbearia não encontrada.'], Response::HTTP_NOT_FOUND);
        }

        if (! empty($validated['expires_at'])) {
            $barbershop->forceFill([
                'license_status' => 'active',
                'license_expires_at' => Carbon::parse($validated['expires_at']),
                'is_active' => true,
                'suspended_at' => null,
                'suspension_reason' => null,
            ])->save();
        } else {
            $barbershop->renew($validated['days'] ?? 30);
        }

        return response()->json([
            'success' => true,
            'message' => "Barbearia '{$barbershop->name}' ativada/renovada com sucesso.",
            'data' => [
                'slug' => $barbershop->slug,
                'license_status' => 'active',
                'license_expires_at' => $barbershop->license_expires_at?->toIso8601String(),
                'is_active' => true,
                'days_remaining' => $barbershop->daysRemaining(),
            ],
        ]);
    }

    /**
     * 5. Retorna telemetria e métricas de uso em tempo real para o dashboard do SaaS Master.
     */
    protected function telemetry(Request $request): JsonResponse
    {
        $slug = $request->input('slug');

        if ($slug) {
            $barbershop = Barbershop::where('slug', $slug)->first();

            if (! $barbershop) {
                return response()->json(['success' => false, 'message' => 'Barbearia não encontrada.'], Response::HTTP_NOT_FOUND);
            }

            // Conta registros do tenant específico
            $profsCount = Professional::where('barbershop_id', $barbershop->id)->count();
            $clientsCount = BarbershopClient::where('barbershop_id', $barbershop->id)->count();
            $appointmentsCount = Appointment::where('barbershop_id', $barbershop->id)->count();
            $appointmentsThisMonth = Appointment::where('barbershop_id', $barbershop->id)
                ->whereMonth('scheduled_at', now()->month)
                ->whereYear('scheduled_at', now()->year)
                ->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $barbershop->id,
                    'name' => $barbershop->name,
                    'slug' => $barbershop->slug,
                    'schema_name' => $barbershop->schema_name,
                    'license_status' => $barbershop->license_status,
                    'license_expires_at' => $barbershop->license_expires_at?->toIso8601String(),
                    'is_active' => (bool) $barbershop->is_active,
                    'days_remaining' => $barbershop->daysRemaining(),
                    'professionals_count' => $profsCount,
                    'clients_count' => $clientsCount,
                    'total_appointments' => $appointmentsCount,
                    'month_appointments' => $appointmentsThisMonth,
                ],
            ]);
        }

        // Telemetria global consolidada do Aurobarber
        $totalBarbershops = Barbershop::count();
        $activeBarbershops = Barbershop::where('is_active', true)->count();
        $totalProfessionals = Professional::count();
        $totalAppointmentsMonth = Appointment::whereMonth('scheduled_at', now()->month)
            ->whereYear('scheduled_at', now()->year)
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_barbershops' => $totalBarbershops,
                'active_barbershops' => $activeBarbershops,
                'total_professionals' => $totalProfessionals,
                'month_appointments' => $totalAppointmentsMonth,
                'tenants' => Barbershop::select(['id', 'name', 'slug', 'schema_name', 'license_status', 'license_expires_at', 'is_active'])->get(),
            ],
        ]);
    }

    /**
     * 6. Executa backup completo de todo o banco PostgreSQL e arquivos do Aurobarber.
     */
    protected function backupFull(Request $request): JsonResponse
    {
        try {
            $filename = $this->backupManager->create();

            return response()->json([
                'success' => true,
                'message' => 'Backup completo do Aurobarber gerado com sucesso.',
                'data' => [
                    'filename' => $filename,
                    'type' => 'full',
                    'created_at' => now()->toIso8601String(),
                ],
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Falha ao gerar backup: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * 7. Executa backup cirúrgico apenas do schema de uma barbearia específica.
     */
    protected function backupTenant(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'slug' => ['required', 'string'],
        ]);

        $barbershop = Barbershop::where('slug', $validated['slug'])->first();

        if (! $barbershop) {
            return response()->json(['success' => false, 'message' => 'Barbearia não encontrada.'], Response::HTTP_NOT_FOUND);
        }

        $schemaName = $barbershop->schema_name ?: ('tenant_' . str_replace('-', '_', $barbershop->slug));

        try {
            $filename = $this->backupManager->create(safetyCopy: false, schema: $schemaName);

            return response()->json([
                'success' => true,
                'message' => "Backup do schema '{$schemaName}' gerado com sucesso.",
                'data' => [
                    'filename' => $filename,
                    'slug' => $barbershop->slug,
                    'schema' => $schemaName,
                    'type' => 'tenant',
                    'created_at' => now()->toIso8601String(),
                ],
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => "Falha ao gerar backup do schema: " . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * 8. Lista os backups disponíveis no armazenamento local do Aurobarber.
     */
    protected function listBackups(Request $request): JsonResponse
    {
        $backups = $this->backupManager->list();

        return response()->json([
            'success' => true,
            'data' => $backups,
        ]);
    }

    /**
     * 9. Restaura um backup a partir do nome de arquivo com cópia de segurança preventiva automática.
     */
    protected function restoreBackup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'filename' => ['required', 'string'],
        ]);

        try {
            $this->backupManager->restore($validated['filename']);

            return response()->json([
                'success' => true,
                'message' => "Backup '{$validated['filename']}' restaurado com sucesso. Uma cópia pré-restauração foi gerada por segurança.",
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Falha ao restaurar backup: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

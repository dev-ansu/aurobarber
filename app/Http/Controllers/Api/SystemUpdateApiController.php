<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\License;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SystemUpdateApiController extends Controller
{
    /**
     * Caminho onde os manifests e pacotes .zip de release ficam armazenados no SaaS Master.
     */
    protected function getReleasesPath(): string
    {
        return storage_path('app/releases');
    }

    /**
     * Endpoint para verificação de novas atualizações via SaaS API.
     * 
     * GET|POST /api/v1/saas/updates/check
     */
    public function check(Request $request): JsonResponse
    {
        $licenseKey = trim((string) ($request->input('license_key') ?? $request->input('key') ?? ''));
        $currentVersion = trim((string) ($request->input('current_version') ?? $request->input('version') ?? '1.0.0'));

        // Validação da licença da escola solicitante
        if (! empty($licenseKey)) {
            /** @var License|null $license */
            $license = License::where('license_key', $licenseKey)
                ->orWhere('cnpj', $licenseKey)
                ->orWhere('notes', 'like', "%{$licenseKey}%")
                ->first();

            if (! $license) {
                // Tenta buscar por CNPJ puro
                $digits = preg_replace('/\D/', '', $licenseKey);
                if (strlen($digits) >= 8) {
                    $license = License::all()->first(function (License $lic) use ($digits): bool {
                        return preg_replace('/\D/', '', (string) $lic->cnpj) === $digits
                            || str_contains(preg_replace('/\D/', '', (string) $lic->notes), $digits);
                    });
                }
            }

            if ($license) {
                $status = strtolower((string) ($license->status ?? ''));
                $expiresAt = $license->expires_at ? Carbon::parse($license->expires_at) : null;
                $isExpired = $expiresAt && $expiresAt->endOfDay()->isPast();

                if ($status !== 'active' || $isExpired) {
                    return response()->json([
                        'has_update' => false,
                        'driver' => 'saas',
                        'error' => 'Licença do Aurosoft suspensa ou vencida. Regularize sua mensalidade para ter acesso às novas atualizações do sistema.',
                    ], 200);
                }
            }
        }

        $manifestPath = $this->getReleasesPath() . '/manifest.json';
        if (! File::exists($manifestPath)) {
            return response()->json([
                'has_update' => false,
                'driver' => 'saas',
                'current_version' => $currentVersion,
                'latest_version' => $currentVersion,
                'message' => 'Nenhuma atualização cadastrada no momento.',
            ]);
        }

        try {
            $manifest = json_decode((string) File::get($manifestPath), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            return response()->json([
                'has_update' => false,
                'driver' => 'saas',
                'error' => 'Falha ao interpretar manifesto de atualizações no servidor.',
            ], 500);
        }

        $latestVersion = (string) ($manifest['version'] ?? '1.0.0');
        $hasUpdate = version_compare($latestVersion, $currentVersion, '>');

        $downloadUrl = route('api.saas.updates.download', [
            'license_key' => $licenseKey,
            'version' => $latestVersion,
        ]);

        return response()->json([
            'has_update' => $hasUpdate,
            'driver' => 'saas',
            'current_version' => $currentVersion,
            'latest_version' => $latestVersion,
            'release_name' => $manifest['name'] ?? "Aurosoft v{$latestVersion}",
            'release_notes' => $manifest['notes'] ?? ['Melhorias de desempenho e estabilidade.'],
            'published_at' => $manifest['published_at'] ?? now()->format('d/m/Y'),
            'download_url' => $downloadUrl,
            'sha256' => $manifest['sha256'] ?? null,
            'size_bytes' => $manifest['size_bytes'] ?? null,
        ]);
    }

    /**
     * Endpoint para download seguro do pacote .zip de atualização.
     * 
     * GET /api/v1/saas/updates/download
     */
    public function download(Request $request): BinaryFileResponse|JsonResponse
    {
        $licenseKey = trim((string) ($request->input('license_key') ?? $request->input('key') ?? ''));
        $version = trim((string) ($request->input('version') ?? ''));

        if (empty($licenseKey)) {
            return response()->json(['error' => 'Chave de licença obrigatória para download de atualizações.'], 403);
        }

        // Validação da licença
        /** @var License|null $license */
        $license = License::where('license_key', $licenseKey)
            ->orWhere('cnpj', $licenseKey)
            ->orWhere('notes', 'like', "%{$licenseKey}%")
            ->first();

        if (! $license) {
            $digits = preg_replace('/\D/', '', $licenseKey);
            if (strlen($digits) >= 8) {
                $license = License::all()->first(function (License $lic) use ($digits): bool {
                    return preg_replace('/\D/', '', (string) $lic->cnpj) === $digits
                        || str_contains(preg_replace('/\D/', '', (string) $lic->notes), $digits);
                });
            }
        }

        if (! $license || strtolower((string) ($license->status ?? '')) !== 'active') {
            return response()->json(['error' => 'Licença não autorizada para download deste pacote.'], 403);
        }

        // Localiza o arquivo .zip
        $releasesDir = $this->getReleasesPath();
        $targetFile = ! empty($version)
            ? "{$releasesDir}/aurosoft-v{$version}.zip"
            : "{$releasesDir}/aurosoft-latest.zip";

        if (! File::exists($targetFile)) {
            // Tenta qualquer .zip na pasta como fallback
            $zipFiles = File::glob("{$releasesDir}/*.zip");
            if (! empty($zipFiles)) {
                $targetFile = end($zipFiles);
            } else {
                return response()->json(['error' => 'Pacote de atualização não encontrado no servidor.'], 404);
            }
        }

        return response()->download($targetFile, basename($targetFile), [
            'Content-Type' => 'application/zip',
            'Cache-Control' => 'no-cache, private',
        ]);
    }
}


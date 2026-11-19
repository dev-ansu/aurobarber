<?php

declare(strict_types=1);

namespace App\Services\Backup;

use App\Models\SaasBackupSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class GoogleDriveStorageService
{
    private ?string $cachedAccessToken = null;

    private ?int $tokenExpiresAt = null;

    /**
     * Verifica se a integração está configurada com os parâmetros mínimos no banco.
     */
    public function isConfigured(): bool
    {
        try {
            $this->credentials();

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Verifica se a integração está configurada e habilitada para uso ativo.
     */
    public function isEnabled(): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        try {
            $setting = SaasBackupSetting::current();

            return (bool) ($setting->google_drive_enabled ?? false);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Retorna as credenciais decifradas a partir do modelo SaasBackupSetting.
     *
     * @return array{client_id: string, client_secret: string, refresh_token: string, folder_id: string}
     */
    public function credentials(bool $requireFolder = true): array
    {
        $setting = SaasBackupSetting::current();
        if (! $setting) {
            throw new RuntimeException('As configurações de backup SaaS não foram inicializadas.');
        }

        $clientId = trim((string) ($setting->google_drive_client_id ?? ''));
        $clientSecret = $this->decryptValue($setting->google_drive_client_secret);
        $refreshToken = $this->decryptValue($setting->google_drive_refresh_token);
        $folderId = trim((string) ($setting->google_drive_folder_id ?? ''));

        if ($clientId === '' || $clientSecret === '' || $refreshToken === '') {
            throw new RuntimeException('As credenciais do Google Drive (Client ID, Secret ou Refresh Token) estão incompletas.');
        }

        if ($requireFolder && $folderId === '') {
            throw new RuntimeException('A pasta de destino do Google Drive não foi configurada.');
        }

        return [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'refresh_token' => $refreshToken,
            'folder_id' => $folderId,
        ];
    }

    /**
     * Obtém um token de acesso OAuth2 válido (reutiliza em memória durante sua validade).
     */
    public function accessToken(?array $credentials = null): string
    {
        if ($this->cachedAccessToken !== null && $this->tokenExpiresAt !== null && time() < ($this->tokenExpiresAt - 60)) {
            return $this->cachedAccessToken;
        }

        $creds = $credentials ?? $this->credentials(requireFolder: false);

        try {
            $response = Http::asForm()
                ->timeout(20)
                ->post('https://oauth2.googleapis.com/token', [
                    'client_id' => $creds['client_id'],
                    'client_secret' => $creds['client_secret'],
                    'refresh_token' => $creds['refresh_token'],
                    'grant_type' => 'refresh_token',
                ]);
        } catch (Throwable $e) {
            Log::error('[GoogleDriveStorageService] Falha de conexão OAuth2: '.$e->getMessage());
            throw new RuntimeException("Falha de conexão com os servidores de autenticação do Google: {$e->getMessage()}");
        }

        if (! $response->successful()) {
            $err = $response->json('error_description') ?: $response->json('error') ?: 'Resposta inválida do OAuth2';
            $exception = new RuntimeException("Não foi possível renovar o token do Google Drive (HTTP {$response->status()}): {$err}");
            Log::error('[GoogleDriveStorageService] '.$exception->getMessage());
            throw $exception;
        }

        $token = (string) $response->json('access_token');
        $expiresIn = (int) ($response->json('expires_in') ?? 3600);

        if ($token === '') {
            throw new RuntimeException('A API do Google não retornou um access_token válido.');
        }

        $this->cachedAccessToken = $token;
        $this->tokenExpiresAt = time() + $expiresIn;

        return $token;
    }

    /**
     * Cria uma pasta dedicada no Google Drive da conta autorizada.
     */
    public function createBackupFolder(): string
    {
        $token = $this->accessToken();
        $folderName = app()->environment('production') ? 'Backups AuroBarber SaaS' : 'Backups AuroBarber SaaS (Ambiente de Testes)';

        $response = Http::withToken($token)
            ->timeout(20)
            ->post('https://www.googleapis.com/drive/v3/files?supportsAllDrives=true', [
                'name' => $folderName,
                'mimeType' => 'application/vnd.google-apps.folder',
                'description' => 'Pasta de armazenamento seguro de backups criptografados do AuroBarber SaaS',
            ]);

        if (! $response->successful()) {
            $err = $response->json('error.message') ?: 'Falha ao criar pasta no Google Drive';
            throw new RuntimeException("Google Drive rejeitou criação de pasta (HTTP {$response->status()}): {$err}");
        }

        $folderId = (string) $response->json('id');
        if ($folderId === '') {
            throw new RuntimeException('Google Drive não retornou o identificador da pasta criada.');
        }

        return $folderId;
    }

    /**
     * Testa se a pasta configurada existe e é acessível no Google Drive.
     */
    public function testConnection(): void
    {
        $token = $this->accessToken();
        $creds = $this->credentials();

        $response = Http::withToken($token)
            ->timeout(20)
            ->get('https://www.googleapis.com/drive/v3/files/'.rawurlencode($creds['folder_id']), [
                'fields' => 'id,name,mimeType,trashed',
                'supportsAllDrives' => 'true',
            ]);

        if (! $response->successful()) {
            $apiMessage = (string) ($response->json('error.message') ?? 'Acesso recusado.');
            throw new RuntimeException("Google Drive recusou o acesso à pasta (HTTP {$response->status()}): {$apiMessage}");
        }

        if ($response->json('trashed') === true) {
            throw new RuntimeException('A pasta configurada no Google Drive está na lixeira.');
        }

        if ($response->json('mimeType') !== 'application/vnd.google-apps.folder') {
            throw new RuntimeException('O ID configurado não corresponde a uma pasta no Google Drive.');
        }
    }

    /**
     * Lista todos os backups criptografados armazenados na pasta do Google Drive.
     *
     * @return array<int, array{id: string, name: string, size: int, created_at: string, scope: string, is_global: bool}>
     */
    public function listDatabaseBackups(): array
    {
        $allFiles = $this->listAllFolderFiles();
        $dbBackups = [];

        foreach ($allFiles as $file) {
            $name = (string) ($file['name'] ?? '');

            // Formatos aceitos:
            // aurobarber-backup-20261119-164658.zip.enc
            // aurobarber-tenant-tenant_marinho-backup-20261119-164658.zip.enc
            // backup_GLOBAL_... ou backup_TENANT_... (compatibilidade)
            if (preg_match('/^(?:aurobarber|aurosoft)(?:-tenant-([a-zA-Z0-9_]+))?-(?:backup|pre-restore)-\d{8}-\d{6}\.zip\.enc$/i', $name, $matches)) {
                $tenantScope = ! empty($matches[1]) ? $matches[1] : null;
                $isGlobal = $tenantScope === null;
                $cleanScope = $isGlobal ? 'GLOBAL' : $tenantScope;

                $dbBackups[] = [
                    'id' => (string) $file['id'],
                    'name' => $name,
                    'size' => (int) ($file['size'] ?? 0),
                    'created_at' => (string) ($file['createdTime'] ?? ''),
                    'scope' => $cleanScope,
                    'is_global' => $isGlobal,
                ];
            } elseif (preg_match('/^backup_(GLOBAL|TENANT_[a-z0-9_]{1,55}|[a-z0-9_]{1,55})_[0-9]{4}-[0-9]{2}-[0-9]{2}_[0-9]{6}\.(?:dump|zip)\.enc$/i', $name, $matches)) {
                $rawScope = $matches[1];
                $isGlobal = strtoupper($rawScope) === 'GLOBAL';
                $cleanScope = $isGlobal ? 'GLOBAL' : preg_replace('/^TENANT_/i', '', $rawScope);

                $dbBackups[] = [
                    'id' => (string) $file['id'],
                    'name' => $name,
                    'size' => (int) ($file['size'] ?? 0),
                    'created_at' => (string) ($file['createdTime'] ?? ''),
                    'scope' => $cleanScope,
                    'is_global' => $isGlobal,
                ];
            }
        }

        return $dbBackups;
    }

    /**
     * Faz download direto de um arquivo do Google Drive para um caminho temporário com streaming.
     */
    public function downloadToFile(string $fileId, string $targetPath, int $timeoutSeconds = 1800): void
    {
        $token = $this->accessToken();
        $targetDir = dirname($targetPath);

        if (! is_dir($targetDir)) {
            @mkdir($targetDir, 0700, true);
        }

        try {
            $response = Http::withToken($token)
                ->timeout($timeoutSeconds)
                ->withOptions(['sink' => $targetPath])
                ->get('https://www.googleapis.com/drive/v3/files/'.rawurlencode($fileId), [
                    'alt' => 'media',
                    'supportsAllDrives' => 'true',
                ]);
        } catch (Throwable $e) {
            @unlink($targetPath);
            Log::error('[GoogleDriveStorageService] Falha no download do Drive: '.$e->getMessage());
            throw new RuntimeException("Falha de rede durante o download do backup no Google Drive: {$e->getMessage()}");
        }

        if (! $response->successful()) {
            @unlink($targetPath);
            $err = $response->body();
            $exception = new RuntimeException("Google Drive recusou o download do backup (HTTP {$response->status()}): {$err}");
            Log::error('[GoogleDriveStorageService] '.$exception->getMessage());
            throw $exception;
        }

        @chmod($targetPath, 0600);
    }

    /**
     * Realiza o upload de um arquivo local para o Google Drive usando o protocolo retomável em blocos (Resumable Uploads).
     *
     * @return array{id: string, name: string, size: int}
     */
    public function uploadResumable(
        string $filePath,
        string $driveFileName,
        string $description = 'Backup AuroBarber SaaS criptografado'
    ): array {
        if (! is_file($filePath) || ! is_readable($filePath)) {
            throw new RuntimeException("Arquivo não disponível para upload no Google Drive: {$filePath}");
        }

        $size = filesize($filePath);
        if (! is_int($size) || $size < 1) {
            throw new RuntimeException('O arquivo para envio ao Google Drive está vazio ou inválido.');
        }

        $creds = $this->credentials();
        $token = $this->accessToken($creds);

        $metadata = json_encode([
            'name' => $driveFileName,
            'parents' => [$creds['folder_id']],
            'description' => $description,
        ], JSON_THROW_ON_ERROR);

        try {
            $init = Http::withToken($token)
                ->withHeaders([
                    'Content-Type' => 'application/json; charset=UTF-8',
                    'X-Upload-Content-Type' => 'application/octet-stream',
                    'X-Upload-Content-Length' => (string) $size,
                ])
                ->timeout(30)
                ->withBody($metadata, 'application/json; charset=UTF-8')
                ->post('https://www.googleapis.com/upload/drive/v3/files?uploadType=resumable&supportsAllDrives=true');
        } catch (Throwable $e) {
            Log::error('[GoogleDriveStorageService] Falha ao iniciar sessão resumable: '.$e->getMessage());
            throw new RuntimeException("Falha ao iniciar sessão de upload no Google Drive: {$e->getMessage()}");
        }

        $sessionUri = $init->header('Location');
        if (! $init->successful() || ! is_string($sessionUri) || $sessionUri === '') {
            $err = $init->body();
            $exception = new RuntimeException("Google Drive recusou inicialização de upload (HTTP {$init->status()}): {$err}");
            Log::error('[GoogleDriveStorageService] '.$exception->getMessage());
            throw $exception;
        }

        return $this->sendChunks($sessionUri, $filePath, $size);
    }

    /**
     * Envia os blocos binários para a URI de sessão do Google Drive.
     */
    private function sendChunks(string $sessionUri, string $filePath, int $fileSize): array
    {
        $stream = fopen($filePath, 'rb');
        if (! is_resource($stream)) {
            throw new RuntimeException("Não foi possível abrir o arquivo para streaming de envio: {$filePath}");
        }

        $offset = 0;
        $chunkSize = 8 * 1024 * 1024; // Blocos de 8 MB
        $stalledAttempts = 0;

        try {
            while ($offset < $fileSize) {
                $length = min($chunkSize, $fileSize - $offset);
                fseek($stream, $offset);

                $chunk = '';
                while (strlen($chunk) < $length) {
                    $part = fread($stream, $length - strlen($chunk));
                    if ($part === false || $part === '') {
                        break;
                    }
                    $chunk .= $part;
                }

                if (strlen($chunk) !== $length) {
                    throw new RuntimeException('Leitura truncada durante envio do bloco ao Drive.');
                }

                $rangeHeader = 'bytes '.$offset.'-'.($offset + $length - 1).'/'.$fileSize;

                try {
                    $response = Http::withHeaders([
                        'Content-Type' => 'application/octet-stream',
                        'Content-Length' => (string) $length,
                        'Content-Range' => $rangeHeader,
                    ])->timeout(300)->withBody($chunk, 'application/octet-stream')->send('PUT', $sessionUri);
                } catch (Throwable) {
                    $response = null;
                }

                if ($response?->status() === 200 || $response?->status() === 201) {
                    fclose($stream);
                    $result = $response->json();
                    if (! is_array($result) || empty($result['id'])) {
                        throw new RuntimeException('Resposta do Google Drive não contém confirmação de arquivo criado.');
                    }

                    return [
                        'id' => (string) $result['id'],
                        'name' => (string) ($result['name'] ?? ''),
                        'size' => $fileSize,
                    ];
                }

                if ($response?->status() === 308) {
                    $acceptedOffset = $this->parseAcceptedOffset($response->header('Range'));
                } else {
                    $acceptedOffset = $this->queryUploadOffset($sessionUri, $fileSize);
                }

                if ($acceptedOffset <= $offset) {
                    $stalledAttempts++;
                    if ($stalledAttempts >= 5) {
                        $exception = new RuntimeException("Upload do backup interrompido: o envio estagnou no byte {$offset} após 5 tentativas.");
                        Log::error('[GoogleDriveStorageService] '.$exception->getMessage());
                        throw $exception;
                    }

                    continue;
                }

                $offset = $acceptedOffset;
                $stalledAttempts = 0;
            }

            $finalize = Http::withHeaders([
                'Content-Length' => '0',
                'Content-Range' => 'bytes */'.$fileSize,
            ])->timeout(60)->withBody('', 'application/octet-stream')->send('PUT', $sessionUri);

            fclose($stream);

            if ($finalize->status() === 200 || $finalize->status() === 201) {
                $result = $finalize->json();

                return [
                    'id' => (string) ($result['id'] ?? ''),
                    'name' => (string) ($result['name'] ?? ''),
                    'size' => $fileSize,
                ];
            }

            throw new RuntimeException("Upload finalizado mas não confirmado pelo Google Drive (HTTP {$finalize->status()}).");
        } catch (Throwable $e) {
            if (is_resource($stream)) {
                fclose($stream);
            }
            throw $e;
        }
    }

    /**
     * Remove um arquivo específico do Google Drive.
     */
    public function deleteFile(string $fileId): bool
    {
        $token = $this->accessToken();
        $response = Http::withToken($token)
            ->timeout(20)
            ->delete('https://www.googleapis.com/drive/v3/files/'.rawurlencode($fileId).'?supportsAllDrives=true');

        return $response->successful() || $response->status() === 404;
    }

    /**
     * Aplica a política de retenção aos backups armazenados no Google Drive,
     * removendo arquivos com mais de $days dias, preservando o mínimo por escopo.
     */
    public function pruneDatabaseBackups(int $days = 14, int $keepMinimumPerScope = 3): int
    {
        if (! $this->isEnabled()) {
            return 0;
        }

        try {
            $backups = collect($this->listDatabaseBackups());
            if ($backups->isEmpty()) {
                return 0;
            }

            $threshold = now()->subDays($days);
            $deletedCount = 0;

            $grouped = $backups->groupBy('scope');

            foreach ($grouped as $scope => $items) {
                $sorted = $items->sortByDesc('created_at')->values();
                $candidates = $sorted->slice($keepMinimumPerScope);

                foreach ($candidates as $backup) {
                    if (empty($backup['created_at'])) {
                        continue;
                    }

                    $createdAt = Carbon::parse($backup['created_at']);
                    if ($createdAt->lt($threshold)) {
                        if (! empty($backup['id']) && $this->deleteFile((string) $backup['id'])) {
                            $deletedCount++;
                        }
                    }
                }
            }

            return $deletedCount;
        } catch (Throwable $e) {
            Log::warning('[GoogleDriveStorageService] Falha ao expurgar backups antigos no Google Drive: '.$e->getMessage());

            return 0;
        }
    }

    /**
     * Varre todos os arquivos da pasta do Google Drive com paginação completa.
     *
     * @return array<int, array{id: string, name: string, size?: int, createdTime?: string}>
     */
    private function listAllFolderFiles(): array
    {
        $creds = $this->credentials();
        $token = $this->accessToken($creds);
        $files = [];
        $pageToken = null;
        $pageLimit = 30;
        $pagesRead = 0;

        do {
            $query = [
                'q' => "'{$creds['folder_id']}' in parents and trashed = false",
                'fields' => 'nextPageToken,files(id,name,size,createdTime)',
                'orderBy' => 'createdTime desc',
                'pageSize' => 100,
                'supportsAllDrives' => 'true',
                'includeItemsFromAllDrives' => 'true',
            ];

            if ($pageToken !== null) {
                $query['pageToken'] = $pageToken;
            }

            try {
                $response = Http::withToken($token)
                    ->timeout(30)
                    ->get('https://www.googleapis.com/drive/v3/files', $query);
            } catch (Throwable $e) {
                Log::error('[GoogleDriveStorageService] Falha de rede ao listar arquivos: '.$e->getMessage());
                throw new RuntimeException("Falha de rede ao listar arquivos no Google Drive: {$e->getMessage()}");
            }

            if (! $response->successful()) {
                $err = $response->json('error.message') ?: 'Erro desconhecido';
                $exception = new RuntimeException("Google Drive rejeitou listagem de arquivos (HTTP {$response->status()}): {$err}");
                Log::error('[GoogleDriveStorageService] '.$exception->getMessage());
                throw $exception;
            }

            $items = $response->json('files', []);
            if (is_array($items)) {
                foreach ($items as $item) {
                    if (is_array($item) && ! empty($item['id']) && ! empty($item['name'])) {
                        $files[] = $item;
                    }
                }
            }

            $pageToken = $response->json('nextPageToken');
            $pagesRead++;
        } while (is_string($pageToken) && $pageToken !== '' && $pagesRead < $pageLimit);

        return $files;
    }

    private function parseAcceptedOffset(?string $rangeHeader): int
    {
        if (is_string($rangeHeader) && preg_match('/bytes=0-([0-9]+)/', $rangeHeader, $matches)) {
            return (int) $matches[1] + 1;
        }

        return 0;
    }

    private function queryUploadOffset(string $sessionUri, int $fileSize): int
    {
        try {
            $response = Http::withHeaders([
                'Content-Length' => '0',
                'Content-Range' => 'bytes */'.$fileSize,
            ])->timeout(30)->send('PUT', $sessionUri);

            if ($response->status() === 308) {
                return $this->parseAcceptedOffset($response->header('Range'));
            }
        } catch (Throwable) {
        }

        return 0;
    }

    private function decryptValue(?string $value): string
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return '';
        }

        try {
            return Crypt::decryptString($raw);
        } catch (Throwable) {
            return $raw;
        }
    }
}

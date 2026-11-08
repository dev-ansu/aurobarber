<?php

namespace App\Services;

use App\Models\School;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GoogleDriveBackupService
{
    protected ?School $school = null;
    protected ?string $clientId = null;
    protected ?string $clientSecret = null;
    protected ?string $refreshToken = null;
    protected ?string $folderId = null;

    public function __construct()
    {
        $this->resolveCredentials();
    }

    public function resolveCredentials(): void
    {
        $school = null;
        try {
            $school = Filament::getTenant();
        } catch (\Throwable) {
            $school = null;
        }

        // Se a unidade ativa não possui configuração própria, busca na Matriz ou primeira unidade configurada
        if (! $school instanceof School || blank($school->google_drive_client_id)) {
            $school = School::query()
                ->where('is_headquarters', true)
                ->whereNotNull('google_drive_client_id')
                ->where('google_drive_client_id', '!=', '')
                ->first()
                ?? School::query()
                    ->whereNotNull('google_drive_client_id')
                    ->where('google_drive_client_id', '!=', '')
                    ->orderBy('id')
                    ->first()
                ?? School::getHeadquarters();
        }

        $this->school = $school;

        // 1. Tenta carregar as credenciais da escola ativa ou herdada da Matriz
        if ($school && filled($school->google_drive_client_id)) {
            $this->clientId = $school->google_drive_client_id;
            try {
                $this->clientSecret = filled($school->google_drive_client_secret)
                    ? Crypt::decryptString($school->google_drive_client_secret)
                    : null;
            } catch (\Throwable) {
                $this->clientSecret = $school->google_drive_client_secret;
            }

            try {
                $this->refreshToken = filled($school->google_drive_refresh_token)
                    ? Crypt::decryptString($school->google_drive_refresh_token)
                    : null;
            } catch (\Throwable) {
                $this->refreshToken = $school->google_drive_refresh_token;
            }

            $this->folderId = $school->google_drive_folder_id;
        }

        // 2. Fallback para variáveis de ambiente (.env) caso a escola não tenha configurado no painel
        $this->clientId ??= config('services.google_drive.client_id', env('GOOGLE_DRIVE_CLIENT_ID'));
        $this->clientSecret ??= config('services.google_drive.client_secret', env('GOOGLE_DRIVE_CLIENT_SECRET'));
        $this->refreshToken ??= config('services.google_drive.refresh_token', env('GOOGLE_DRIVE_REFRESH_TOKEN'));
        $this->folderId ??= config('services.google_drive.folder_id', env('GOOGLE_DRIVE_FOLDER_ID'));
    }

    public function isConfigured(): bool
    {
        return filled($this->clientId)
            && filled($this->clientSecret)
            && filled($this->refreshToken)
            && filled($this->folderId);
    }

    public function isEnabled(): bool
    {
        if ($this->school && isset($this->school->google_drive_backup_enabled)) {
            return (bool) $this->school->google_drive_backup_enabled;
        }

        return true;
    }

    /**
     * Obtém um novo access_token a partir do refresh_token permanente
     */
    public function getAccessToken(): string
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Google Drive não está configurado. Verifique as credenciais no .env.');
        }

        $response = Http::asForm()->timeout(20)->post('https://oauth2.googleapis.com/token', [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $this->refreshToken,
            'grant_type' => 'refresh_token',
        ]);

        if (! $response->successful()) {
            $error = $response->json('error_description') ?? $response->body();
            Log::error('Erro ao autenticar com Google Drive API', ['response' => $error]);
            throw new RuntimeException('Falha na autenticação do Google Drive: ' . $error);
        }

        $token = $response->json('access_token');
        if (! $token) {
            throw new RuntimeException('Access Token não retornado pelo Google.');
        }

        return $token;
    }

    /**
     * Envia um arquivo de backup local para a pasta configurada no Google Drive
     */
    public function uploadFile(string $filePath, ?string $customFilename = null): array
    {
        if (! File::exists($filePath)) {
            throw new RuntimeException("Arquivo de backup '{$filePath}' não encontrado.");
        }

        $filename = $customFilename ?: basename($filePath);
        $fileSize = File::size($filePath);
        $accessToken = $this->getAccessToken();

        $metadata = [
            'name' => $filename,
            'parents' => [$this->folderId],
            'description' => 'Backup automático de segurança do Aurosoft — ' . now()->format('d/m/Y H:i:s'),
        ];

        // Upload Multipart padrão com Content-Type multipart/related
        $boundary = '-------AurosoftBackupBoundary' . uniqid();
        $delimiter = "\r\n--" . $boundary . "\r\n";
        $closeDelim = "\r\n--" . $boundary . "--";

        $metaPart = $delimiter .
            "Content-Type: application/json; charset=UTF-8\r\n\r\n" .
            json_encode($metadata) .
            $delimiter .
            "Content-Type: application/zip\r\n\r\n";

        $tempPayload = tempnam(sys_get_temp_dir(), 'gdrive_upload_');
        $fileStream = fopen($filePath, 'rb');
        if (! $fileStream) {
            throw new RuntimeException("Não foi possível ler o arquivo '{$filePath}'.");
        }

        $tempHandle = fopen($tempPayload, 'wb');
        fwrite($tempHandle, $metaPart);
        while (! feof($fileStream)) {
            fwrite($tempHandle, fread($fileStream, 1048576)); // 1MB por bloco
        }
        fclose($fileStream);
        fwrite($tempHandle, $closeDelim);
        fclose($tempHandle);

        $payloadData = file_get_contents($tempPayload);
        @unlink($tempPayload);

        $ch = curl_init('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 600); // 10 minutos para grandes backups
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payloadData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: multipart/related; boundary=' . $boundary,
        ]);

        $responseBody = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            Log::error('Erro de conexão ao enviar backup para Google Drive', ['error' => $curlError]);
            throw new RuntimeException('Erro de rede ao conectar com o Google Drive: ' . $curlError);
        }

        if ($httpCode !== 200 && $httpCode !== 201) {
            Log::error('Falha na resposta do Google Drive', ['code' => $httpCode, 'body' => $responseBody]);
            throw new RuntimeException("Falha no upload para o Google Drive (HTTP {$httpCode}): {$responseBody}");
        }

        $result = json_decode($responseBody, true) ?: [];

        Log::info('Backup enviado com sucesso para o Google Drive', [
            'filename' => $filename,
            'file_id' => $result['id'] ?? null,
            'size' => $fileSize,
        ]);

        return [
            'success' => true,
            'file_id' => $result['id'] ?? null,
            'name' => $result['name'] ?? $filename,
            'size' => $fileSize,
        ];
    }

    /**
     * Lista os últimos backups armazenados na pasta do Google Drive
     */
    public function listDriveBackups(int $limit = 10): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        try {
            $accessToken = $this->getAccessToken();
            $query = sprintf("'%s' in parents and trashed = false and name contains 'aurosoft'", $this->folderId);

            $response = Http::withToken($accessToken)
                ->timeout(15)
                ->get('https://www.googleapis.com/drive/v3/files', [
                    'q' => $query,
                    'fields' => 'files(id, name, size, createdTime, webViewLink)',
                    'orderBy' => 'createdTime desc',
                    'pageSize' => $limit,
                ]);

            if (! $response->successful()) {
                return [];
            }

            return $response->json('files') ?? [];
        } catch (\Throwable $e) {
            Log::warning('Não foi possível listar backups do Google Drive: ' . $e->getMessage());

            return [];
        }
    }
}

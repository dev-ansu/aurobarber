<?php

declare(strict_types=1);

namespace App\Services\Backup;

use RuntimeException;

class BackupEncryptionService
{
    public const MAGIC_PREFIX = "AUROBARBERENC1\n";

    public const LEGACY_PREFIX = "AUROSAASENC1\n";

    public const DEFAULT_KEY_CONTEXT = 'aurobarber-saas-google-drive-backup-v1';

    /**
     * Criptografa um arquivo local em streaming usando libsodium secretstream (XChaCha20-Poly1305).
     *
     * @return array{encrypted_path: string, sha256: string, size_bytes: int, encrypted_size_bytes: int}
     */
    public function encryptFile(
        string $filePath,
        ?string $destinationPath = null,
        string $keyContext = self::DEFAULT_KEY_CONTEXT
    ): array {
        if (! extension_loaded('sodium')) {
            throw new RuntimeException('A extensão PHP sodium é necessária para criptografar backups.');
        }

        if (! is_file($filePath) || ! is_readable($filePath)) {
            throw new RuntimeException("O arquivo de origem para criptografia não existe ou não pode ser lido: {$filePath}");
        }

        $appKey = $this->resolveRawAppKey();
        $key = hash_hkdf('sha256', $appKey, SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_KEYBYTES, $keyContext);

        $targetPath = $destinationPath;
        if ($targetPath === null) {
            $tmpDir = storage_path('app/private/tmp');
            if (! is_dir($tmpDir)) {
                @mkdir($tmpDir, 0700, true);
            }
            $targetPath = tempnam($tmpDir, '.saas-backup-encrypted-');
            if ($targetPath === false) {
                throw new RuntimeException('Não foi possível gerar arquivo temporário para criptografia.');
            }
        }

        [$state, $header] = sodium_crypto_secretstream_xchacha20poly1305_init_push($key);

        $input = fopen($filePath, 'rb');
        $output = fopen($targetPath, 'wb');

        if (! is_resource($input) || ! is_resource($output)) {
            if (is_resource($input)) {
                fclose($input);
            }
            if (is_resource($output)) {
                fclose($output);
            }
            if ($destinationPath === null && is_string($targetPath)) {
                @unlink($targetPath);
            }
            throw new RuntimeException('Não foi possível abrir os arquivos para streaming de criptografia.');
        }

        $hashContext = hash_init('sha256');
        $plainSize = 0;

        try {
            fwrite($output, self::MAGIC_PREFIX.$header);

            do {
                $chunk = fread($input, 1024 * 1024);
                if ($chunk === false) {
                    throw new RuntimeException('Falha na leitura do arquivo durante a criptografia.');
                }

                $chunkLen = strlen($chunk);
                $plainSize += $chunkLen;
                hash_update($hashContext, $chunk);

                $final = feof($input);
                $tag = $final
                    ? SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL
                    : SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_MESSAGE;

                $ciphertext = sodium_crypto_secretstream_xchacha20poly1305_push($state, $chunk, '', $tag);
                $cipherLen = strlen($ciphertext);

                if (fwrite($output, pack('N', $cipherLen).$ciphertext) !== 4 + $cipherLen) {
                    throw new RuntimeException('Falha ao gravar bloco criptografado no destino.');
                }
            } while (! $final);
        } catch (\Throwable $exception) {
            fclose($input);
            fclose($output);
            if ($destinationPath === null) {
                @unlink($targetPath);
            }
            throw $exception;
        }

        fclose($input);
        fclose($output);
        @chmod($targetPath, 0600);

        $encryptedSize = (int) @filesize($targetPath);
        $finalHash = hash_final($hashContext);

        return [
            'encrypted_path' => $targetPath,
            'sha256' => $finalHash,
            'size_bytes' => $plainSize,
            'encrypted_size_bytes' => $encryptedSize,
        ];
    }

    /**
     * Descriptografa um arquivo local criptografado em streaming, autenticando cada bloco e validando integridade.
     *
     * @return array{decrypted_path: string, sha256: string, size_bytes: int}
     */
    public function decryptFile(
        string $encryptedPath,
        string $destinationPath,
        string $keyContext = self::DEFAULT_KEY_CONTEXT,
        ?string $expectedSha256 = null
    ): array {
        if (! extension_loaded('sodium')) {
            throw new RuntimeException('A extensão PHP sodium é necessária para descriptografar backups.');
        }

        if (! is_file($encryptedPath) || ! is_readable($encryptedPath)) {
            throw new RuntimeException("O arquivo criptografado não foi encontrado ou não pode ser lido: {$encryptedPath}");
        }

        $appKey = $this->resolveRawAppKey();
        $key = hash_hkdf('sha256', $appKey, SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_KEYBYTES, $keyContext);

        $destDir = dirname($destinationPath);
        if (! is_dir($destDir)) {
            @mkdir($destDir, 0700, true);
        }

        $input = fopen($encryptedPath, 'rb');
        $output = fopen($destinationPath, 'wb');

        if (! is_resource($input) || ! is_resource($output)) {
            if (is_resource($input)) {
                fclose($input);
            }
            if (is_resource($output)) {
                fclose($output);
            }
            throw new RuntimeException("Não foi possível abrir arquivos para descriptografia em {$destinationPath}.");
        }

        @chmod($destinationPath, 0600);
        $hashContext = hash_init('sha256');
        $plainSize = 0;

        try {
            $prefix = $this->readExact($input, strlen(self::MAGIC_PREFIX));
            if ($prefix !== self::MAGIC_PREFIX && $prefix !== self::LEGACY_PREFIX) {
                throw new RuntimeException('O arquivo não possui o cabeçalho mágico de backup criptografado do AuroBarber.');
            }

            $header = $this->readExact($input, SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_HEADERBYTES);
            $state = sodium_crypto_secretstream_xchacha20poly1305_init_pull($header, $key);
            $finished = false;

            while (! $finished) {
                $lengthBytes = fread($input, 4);
                if ($lengthBytes === false || strlen($lengthBytes) !== 4) {
                    throw new RuntimeException('O arquivo criptografado terminou inesperadamente antes do marcador final.');
                }

                $length = unpack('Nlength', $lengthBytes)['length'];
                if ($length < SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_ABYTES || $length > 1024 * 1024 + SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_ABYTES) {
                    throw new RuntimeException('O arquivo criptografado contém um bloco de tamanho inválido.');
                }

                $ciphertext = $this->readExact($input, $length);
                $decrypted = sodium_crypto_secretstream_xchacha20poly1305_pull($state, $ciphertext);

                if ($decrypted === false) {
                    throw new RuntimeException('A autenticação do bloco criptografado falhou. O backup pode estar corrompido ou foi cifrado com outro APP_KEY.');
                }

                [$plainChunk, $tag] = $decrypted;
                $chunkLen = strlen($plainChunk);
                $plainSize += $chunkLen;
                hash_update($hashContext, $plainChunk);

                if (fwrite($output, $plainChunk) !== $chunkLen) {
                    throw new RuntimeException('Falha na gravação dos dados descriptografados.');
                }

                $finished = $tag === SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL;
            }

            if (fread($input, 1) !== '') {
                throw new RuntimeException('O arquivo contém bytes adicionais após o bloco final autenticado.');
            }
        } catch (\Throwable $exception) {
            fclose($input);
            fclose($output);
            @unlink($destinationPath);
            throw $exception;
        }

        fclose($input);
        fclose($output);

        $calculatedHash = hash_final($hashContext);

        if ($expectedSha256 !== null && ! hash_equals($expectedSha256, $calculatedHash)) {
            @unlink($destinationPath);
            throw new RuntimeException("Disparidade no hash de integridade SHA-256 do backup. Esperado: {$expectedSha256}, Calculado: {$calculatedHash}.");
        }

        return [
            'decrypted_path' => $destinationPath,
            'sha256' => $calculatedHash,
            'size_bytes' => $plainSize,
        ];
    }

    /**
     * Extrai a chave bruta decodificada do APP_KEY da aplicação.
     */
    public function resolveRawAppKey(): string
    {
        $appKey = (string) config('app.key');
        if (str_starts_with($appKey, 'base64:')) {
            $decoded = base64_decode(substr($appKey, 7), true);
            $appKey = is_string($decoded) ? $decoded : '';
        }

        if ($appKey === '') {
            throw new RuntimeException('APP_KEY da aplicação não está configurada; não é possível operar criptografia de backup.');
        }

        return $appKey;
    }

    private function readExact($stream, int $length): string
    {
        $buffer = '';
        while (strlen($buffer) < $length && ! feof($stream)) {
            $chunk = fread($stream, $length - strlen($buffer));
            if ($chunk === false || $chunk === '') {
                break;
            }
            $buffer .= $chunk;
        }

        if (strlen($buffer) !== $length) {
            throw new RuntimeException('Leitura truncada do fluxo de dados criptografados.');
        }

        return $buffer;
    }
}

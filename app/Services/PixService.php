<?php

namespace App\Services;

use App\Models\Installment;
use App\Models\School;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class PixService
{
    /**
     * Gera os dados completos do Pix (Copia e Cola + QR Code SVG) para uma parcela de contrato.
     *
     * @return array<string, mixed>|null Retorna null se a escola não possuir chave Pix cadastrada
     */
    public function generateForInstallment(Installment $installment, School|array|null $school = null): ?array
    {
        if ($school === null) {
            $school = $installment->school
                ?? $installment->contract?->school
                ?? (class_exists(\Filament\Facades\Filament::class) ? \Filament\Facades\Filament::getTenant() : null)
                ?? School::getActiveSchool()
                ?? School::query()->first();
        }

        $pixConfig = is_array($school)
            ? $school
            : ($school?->resolvePixSettings() ?? []);

        $pixEnabled = (bool) ($pixConfig['pix_enabled'] ?? true);

        if (! $pixEnabled) {
            return null;
        }

        $pixKey = $pixConfig['pix_key'] ?? null;
        if (! is_string($pixKey) || trim($pixKey) === '') {
            return null;
        }

        $merchantName = $pixConfig['pix_merchant_name'] ?: 'ESCOLA';
        $merchantCity = $pixConfig['pix_merchant_city'] ?: 'BRASIL';
        $instructions = $pixConfig['pix_instructions'] ?? null;

        $amount = (float) ($installment->amount_paid > 0 ? $installment->amount_paid : $installment->amount);

        // TXID formatado: alfanumérico sem espaços, máx 25 chars (Ex: CTR001P01)
        $contractNum = preg_replace('/[^A-Za-z0-9]/', '', (string) ($installment->contract?->contract_number ?? $installment->contract_id));
        $instNum = str_pad((string) $installment->installment_number, 2, '0', STR_PAD_LEFT);
        $txid = substr("CTR{$contractNum}P{$instNum}", 0, 25);
        if ($txid === '') {
            $txid = '***';
        }

        $description = "Parcela {$instNum} Contrato {$installment->contract?->contract_number}";

        $copyPaste = $this->generatePayload(
            pixKey: $pixKey,
            merchantName: $merchantName,
            merchantCity: $merchantCity,
            amount: $amount,
            txid: $txid,
            description: $description,
        );

        $qrCodeDataUri = $this->generateQrCodeDataUri($copyPaste);

        return [
            'pix_key' => $pixKey,
            'merchant_name' => $this->sanitizeText($merchantName, 25),
            'merchant_city' => $this->sanitizeText($merchantCity, 15),
            'amount' => $amount,
            'txid' => $txid,
            'instructions' => $instructions,
            'copy_paste' => $copyPaste,
            'qr_code_uri' => $qrCodeDataUri,
        ];
    }

    /**
     * Gera o código estático BR Code (Pix Copia e Cola) conforme especificação oficial do Banco Central.
     */
    public function generatePayload(
        string $pixKey,
        string $merchantName,
        string $merchantCity,
        ?float $amount = null,
        ?string $txid = '***',
        ?string $description = null,
    ): string {
        $cleanKey = $this->normalizeKey($pixKey);
        $cleanName = $this->sanitizeText($merchantName, 25);
        $cleanCity = $this->sanitizeText($merchantCity, 15);
        $cleanTxid = $this->sanitizeTxid($txid);

        // Tag 26: Merchant Account Information
        $merchantInfo = $this->formatTlv('00', 'br.gov.bcb.pix')
            .$this->formatTlv('01', $cleanKey);

        if ($description) {
            $merchantInfo .= $this->formatTlv('02', $this->sanitizeText($description, 40));
        }

        // Tag 62: Additional Data Field (TXID)
        $additionalData = $this->formatTlv('05', $cleanTxid);

        // Montagem do payload EMV
        $payload = $this->formatTlv('00', '01')
            .$this->formatTlv('26', $merchantInfo)
            .$this->formatTlv('52', '0000')
            .$this->formatTlv('53', '986');

        if ($amount !== null && $amount > 0) {
            $payload .= $this->formatTlv('54', number_format($amount, 2, '.', ''));
        }

        $payload .= $this->formatTlv('58', 'BR')
            .$this->formatTlv('59', $cleanName)
            .$this->formatTlv('60', $cleanCity)
            .$this->formatTlv('62', $additionalData);

        // Tag 63: CRC16
        $crc = $this->calculateCrc16($payload.'6304');

        return $payload.'6304'.$crc;
    }

    /**
     * Renderiza o QR Code no formato Data URI SVG (ótimo para impressão nítida e tela).
     */
    public function generateQrCodeDataUri(string $payload): string
    {
        if (class_exists(QRCode::class) && class_exists(QROptions::class)) {
            try {
                $options = new QROptions([
                    'outputType' => defined(QRCode::class.'::OUTPUT_MARKUP_SVG') ? constant(QRCode::class.'::OUTPUT_MARKUP_SVG') : 'svg',
                    'outputInterface' => class_exists(QRMarkupSVG::class) ? QRMarkupSVG::class : null,
                    'svgUseFill' => true,
                    'addQuietzone' => true,
                ]);

                return (new QRCode($options))->render($payload);
            } catch (\Throwable) {
                // Fallback abaixo
            }
        }

        $encoded = rawurlencode($payload);

        return "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data={$encoded}";
    }

    /**
     * Formata um campo no padrão TLV (Tag - Length - Value).
     */
    private function formatTlv(string $id, string $value): string
    {
        $length = mb_strlen($value, 'UTF-8');

        return sprintf('%02s%02d%s', $id, $length, $value);
    }

    /**
     * Calcula o CRC16-CCITT (polinômio 0x1021, valor inicial 0xFFFF).
     */
    private function calculateCrc16(string $payload): string
    {
        $polynomial = 0x1021;
        $crc = 0xFFFF;
        $length = strlen($payload);

        for ($i = 0; $i < $length; $i++) {
            $crc ^= (ord($payload[$i]) << 8);
            for ($j = 0; $j < 8; $j++) {
                if (($crc & 0x8000) !== 0) {
                    $crc = (($crc << 1) ^ $polynomial) & 0xFFFF;
                } else {
                    $crc = ($crc << 1) & 0xFFFF;
                }
            }
        }

        return strtoupper(sprintf('%04x', $crc));
    }

    /**
     * Remove acentos e caracteres especiais conforme exigido pelo BACEN.
     */
    private function sanitizeText(string $text, int $maxLength): string
    {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
        $clean = preg_replace('/[^A-Za-z0-9 ]/', '', $ascii) ?: 'ESCOLA';
        $trimmed = strtoupper(trim($clean));

        return mb_substr($trimmed !== '' ? $trimmed : 'ESCOLA', 0, $maxLength, 'UTF-8');
    }

    private function sanitizeTxid(?string $txid): string
    {
        if (! $txid || $txid === '***') {
            return '***';
        }

        $clean = preg_replace('/[^A-Za-z0-9]/', '', $txid);

        return mb_substr($clean !== '' ? $clean : '***', 0, 25, 'UTF-8');
    }

    private function normalizeKey(string $key): string
    {
        $trimmed = trim($key);

        // Se for e-mail
        if (filter_var($trimmed, FILTER_VALIDATE_EMAIL)) {
            return strtolower($trimmed);
        }

        // Se for número de telefone
        if (str_starts_with($trimmed, '+')) {
            return '+'.preg_replace('/\D/', '', $trimmed);
        }

        $digits = preg_replace('/\D/', '', $trimmed);

        // CPF (11 dígitos) ou CNPJ (14 dígitos)
        if (strlen($digits) === 11 || strlen($digits) === 14) {
            return $digits;
        }

        // Celular nacional sem DDI (10 ou 11 dígitos começando com DDD)
        if (strlen($digits) === 10 || (strlen($digits) === 11 && $digits[2] === '9')) {
            return '+55'.$digits;
        }

        // Chave aleatória (EVP) ou outro formato
        return $trimmed;
    }
}

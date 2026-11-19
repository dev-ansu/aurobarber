<?php

declare(strict_types=1);

namespace App\Services;

use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class PixService
{
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
     * Renderiza o QR Code no formato Data URI SVG.
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

    private function formatTlv(string $id, string $value): string
    {
        $length = mb_strlen($value, 'UTF-8');

        return sprintf('%02s%02d%s', $id, $length, $value);
    }

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

    private function sanitizeText(string $text, int $maxLength): string
    {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
        $clean = preg_replace('/[^A-Za-z0-9 ]/', '', $ascii) ?: 'AUROBARBER';
        $trimmed = strtoupper(trim($clean));

        return mb_substr($trimmed !== '' ? $trimmed : 'AUROBARBER', 0, $maxLength, 'UTF-8');
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

        if (filter_var($trimmed, FILTER_VALIDATE_EMAIL)) {
            return strtolower($trimmed);
        }

        if (str_starts_with($trimmed, '+')) {
            return '+'.preg_replace('/\D/', '', $trimmed);
        }

        $digits = preg_replace('/\D/', '', $trimmed);

        if (strlen($digits) === 11 || strlen($digits) === 14) {
            return $digits;
        }

        if (strlen($digits) === 10 || (strlen($digits) === 11 && $digits[2] === '9')) {
            return '+55'.$digits;
        }

        return $trimmed;
    }
}

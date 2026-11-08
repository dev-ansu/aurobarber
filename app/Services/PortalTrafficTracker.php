<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PortalTrafficEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PortalTrafficTracker
{
    /**
     * Registra um evento de tráfego (visita, clique de CTA, WhatsApp ou envio de lead) de forma segura.
     */
    public static function log(
        Request $request,
        string $scope = 'aurosoft_landing',
        string $eventType = 'page_view',
        ?int $schoolId = null,
        ?int $formationId = null,
        ?string $eventLabel = null
    ): ?PortalTrafficEvent {
        try {
            $ip = $request->ip() ?? '127.0.0.1';
            $userAgent = (string) $request->userAgent();

            // Hash anônimo diário em conformidade com a LGPD
            $visitorHash = hash('sha256', $ip . '|' . $userAgent . '|' . date('Y-m-d'));

            // Detecção de tipo de dispositivo
            $uaLower = strtolower($userAgent);
            $deviceType = 'desktop';
            if (str_contains($uaLower, 'ipad') || str_contains($uaLower, 'tablet')) {
                $deviceType = 'tablet';
            } elseif (str_contains($uaLower, 'mobile') || str_contains($uaLower, 'android') || str_contains($uaLower, 'iphone')) {
                $deviceType = 'mobile';
            }

            // Detecção inteligente de UTM e Origem
            $utmSource = $request->input('utm_source') ?? $request->query('utm_source');
            $referrer = (string) $request->header('referer');

            if (! $utmSource && $referrer) {
                $refLower = strtolower($referrer);
                if (str_contains($refLower, 'instagram')) {
                    $utmSource = 'instagram';
                } elseif (str_contains($refLower, 'facebook') || str_contains($refLower, 'fb.me')) {
                    $utmSource = 'facebook';
                } elseif (str_contains($refLower, 'google')) {
                    $utmSource = 'google';
                } elseif (str_contains($refLower, 'whatsapp') || str_contains($refLower, 'wa.me')) {
                    $utmSource = 'whatsapp';
                } elseif (str_contains($refLower, 'tiktok')) {
                    $utmSource = 'tiktok';
                } elseif (str_contains($refLower, 'youtube') || str_contains($refLower, 'youtu.be')) {
                    $utmSource = 'youtube';
                } else {
                    $parsedHost = parse_url($referrer, PHP_URL_HOST);
                    if ($parsedHost && ! str_contains($parsedHost, 'aurosoft.com.br')) {
                        $utmSource = 'referral:' . $parsedHost;
                    }
                }
            }

            if (! $utmSource) {
                $utmSource = 'direto';
            }

            $utmCampaign = $request->input('utm_campaign') ?? $request->query('utm_campaign');

            return PortalTrafficEvent::create([
                'scope' => $scope,
                'school_id' => $schoolId,
                'formation_id' => $formationId,
                'event_type' => $eventType,
                'event_label' => $eventLabel ? substr($eventLabel, 0, 150) : null,
                'visitor_hash' => $visitorHash,
                'device_type' => $deviceType,
                'utm_source' => substr((string) $utmSource, 0, 100),
                'utm_campaign' => $utmCampaign ? substr((string) $utmCampaign, 0, 100) : null,
                'referrer' => $referrer ? substr($referrer, 0, 500) : null,
                'page_url' => substr($request->fullUrl(), 0, 500),
                'ip_address' => $ip,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::debug('Erro não-bloqueante no rastreamento de tráfego: ' . $e->getMessage());
            return null;
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Models\SecurityAccessRule;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SecurityAccessRuleService
{
    /**
     * Verifica se o endereço IP ou usuário atual consta na Blacklist ativa.
     */
    public function findBlacklistMatch(?string $ip, ?User $user = null): ?SecurityAccessRule
    {
        if (empty($ip) && ! $user) {
            return null;
        }

        $rule = SecurityAccessRule::query()
            ->blacklist()
            ->active()
            ->where(function ($query) use ($ip, $user): void {
                if (filled($ip)) {
                    $query->where('ip_address', $ip);
                }
                if ($user) {
                    $query->orWhere('user_id', $user->id);
                }
            })
            ->first();

        if ($rule) {
            try {
                $rule->increment('match_count');
                $rule->updateQuietly(['last_matched_at' => now()]);
            } catch (\Throwable) {
                // Telemetria silenciosa
            }
        }

        return $rule;
    }

    /**
     * Verifica se o login atual provém de um dispositivo ou IP presente na Whitelist ativa.
     */
    public function isWhitelisted(?User $user, ?string $ip, ?string $userAgent = null, ?Request $request = null): bool
    {
        if (! $user && empty($ip)) {
            return false;
        }

        // 1. Verifica correspondência por IP
        if (filled($ip)) {
            $matchedRule = SecurityAccessRule::query()
                ->whitelist()
                ->active()
                ->where('ip_address', $ip)
                ->where(function ($query) use ($user): void {
                    if ($user) {
                        $query->where('user_id', $user->id)
                            ->orWhereNull('user_id');
                    } else {
                        $query->whereNull('user_id');
                    }
                })
                ->first();

            if ($matchedRule) {
                try {
                    $matchedRule->increment('match_count');
                    $matchedRule->updateQuietly(['last_matched_at' => now()]);
                } catch (\Throwable) {
                }

                return true;
            }
        }

        // 2. Verifica correspondência por User-Agent e Usuário
        if ($user && filled($userAgent)) {
            $matchedUserAgentRule = SecurityAccessRule::query()
                ->whitelist()
                ->active()
                ->where('user_id', $user->id)
                ->where('user_agent', $userAgent)
                ->first();

            if ($matchedUserAgentRule) {
                try {
                    $matchedUserAgentRule->increment('match_count');
                    $matchedUserAgentRule->updateQuietly(['last_matched_at' => now()]);
                } catch (\Throwable) {
                }

                return true;
            }
        }

        return false;
    }

    /**
     * Bloqueia um endereço IP inserindo na Blacklist.
     */
    public function blockIp(string $ip, string $reason = 'Bloqueio preventivo', ?int $durationHours = null): SecurityAccessRule
    {
        $rule = SecurityAccessRule::query()->firstOrNew([
            'type' => 'blacklist',
            'ip_address' => $ip,
        ]);

        $rule->label = "Bloqueio IP: {$ip}";
        $rule->is_active = true;
        $rule->notes = $reason;
        $rule->created_by_user_id = auth()->id();
        $rule->expires_at = $durationHours ? now()->addHours($durationHours) : null;
        $rule->save();

        Log::warning("[SecurityAccessRule] IP inserido na Blacklist: {$ip} (Motivo: {$reason})");

        return $rule;
    }
}

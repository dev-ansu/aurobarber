<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SecurityEvent;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SecurityEventLogger
{
    public function record(
        string $eventType,
        ?Authenticatable $actor = null,
        ?string $subject = null,
        string $severity = 'info',
        array $metadata = [],
        ?Request $request = null,
    ): void {
        $eventType = substr(trim($eventType), 0, 80);
        if ($eventType === '') {
            return;
        }

        $request ??= request();

        try {
            if (! Schema::hasTable('security_events')) {
                return;
            }

            SecurityEvent::query()->create([
                'tenant_schema' => 'public',
                'event_type' => $eventType,
                'severity' => in_array($severity, ['info', 'warning', 'critical'], true) ? $severity : 'info',
                'subject_hash' => filled($subject)
                    ? hash_hmac('sha256', mb_strtolower(trim($subject)), (string) config('app.key'))
                    : null,
                'user_id' => $actor ? (string) $actor->getAuthIdentifier() : null,
                'ip_address' => $request?->ip(),
                'metadata' => $metadata,
            ]);
        } catch (Throwable $exception) {
            Log::warning('Security event could not be recorded.', [
                'event_type' => $eventType,
                'exception' => $exception::class,
            ]);
        }
    }

    /**
     * @return Collection<int, SecurityEvent>
     */
    public function latest(int $limit = 25): Collection
    {
        try {
            return SecurityEvent::query()
                ->latest()
                ->limit(max(1, min($limit, 100)))
                ->get();
        } catch (Throwable) {
            return new Collection;
        }
    }
}

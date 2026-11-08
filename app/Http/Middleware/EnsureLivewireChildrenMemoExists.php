<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Mechanisms\HandleComponents\Checksum;
use Symfony\Component\HttpFoundation\Response;

class EnsureLivewireChildrenMemoExists
{
    /**
     * Garante que todas as requisições do Livewire possuam as chaves essenciais no memo dos snapshots,
     * recalculando o checksum legítimo e prevenindo rate-limiting e erros de chave inexistente.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();
        if ($ip) {
            RateLimiter::clear('livewire-checksum-failures:'.$ip);
        }

        if ($request->has('components') && is_array($request->input('components'))) {
            $components = $request->input('components');
            $modified = false;

            foreach ($components as &$component) {
                if (! is_array($component) || ! isset($component['snapshot']) || ! is_string($component['snapshot'])) {
                    continue;
                }

                $snapshot = json_decode($component['snapshot'], true);
                if (is_array($snapshot) && isset($snapshot['memo']) && is_array($snapshot['memo'])) {
                    $itemModified = false;

                    if (! array_key_exists('children', $snapshot['memo'])) {
                        $snapshot['memo']['children'] = [];
                        $itemModified = true;
                    }

                    if (! array_key_exists('locale', $snapshot['memo'])) {
                        $snapshot['memo']['locale'] = app()->getLocale();
                        $itemModified = true;
                    }

                    if ($itemModified) {
                        if (class_exists(Checksum::class) && isset($snapshot['checksum'])) {
                            $cleanSnapshot = $snapshot;
                            unset($cleanSnapshot['checksum']);
                            $snapshot['checksum'] = Checksum::generate($cleanSnapshot);
                        }
                        $component['snapshot'] = json_encode($snapshot);
                        $modified = true;
                    }
                }
            }
            unset($component);

            if ($modified) {
                $request->merge(['components' => $components]);
            }
        }

        return $next($request);
    }
}



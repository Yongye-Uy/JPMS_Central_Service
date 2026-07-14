<?php

namespace App\Services\Cache;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Write-through/cache-aside helper for the "data cache" Redis usage that
 * Central-Service owns (distinct from Backend's own session/token cache —
 * see jpms-backend:* keys, which never touch this service). Key convention:
 * jpms:{entity}:{id} / jpms:{entity}:{id}:{relation}.
 */
class CacheAsideService
{
    private const DEFAULT_TTL = 300;

    public function remember(string $key, Closure $resolver, int $ttl = self::DEFAULT_TTL): mixed
    {
        return Cache::store('redis')->remember($key, $ttl, $resolver);
    }

    public function forget(string $key): void
    {
        Cache::store('redis')->forget($key);
    }

    public function forgetMany(array $keys): void
    {
        foreach ($keys as $key) {
            $this->forget($key);
        }
    }

    public static function entityKey(string $entity, int|string $id): string
    {
        return "jpms:{$entity}:{$id}";
    }

    public static function relationKey(string $entity, int|string $id, string $relation): string
    {
        return "jpms:{$entity}:{$id}:{$relation}";
    }
}

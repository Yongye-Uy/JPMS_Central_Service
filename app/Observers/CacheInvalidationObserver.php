<?php

namespace App\Observers;

use App\Services\Cache\CacheAsideService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Write-through cache invalidation: forgets the jpms:{entity}:{id} Redis
 * key for any model attached to this observer on every write, so the next
 * read repopulates from Postgres. Attached to the same models as
 * AuditObserver (see AppServiceProvider::boot()).
 */
class CacheInvalidationObserver
{
    public function __construct(private readonly CacheAsideService $cache) {}

    public function saved(Model $model): void
    {
        $this->forget($model);
    }

    public function deleted(Model $model): void
    {
        $this->forget($model);
    }

    private function forget(Model $model): void
    {
        $entity = Str::snake(class_basename($model));
        $this->cache->forget(CacheAsideService::entityKey($entity, $model->getKey()));

        if ($manuscriptId = $this->resolveManuscriptId($model)) {
            $this->cache->forget(CacheAsideService::entityKey('manuscript', $manuscriptId));
        }

        if ($model instanceof \App\Models\Journal) {
            foreach ($model->manuscripts()->pluck('id') as $manuscriptId) {
                $this->cache->forget(CacheAsideService::entityKey('manuscript', $manuscriptId));
            }
        }
    }

    /**
     * These models are eager-loaded into the cached manuscript show() payload
     * but don't own a jpms:manuscript:{id} key themselves — a write to one of
     * them must also bust the manuscript's own cache entry, or the cached
     * response goes stale until the 300s TTL expires.
     */
    private function resolveManuscriptId(Model $model): ?int
    {
        return match (true) {
            $model instanceof \App\Models\Author,
            $model instanceof \App\Models\CoAuthorInvitation,
            $model instanceof \App\Models\EditorAssignment,
            $model instanceof \App\Models\ReviewInvitation => $model->manuscript_id,
            $model instanceof \App\Models\ManuscriptFile => $model->version?->manuscript_id,
            default => null,
        };
    }
}

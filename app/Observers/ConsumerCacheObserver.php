<?php

namespace App\Observers;

use App\Support\Cache\ConsumerCache;
use Illuminate\Database\Eloquent\Model;

/**
 * Clears the storefront's caches whenever menu, offer or bundle data changes.
 *
 * Registered against every model those caches are built from — see
 * AppServiceProvider. Note this rides on Eloquent events, so a mass update
 * through the query builder (Item::query()->update(...)) will not trigger it;
 * those need ConsumerCache::flush() called alongside.
 */
class ConsumerCacheObserver
{
    public function __construct(private readonly ConsumerCache $cache) {}

    public function saved(Model $model): void
    {
        $this->cache->flush();
    }

    public function deleted(Model $model): void
    {
        $this->cache->flush();
    }

    public function restored(Model $model): void
    {
        $this->cache->flush();
    }

    public function forceDeleted(Model $model): void
    {
        $this->cache->flush();
    }
}

<?php

namespace App\Support\Cache;

use Illuminate\Support\Facades\Cache;

/**
 * Clears the caches that ordering clients fill from this API.
 *
 * itsawrapweb caches the menu, offers and bundles for a few minutes, which
 * meant an edit here took until the TTL lapsed to reach the storefront. Both
 * applications share one cache store — the same database and the same
 * CACHE_PREFIX — so this API can drop those entries the moment the records
 * behind them change, and the storefront refills them on its next request.
 *
 * These key names are a contract with itsawrapweb, mirrored there in
 * App\Services\Api\ApiCacheKeys. Renaming one without the other silently goes
 * back to waiting out the TTL, so change them together.
 */
class ConsumerCache
{
    /** Items and categories behind the menu and the landing page. */
    public const MENU = 'itsawrap_api.menu.bootstrap';

    /** Active promotional offers. */
    public const OFFERS = 'itsawrap_api.guest_offers';

    /** Ready-made bundles, including the prices resolved from the menu. */
    public const BUNDLES = 'itsawrap_api.guest_bundles';

    /**
     * Drop every consumer cache entry.
     *
     * All three are cleared together rather than picking the one that matches
     * the model that changed: bundles embed item and option prices, and their
     * headline price comes from an offer, so the three are entangled enough
     * that being clever here would mostly be a way to be wrong. Refilling them
     * costs one request each.
     */
    public function flush(): void
    {
        foreach (self::keys() as $key) {
            Cache::forget($key);
        }
    }

    /**
     * @return array<int, string>
     */
    public static function keys(): array
    {
        return [self::MENU, self::OFFERS, self::BUNDLES];
    }
}

<?php

namespace App\Providers;

use App\Models\Bundle;
use App\Models\BundleItem;
use App\Models\BundleItemOptionValue;
use App\Models\Category;
use App\Models\Item;
use App\Models\ItemOption;
use App\Models\ItemOptionValue;
use App\Models\Offer;
use App\Models\Option;
use App\Models\OptionValue;
use App\Observers\ConsumerCacheObserver;
use App\Services\Push\KreaitFirebasePushNotificationSender;
use App\Services\Push\PushNotificationSender;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Kreait\Firebase\Factory;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PushNotificationSender::class, KreaitFirebasePushNotificationSender::class);
        $this->app->singleton(Factory::class, function (): Factory {
            $factory = new Factory;
            $defaultProject = config('firebase.default', 'app');
            $projectId = config("firebase.projects.{$defaultProject}.project_id");

            if (is_string($projectId) && $projectId !== '') {
                return $factory->withProjectId($projectId);
            }

            return $factory;
        });
    }

    /**
     * Models the storefront's cached menu, offer and bundle payloads are built
     * from. Saving any of them clears those caches so the change is live on the
     * next storefront request instead of waiting out its TTL.
     *
     * @var array<int, class-string<\Illuminate\Database\Eloquent\Model>>
     */
    private const CONSUMER_CACHE_MODELS = [
        Bundle::class,
        BundleItem::class,
        BundleItemOptionValue::class,
        Category::class,
        Item::class,
        ItemOption::class,
        ItemOptionValue::class,
        Offer::class,
        Option::class,
        OptionValue::class,
    ];

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach (self::CONSUMER_CACHE_MODELS as $model) {
            $model::observe(ConsumerCacheObserver::class);
        }

        RateLimiter::for('guest-menu', function (Request $request): Limit {
            return Limit::perMinute(120)
                ->by($request->ip())
                ->response(function () use ($request) {
                    Log::warning('guest-menu-throttled', [
                        'ip' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'path' => $request->path(),
                    ]);

                    return response()->json([
                        'message' => 'Too many guest menu requests.',
                    ], 429);
                });
        });

        RateLimiter::for('guest-customer', function (Request $request): Limit {
            return Limit::perMinute(20)
                ->by($request->ip())
                ->response(function () use ($request) {
                    Log::warning('guest-customer-throttled', [
                        'ip' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'path' => $request->path(),
                    ]);

                    return response()->json([
                        'message' => 'Too many guest customer requests.',
                    ], 429);
                });
        });

        RateLimiter::for('guest-delivery-windows', function (Request $request): Limit {
            return Limit::perMinute(120)
                ->by($request->ip())
                ->response(function () use ($request) {
                    Log::warning('guest-delivery-windows-throttled', [
                        'ip' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'path' => $request->path(),
                    ]);

                    return response()->json([
                        'message' => 'Too many guest delivery window requests.',
                    ], 429);
                });
        });

        RateLimiter::for('guest-register', function (Request $request): Limit {
            return Limit::perMinute(10)
                ->by($request->ip())
                ->response(function () use ($request) {
                    Log::warning('guest-register-throttled', [
                        'ip' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'path' => $request->path(),
                    ]);

                    return response()->json([
                        'message' => 'Too many registration requests.',
                    ], 429);
                });
        });

        RateLimiter::for('guest-order', function (Request $request): Limit {
            $idempotencyKey = (string) ($request->header('Idempotency-Key') ?? $request->input('idempotency_key', ''));

            return Limit::perMinute(10)
                ->by($request->ip())
                ->response(function () use ($request, $idempotencyKey) {
                    Log::warning('guest-order-throttled', [
                        'ip' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'path' => $request->path(),
                        'idempotency_key' => $idempotencyKey !== '' ? $idempotencyKey : null,
                    ]);

                    return response()->json([
                        'message' => 'Too many guest order requests.',
                    ], 429);
                });
        });

        RateLimiter::for('guest-order-lookup', function (Request $request): Limit {
            return Limit::perMinute(30)
                ->by($request->ip())
                ->response(function () use ($request) {
                    Log::warning('guest-order-lookup-throttled', [
                        'ip' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'path' => $request->path(),
                    ]);

                    return response()->json([
                        'message' => 'Too many guest order lookup requests.',
                    ], 429);
                });
        });
    }
}

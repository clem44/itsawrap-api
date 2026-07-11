<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
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
    }
}

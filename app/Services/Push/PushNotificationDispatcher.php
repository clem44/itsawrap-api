<?php

namespace App\Services\Push;

use App\Jobs\SendFirebasePushNotification;
use App\Models\PushSubscription;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Throwable;

class PushNotificationDispatcher
{
    /**
     * @param  Builder<PushSubscription>  $subscriptions
     * @param  array<string, string>  $data
     */
    public function dispatch(Builder $subscriptions, ?int $orderId, string $title, string $body, array $data, string $failureLogEvent): void
    {
        $subscriptions->each(function (PushSubscription $subscription) use ($orderId, $title, $body, $data, $failureLogEvent): void {
            try {
                SendFirebasePushNotification::dispatch(
                    $subscription->id,
                    $orderId,
                    $title,
                    $body,
                    $data
                );
            } catch (Throwable $exception) {
                Log::error($failureLogEvent, [
                    'push_subscription_id' => $subscription->id,
                    'order_id' => $orderId,
                    'error' => $exception->getMessage(),
                ]);
            }
        });
    }
}

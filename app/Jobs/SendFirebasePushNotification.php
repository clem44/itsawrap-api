<?php

namespace App\Jobs;

use App\Models\PushSubscription;
use App\Services\Push\InvalidPushTokenException;
use App\Services\Push\PushNotificationSender;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendFirebasePushNotification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, string>  $data
     */
    public function __construct(
        public int $pushSubscriptionId,
        public ?int $orderId,
        public string $title,
        public string $body,
        public array $data
    ) {}

    public function handle(PushNotificationSender $sender): void
    {
        $subscription = PushSubscription::query()
            ->active()
            ->find($this->pushSubscriptionId);

        if ($subscription === null) {
            return;
        }

        try {
            $sender->sendToSubscription($subscription, $this->title, $this->body, $this->data);
        } catch (InvalidPushTokenException $exception) {
            $subscription->markRevoked();

            Log::info('firebase-push-token-revoked', [
                'push_subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
                'order_id' => $this->orderId,
                'error' => $exception->getMessage(),
            ]);
        } catch (Throwable $exception) {
            Log::error('firebase-push-send-failed', [
                'push_subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
                'order_id' => $this->orderId,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}

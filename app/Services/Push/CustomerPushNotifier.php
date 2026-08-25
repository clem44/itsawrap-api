<?php

namespace App\Services\Push;

use App\Models\Order;
use App\Models\PushSubscription;

class CustomerPushNotifier
{
    public const TYPE_ORDER_STATUS_UPDATED = 'order_status_updated';

    public function __construct(private readonly PushNotificationDispatcher $dispatcher) {}

    public function orderStatusUpdated(Order $order): void
    {
        $order->loadMissing(['customer', 'status']);

        $customerUserId = $order->customer?->user_id;

        if ($customerUserId === null || $order->status === null) {
            return;
        }

        $orderNumber = (string) ($order->number ?? $order->id);
        $statusName = (string) $order->status->name;
        $title = 'Order status updated';
        $body = "Order #{$orderNumber} is now {$statusName}";
        $data = [
            'type' => self::TYPE_ORDER_STATUS_UPDATED,
            'order_id' => (string) $order->id,
            'order_number' => $orderNumber,
            'status_id' => (string) $order->status_id,
            'status' => $statusName,
            'source' => (string) $order->source,
        ];

        $this->dispatcher->dispatch(
            PushSubscription::query()
                ->active()
                ->where('provider', PushSubscription::PROVIDER_FIREBASE)
                ->where('app_context', PushSubscription::APP_CONTEXT_CUSTOMER)
                ->whereIn('platform', [PushSubscription::PLATFORM_IOS, PushSubscription::PLATFORM_ANDROID])
                ->where('user_id', $customerUserId),
            $order->id,
            $title,
            $body,
            $data,
            'customer-push-dispatch-failed'
        );
    }
}

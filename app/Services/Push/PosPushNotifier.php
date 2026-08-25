<?php

namespace App\Services\Push;

use App\Models\Order;
use App\Models\PushSubscription;

class PosPushNotifier
{
    public const TYPE_WEB_ORDER_CREATED = 'web_order_created';

    public function __construct(private readonly PushNotificationDispatcher $dispatcher) {}

    public function webOrderCreated(Order $order): void
    {
        if (! in_array($order->source, ['guest-web', 'web-customer'], true)) {
            return;
        }

        $orderNumber = (string) ($order->number ?? $order->id);
        $title = 'New web order';
        $body = "Order #{$orderNumber} is waiting for confirmation";
        $data = [
            'type' => self::TYPE_WEB_ORDER_CREATED,
            'order_id' => (string) $order->id,
            'order_number' => $orderNumber,
            'source' => (string) $order->source,
        ];

        $this->dispatcher->dispatch(
            PushSubscription::query()->forPosOrderConfirmation(),
            $order->id,
            $title,
            $body,
            $data,
            'firebase-push-dispatch-failed'
        );
    }
}

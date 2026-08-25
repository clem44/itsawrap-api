<?php

namespace App\Services\Push;

use App\Models\PushSubscription;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\Messaging\InvalidArgument;
use Kreait\Firebase\Exception\Messaging\NotFound;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class KreaitFirebasePushNotificationSender implements PushNotificationSender
{
    public function __construct(private readonly Messaging $messaging) {}

    public function sendToSubscription(PushSubscription $subscription, string $title, string $body, array $data): array
    {
        $message = CloudMessage::new()
            ->withToken($subscription->token)
            ->withNotification(Notification::create($title, $body))
            ->withData($data)
            ->withHighestPossiblePriority();

        try {
            return $this->messaging->send($message);
        } catch (NotFound|InvalidArgument $exception) {
            throw new InvalidPushTokenException($exception->getMessage(), previous: $exception);
        }
    }
}

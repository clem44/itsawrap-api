<?php

namespace App\Services\Push;

use App\Models\PushSubscription;

interface PushNotificationSender
{
    /**
     * @param  array<string, string>  $data
     * @return array<string, mixed>
     */
    public function sendToSubscription(PushSubscription $subscription, string $title, string $body, array $data): array;
}

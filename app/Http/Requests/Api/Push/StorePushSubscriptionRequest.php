<?php

namespace App\Http\Requests\Api\Push;

use App\Models\PushSubscription;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePushSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'max:4096'],
            'platform' => ['required', 'string', Rule::in([PushSubscription::PLATFORM_IOS, PushSubscription::PLATFORM_ANDROID, PushSubscription::PLATFORM_WEB])],
            'app_context' => ['required', 'string', Rule::in([PushSubscription::APP_CONTEXT_POS, PushSubscription::APP_CONTEXT_ADMIN, PushSubscription::APP_CONTEXT_CUSTOMER])],
            'device_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}

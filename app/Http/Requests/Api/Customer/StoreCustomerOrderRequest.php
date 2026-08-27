<?php

namespace App\Http\Requests\Api\Customer;

use App\Models\Item;
use App\Models\OptionValue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreCustomerOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>|string>
     */
    public function rules(): array
    {
        return [
            'number' => ['nullable', 'string', 'max:50'],
            'subtotal' => ['required', 'numeric', 'min:0', 'max:99999.99'],
            'service_charge' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'total' => ['required', 'numeric', 'min:0', 'max:99999.99'],
            'comments' => ['nullable', 'string', 'max:1000'],
            'is_delivery' => ['required', 'boolean'],
            'delivery_window_id' => ['nullable', 'integer', 'exists:delivery_windows,id'],
            'delivery_address' => ['nullable', 'string', 'max:1000'],
            'delivery_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'delivery_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'delivery_instructions' => ['nullable', 'string', 'max:1000'],
            'idempotency_key' => ['nullable', 'string', 'max:100'],
            'items' => ['required', 'array', 'min:1', 'max:25'],
            'items.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'items.*.price' => ['required', 'numeric', 'min:0', 'max:9999.99'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:20'],
            'items.*.comment' => ['nullable', 'string', 'max:255'],
            'items.*.options' => ['nullable', 'array', 'max:30'],
            'items.*.options.*.option_value_id' => ['required', 'integer', 'exists:option_values,id'],
            'items.*.options.*.price' => ['nullable', 'numeric', 'min:0', 'max:9999.99'],
            'items.*.options.*.qty' => ['nullable', 'integer', 'min:1', 'max:20'],
            'items.*.options.*.parent_option_value_id' => ['nullable', 'integer', 'exists:option_values,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'service_charge' => $this->input('service_charge', 0),
            'idempotency_key' => $this->header('Idempotency-Key') ?? $this->input('idempotency_key'),
        ]);
    }

    /**
     * @return array<int, callable(\Illuminate\Validation\Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (strlen((string) $this->getContent()) > 65535) {
                    $validator->errors()->add('payload', 'The order payload is too large.');
                }

                if ($this->boolean('is_delivery')) {
                    foreach (['delivery_window_id', 'delivery_address', 'delivery_latitude', 'delivery_longitude'] as $field) {
                        if (! $this->filled($field)) {
                            $validator->errors()->add($field, 'This field is required for delivery orders.');
                        }
                    }
                }

                $totalQuantity = 0;
                $totalOptions = 0;

                foreach ($this->input('items', []) as $index => $item) {
                    $itemModel = Item::query()->find($item['item_id'] ?? null);
                    if ($itemModel === null || ! $itemModel->active) {
                        $validator->errors()->add("items.$index.item_id", 'The selected item is not available for ordering.');
                    }

                    $totalQuantity += (int) ($item['quantity'] ?? 0);
                    $totalOptions += count($item['options'] ?? []);

                    foreach ($item['options'] ?? [] as $optionIndex => $option) {
                        if (isset($option['parent_option_value_id']) && $option['parent_option_value_id'] !== null) {
                            $parentOptionValue = OptionValue::query()->find($option['parent_option_value_id']);
                            if ($parentOptionValue === null) {
                                $validator->errors()->add("items.$index.options.$optionIndex.parent_option_value_id", 'The selected parent option is invalid.');
                            }
                        }
                    }
                }

                if ($totalQuantity > 100) {
                    $validator->errors()->add('items', 'Orders may not contain more than 100 total item quantities.');
                }

                if ($totalOptions > 200) {
                    $validator->errors()->add('items', 'Orders may not contain more than 200 option selections.');
                }
            },
        ];
    }
}

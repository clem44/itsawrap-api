<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderPaymentRequest extends FormRequest
{
    protected $errorBag = 'payment';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
            'method' => ['required', Rule::in(['cash', 'card'])],
            'card_last_four' => ['exclude_unless:method,card', 'required', 'digits:4'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.gt' => 'The payment amount must be greater than zero.',
            'card_last_four.required' => 'Enter the last four digits of the card.',
            'card_last_four.digits' => 'The card reference must be exactly four digits.',
        ];
    }
}

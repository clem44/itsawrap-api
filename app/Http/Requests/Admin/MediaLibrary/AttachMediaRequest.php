<?php

namespace App\Http\Requests\Admin\MediaLibrary;

use App\Models\Bundle;
use App\Models\Item;
use App\Models\Offer;
use App\Models\Option;
use App\Models\OptionValue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttachMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'media_id' => ['required', 'integer', 'exists:media,id'],
            'mediable_type' => ['required', 'string', Rule::in($this->allowedTypes())],
            'mediable_id' => ['required', 'integer'],
            'tag' => ['required', 'string', 'max:100', Rule::in($this->allowedTags())],
        ];
    }

    public function allowedTypes(): array
    {
        return [
            Bundle::class,
            Item::class,
            Offer::class,
            Option::class,
            OptionValue::class,
        ];
    }

    public function allowedTags(): array
    {
        return [
            'primary_image',
        ];
    }
}

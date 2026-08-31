<?php

namespace App\Http\Requests\Admin\MediaLibrary;

use Illuminate\Foundation\Http\FormRequest;

class StoreMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,gif,webp,heic', 'max:10240'],
            'alt' => ['nullable', 'string', 'max:500'],
        ];
    }
}

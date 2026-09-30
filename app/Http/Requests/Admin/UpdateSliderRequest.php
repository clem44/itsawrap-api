<?php

namespace App\Http\Requests\Admin;

use App\Models\Slide;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Plank\Mediable\Media;

class UpdateSliderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
            'autoplay' => ['required', 'boolean'],
            'autoplay_interval_ms' => ['required', 'integer', 'min:2000', 'max:30000'],
            'slides' => ['required', 'array', 'min:1', 'max:20'],
            'slides.*.id' => ['nullable', 'integer', 'distinct'],
            'slides.*.sort_order' => ['required', 'integer', 'min:0', 'distinct'],
            'slides.*.is_active' => ['required', 'boolean'],
            'slides.*.media_id' => ['required', 'integer', 'exists:media,id'],
            'slides.*.badge' => ['nullable', 'string', 'max:80'],
            'slides.*.heading' => ['required', 'string', 'max:160'],
            'slides.*.body' => ['nullable', 'string', 'max:500'],
            'slides.*.text_alignment' => ['required', Rule::in(['left', 'center', 'right'])],
            'slides.*.overlay_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'slides.*.overlay_opacity' => ['required', 'integer', 'min:0', 'max:90'],
            'slides.*.transition' => ['required', Rule::in(['fade', 'slide-left', 'slide-right'])],
            'slides.*.transition_ms' => ['required', 'integer', 'min:100', 'max:2000'],
            'slides.*.buttons' => ['present', 'array', 'max:2'],
            'slides.*.buttons.*.sort_order' => ['required', 'integer', 'min:0', 'max:1', 'distinct'],
            'slides.*.buttons.*.label' => ['required', 'string', 'max:60'],
            'slides.*.buttons.*.url' => ['required', 'string', 'max:2048', $this->safeUrlRule()],
            'slides.*.buttons.*.style' => ['required', Rule::in(['solid', 'outline'])],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            foreach ((array) $this->input('slides', []) as $index => $slidePayload) {
                $slideId = $slidePayload['id'] ?? null;
                if ($slideId && ! Slide::query()->whereKey($slideId)->where('slider_id', $this->route('slider')?->id)->exists()) {
                    $validator->errors()->add("slides.{$index}.id", 'The selected slide does not belong to this slider.');
                }

                $mediaId = $slidePayload['media_id'] ?? null;
                if ($mediaId && ! Media::query()->whereKey($mediaId)->whereIn('aggregate_type', [Media::TYPE_IMAGE, Media::TYPE_IMAGE_VECTOR])->exists()) {
                    $validator->errors()->add("slides.{$index}.media_id", 'The selected media must be an image.');
                }
            }
        }];
    }

    private function safeUrlRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_string($value) || ! preg_match('/^(?:\/(?!\/)[^\s]*|https?:\/\/[^\s]+)$/i', $value)) {
                $fail('The button URL must be a site path or an HTTP(S) URL.');
            }
        };
    }
}

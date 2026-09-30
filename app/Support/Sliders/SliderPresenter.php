<?php

namespace App\Support\Sliders;

use App\Models\Slide;
use App\Models\Slider;
use Plank\Mediable\Media;

class SliderPresenter
{
    /** @var array<int, array{0: int|null, 1: int|null}> */
    private array $dimensionCache = [];

    /**
     * @return array<string, mixed>
     */
    public function present(Slider $slider, bool $publishedOnly = false): array
    {
        $slides = $slider->slides;

        if ($publishedOnly) {
            $slides = $slides->where('is_active', true);
        }

        return [
            'id' => $slider->id,
            'name' => $slider->name,
            'slug' => $slider->slug,
            'is_active' => $slider->is_active,
            'autoplay' => $slider->autoplay,
            'autoplay_interval_ms' => $slider->autoplay_interval_ms,
            'slides' => $slides->values()->map(fn (Slide $slide) => $this->presentSlide($slide))->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentSlide(Slide $slide): array
    {
        $media = $slide->firstMedia(Slide::IMAGE_TAG);

        return [
            'id' => $slide->id,
            'sort_order' => $slide->sort_order,
            'is_active' => $slide->is_active,
            'badge' => $slide->badge,
            'heading' => $slide->heading,
            'body' => $slide->body,
            'text_alignment' => $slide->text_alignment,
            'overlay_color' => $slide->overlay_color,
            'overlay_opacity' => $slide->overlay_opacity,
            'transition' => $slide->transition,
            'transition_ms' => $slide->transition_ms,
            'media' => $media ? $this->presentMedia($media) : null,
            'buttons' => $slide->buttons->map(fn ($button) => [
                'id' => $button->id,
                'sort_order' => $button->sort_order,
                'label' => $button->label,
                'url' => $button->url,
                'style' => $button->style,
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentMedia(Media $media): array
    {
        [$width, $height] = $this->dimensions($media);

        try {
            $url = $media->getUrl();
        } catch (\Throwable) {
            $url = null;
        }

        return [
            'id' => $media->id,
            'basename' => $media->basename,
            'filename' => $media->filename,
            'extension' => $media->extension,
            'mime_type' => $media->mime_type,
            'aggregate_type' => $media->aggregate_type,
            'size' => $media->size,
            'size_label' => $media->readableSize(),
            'width' => $width,
            'height' => $height,
            'dimensions_label' => $width && $height ? "{$width} x {$height} px" : null,
            'url' => $url,
            'preview_url' => $media->aggregate_type === Media::TYPE_IMAGE ? $url : null,
            'alt' => $media->alt ?? '',
        ];
    }

    /**
     * @return array{0: int|null, 1: int|null}
     */
    private function dimensions(Media $media): array
    {
        if (isset($this->dimensionCache[$media->id])) {
            return $this->dimensionCache[$media->id];
        }

        if ($media->aggregate_type !== Media::TYPE_IMAGE || ! $media->fileExists()) {
            return $this->dimensionCache[$media->id] = [null, null];
        }

        $dimensions = @getimagesize($media->getAbsolutePath());

        return $this->dimensionCache[$media->id] = is_array($dimensions)
            ? [$dimensions[0] ?? null, $dimensions[1] ?? null]
            : [null, null];
    }
}

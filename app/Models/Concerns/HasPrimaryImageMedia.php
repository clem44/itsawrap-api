<?php

namespace App\Models\Concerns;

use Plank\Mediable\Media;

trait HasPrimaryImageMedia
{
    public const IMAGE_TAG = 'primary_image';

    public function includePrimaryImageMedia(): static
    {
        return $this
            ->append(['primary_image_url', 'primary_media'])
            ->makeHidden('media');
    }

    public function getPrimaryImageUrlAttribute(): ?string
    {
        $media = $this->primaryImageMedia();

        return $media ? $this->mediaUrl($media) : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getPrimaryMediaAttribute(): ?array
    {
        $media = $this->primaryImageMedia();

        if ($media === null) {
            return null;
        }

        return [
            'id' => $media->id,
            'basename' => $media->basename,
            'filename' => $media->filename,
            'extension' => $media->extension,
            'mime_type' => $media->mime_type,
            'aggregate_type' => $media->aggregate_type,
            'size' => $media->size,
            'url' => $this->mediaUrl($media),
            'preview_url' => $media->aggregate_type === Media::TYPE_IMAGE ? $this->mediaUrl($media) : null,
            'alt' => $media->alt ?? '',
        ];
    }

    public function primaryImageMedia(): ?Media
    {
        return $this->firstMedia(self::IMAGE_TAG);
    }

    private function mediaUrl(Media $media): ?string
    {
        try {
            return $media->getUrl();
        } catch (\Throwable) {
            return null;
        }
    }
}

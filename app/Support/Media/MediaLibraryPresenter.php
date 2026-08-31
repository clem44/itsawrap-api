<?php

namespace App\Support\Media;

use Illuminate\Support\Facades\DB;
use Plank\Mediable\Media;

class MediaLibraryPresenter
{
    public function present(Media $media): array
    {
        [$width, $height] = $this->dimensions($media);

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
            'uploaded_at' => $media->created_at?->toIso8601String(),
            'uploaded_label' => $media->created_at?->format('M j, Y'),
            'url' => $this->url($media),
            'preview_url' => $media->aggregate_type === Media::TYPE_IMAGE ? $this->url($media) : null,
            'alt' => $media->alt ?? '',
            'attached_count' => $this->attachedCount($media),
        ];
    }

    private function dimensions(Media $media): array
    {
        if ($media->aggregate_type !== Media::TYPE_IMAGE || ! $media->fileExists()) {
            return [null, null];
        }

        $dimensions = @getimagesize($media->getAbsolutePath());

        if (! is_array($dimensions)) {
            return [null, null];
        }

        return [$dimensions[0] ?? null, $dimensions[1] ?? null];
    }

    private function url(Media $media): ?string
    {
        try {
            return $media->getUrl();
        } catch (\Throwable) {
            return null;
        }
    }

    private function attachedCount(Media $media): int
    {
        return DB::table(config('mediable.mediables_table', 'mediables'))
            ->where('media_id', $media->id)
            ->count();
    }
}

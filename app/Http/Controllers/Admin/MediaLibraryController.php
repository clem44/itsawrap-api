<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MediaLibrary\AttachMediaRequest;
use App\Http\Requests\Admin\MediaLibrary\StoreMediaRequest;
use App\Support\Media\MediaLibraryPresenter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Plank\Mediable\Facades\MediaUploader;
use Plank\Mediable\Media;

class MediaLibraryController extends Controller
{
    public function show(): View
    {
        return view('admin.media.index');
    }

    public function index(Request $request, MediaLibraryPresenter $presenter): JsonResponse
    {
        $validated = $request->validate([
            'query' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'in:all,image,document,audio,video'],
            'selected' => ['nullable', 'integer', 'exists:media,id'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $typeMap = [
            'image' => [Media::TYPE_IMAGE, Media::TYPE_IMAGE_VECTOR],
            'document' => [Media::TYPE_PDF, Media::TYPE_DOCUMENT, Media::TYPE_SPREADSHEET, Media::TYPE_PRESENTATION],
            'audio' => [Media::TYPE_AUDIO],
            'video' => [Media::TYPE_VIDEO],
        ];

        $media = Media::query()
            ->with(['variants', 'originalMedia'])
            ->whereIsOriginal()
            ->when(($validated['query'] ?? null), function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('filename', 'like', "%{$search}%")
                        ->orWhere('extension', 'like', "%{$search}%")
                        ->orWhere('mime_type', 'like', "%{$search}%");
                });
            })
            ->when(
                isset($validated['type']) && $validated['type'] !== 'all',
                fn ($query) => $query->whereIn('aggregate_type', $typeMap[$validated['type']] ?? [])
            )
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(24)
            ->withQueryString();

        return response()->json([
            'data' => $media->getCollection()
                ->map(fn (Media $media) => $presenter->present($media))
                ->values(),
            'meta' => [
                'current_page' => $media->currentPage(),
                'last_page' => $media->lastPage(),
                'per_page' => $media->perPage(),
                'total' => $media->total(),
            ],
        ]);
    }

    public function store(StoreMediaRequest $request, MediaLibraryPresenter $presenter): JsonResponse
    {
        $validated = $request->validated();

        $media = MediaUploader::fromSource($request->file('file'))
            ->toDisk('public')
            ->toDirectory('media-library')
            ->onDuplicateIncrement()
            ->withAltAttribute($validated['alt'] ?? '')
            ->upload();

        return response()->json([
            'data' => $presenter->present($media),
        ], 201);
    }

    public function update(Request $request, Media $media, MediaLibraryPresenter $presenter): JsonResponse
    {
        $validated = $request->validate([
            'alt' => ['nullable', 'string', 'max:500'],
        ]);

        $media->forceFill([
            'alt' => $validated['alt'] ?? '',
        ])->save();

        return response()->json([
            'data' => $presenter->present($media->fresh()),
        ]);
    }

    public function destroy(Media $media): JsonResponse
    {
        $attachedCount = DB::table(config('mediable.mediables_table', 'mediables'))
            ->where('media_id', $media->id)
            ->count();

        if ($attachedCount > 0) {
            return response()->json([
                'message' => 'This media file is attached to existing records and cannot be deleted.',
            ], 422);
        }

        $media->delete();

        return response()->json(['success' => true]);
    }

    public function attach(AttachMediaRequest $request, MediaLibraryPresenter $presenter): JsonResponse
    {
        $validated = $request->validated();
        $model = $this->resolveMediable($validated['mediable_type'], (int) $validated['mediable_id']);
        $media = Media::query()->findOrFail($validated['media_id']);

        $model->syncMedia($media, $validated['tag']);

        return response()->json([
            'data' => $presenter->present($media),
        ]);
    }

    private function resolveMediable(string $type, int $id): Model
    {
        return $type::query()->findOrFail($id);
    }
}

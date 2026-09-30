<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Slide;
use App\Models\Slider;
use App\Support\Sliders\SliderPresenter;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class GuestSliderController extends Controller
{
    #[OA\Get(
        path: '/guest/sliders/{slug}',
        summary: 'Get a published slider',
        description: 'Returns an active slider with its active slides in display order.',
        tags: ['Guest Sliders'],
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string'), example: 'homepage-main'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Published slider', content: new OA\JsonContent(ref: '#/components/schemas/Slider')),
            new OA\Response(response: 404, description: 'Slider not found'),
        ]
    )]
    public function show(string $slug, SliderPresenter $presenter): JsonResponse
    {
        $slider = Slider::query()
            ->active()
            ->where('slug', $slug)
            ->with([
                'slides' => fn ($query) => $query->active()->with('buttons')->withMedia(Slide::IMAGE_TAG),
            ])
            ->firstOrFail();

        return response()->json($presenter->present($slider, publishedOnly: true));
    }
}

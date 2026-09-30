<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSliderRequest;
use App\Models\Slide;
use App\Models\Slider;
use App\Support\Sliders\SliderPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HeroSliderController extends Controller
{
    public function index(SliderPresenter $presenter): View
    {
        $slider = Slider::query()->firstOrCreate(
            ['slug' => 'homepage-main'],
            [
                'name' => 'Homepage Main Carousel',
                'is_active' => true,
                'autoplay' => true,
                'autoplay_interval_ms' => 5000,
            ]
        );
        $this->loadSlider($slider);

        return view('admin.hero-sliders.index', [
            'slider' => $presenter->present($slider),
        ]);
    }

    public function update(UpdateSliderRequest $request, Slider $slider, SliderPresenter $presenter): JsonResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($slider, $validated): void {
            $slider->update([
                'name' => $validated['name'],
                'is_active' => $validated['is_active'],
                'autoplay' => $validated['autoplay'],
                'autoplay_interval_ms' => $validated['autoplay_interval_ms'],
            ]);

            $retainedIds = collect($validated['slides'])->pluck('id')->filter()->map(fn ($id) => (int) $id);
            $slider->slides()->whereNotIn('id', $retainedIds)->get()->each->delete();

            foreach ($validated['slides'] as $slidePayload) {
                $slide = isset($slidePayload['id'])
                    ? $slider->slides()->whereKey($slidePayload['id'])->firstOrFail()
                    : $slider->slides()->make();

                $slide->fill(collect($slidePayload)->except(['id', 'media_id', 'buttons'])->all());
                $slide->save();
                $slide->syncMedia((int) $slidePayload['media_id'], Slide::IMAGE_TAG);
                $slide->buttons()->delete();
                $slide->buttons()->createMany($slidePayload['buttons']);
            }
        });

        $this->loadSlider($slider);

        return response()->json([
            'message' => 'Slider saved.',
            'slider' => $presenter->present($slider),
        ]);
    }

    private function loadSlider(Slider $slider): void
    {
        $slider->load([
            'slides' => fn ($query) => $query->with('buttons')->withMedia(Slide::IMAGE_TAG),
        ]);
    }
}

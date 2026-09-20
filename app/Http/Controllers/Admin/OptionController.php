<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Option;
use App\Models\OptionValue;
use App\Support\Media\MediaLibraryPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Plank\Mediable\Media;

class OptionController extends Controller
{
    public function index(MediaLibraryPresenter $mediaPresenter): View
    {
        $options = Option::query()
            ->with(['optionValues' => fn ($query) => $query->withMedia(OptionValue::IMAGE_TAG)])
            ->withCount('optionValues')
            ->withMedia(Option::IMAGE_TAG)
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $oldMedia = null;

        if (old('form_action') && old('media_id')) {
            $media = Media::find(old('media_id'));
            $oldMedia = $media ? $mediaPresenter->present($media) : null;
        }

        return view('admin.options.index', compact('options', 'mediaPresenter', 'oldMedia'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:options,name',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'media_id' => 'nullable|integer|exists:media,id',
        ]);

        unset($validated['media_id']);

        $option = Option::create($validated);
        $this->syncPrimaryImage($option, $request);

        return redirect()->route('admin.options.index')
            ->with('success', 'Option created successfully.');
    }

    public function update(Request $request, Option $option): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:options,name,'.$option->id,
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'media_id' => 'nullable|integer|exists:media,id',
        ]);

        unset($validated['media_id']);

        $option->update($validated);
        $this->syncPrimaryImage($option, $request);

        return redirect()->route('admin.options.index')
            ->with('success', 'Option updated successfully.');
    }

    public function destroy(Option $option): RedirectResponse
    {
        if ($option->itemOptions()->exists()) {
            return redirect()->route('admin.options.index')
                ->with('error', 'Cannot delete an option that is in use by items.');
        }

        // Delete associated option values
        $option->optionValues()->delete();
        $option->delete();

        return redirect()->route('admin.options.index')
            ->with('success', 'Option deleted successfully.');
    }

    public function storeValue(Request $request, Option $option): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'media_id' => 'nullable|integer|exists:media,id',
        ]);

        unset($validated['media_id']);

        $validated['option_id'] = $option->id;
        $optionValue = OptionValue::create($validated);
        $this->syncPrimaryImage($optionValue, $request);

        return redirect()->route('admin.options.index')
            ->with('success', 'Option value added successfully.');
    }

    public function updateValue(Request $request, Option $option, OptionValue $optionValue): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'media_id' => 'nullable|integer|exists:media,id',
        ]);

        if ($optionValue->option_id !== $option->id) {
            return redirect()->route('admin.options.index')
                ->with('error', 'Option value does not belong to this option.');
        }

        unset($validated['media_id']);

        $optionValue->update($validated);
        $this->syncPrimaryImage($optionValue, $request);

        return redirect()->route('admin.options.index')
            ->with('success', 'Option value updated successfully.');
    }

    public function destroyValue(Option $option, OptionValue $optionValue): RedirectResponse
    {
        if ($optionValue->option_id !== $option->id) {
            return redirect()->route('admin.options.index')
                ->with('error', 'Option value does not belong to this option.');
        }

        $optionValue->delete();

        return redirect()->route('admin.options.index')
            ->with('success', 'Option value deleted successfully.');
    }

    private function syncPrimaryImage(Option|OptionValue $model, Request $request): void
    {
        if ($request->filled('media_id')) {
            $model->syncMedia((int) $request->input('media_id'), $model::IMAGE_TAG);

            return;
        }

        $model->detachMediaTags($model::IMAGE_TAG);
    }
}

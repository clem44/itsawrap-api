<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Support\Media\MediaLibraryPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Plank\Mediable\Media;

class CategoryController extends Controller
{
    public function index(MediaLibraryPresenter $mediaPresenter): View
    {
        $categories = Category::query()
            ->withCount('items')
            ->withMedia('primary_image')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $oldMedia = null;

        if (old('form_action') && old('media_id')) {
            $media = Media::find(old('media_id'));
            $oldMedia = $media ? $mediaPresenter->present($media) : null;
        }

        return view('admin.categories.index', compact('categories', 'mediaPresenter', 'oldMedia'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
            'media_id' => 'nullable|integer|exists:media,id',
        ]);

        $category = Category::create($validated);
        $this->syncPrimaryImage($category, $request);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category created successfully.');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
            'media_id' => 'nullable|integer|exists:media,id',
        ]);

        $category->update($validated);
        $this->syncPrimaryImage($category, $request);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->items()->exists()) {
            return redirect()->route('admin.categories.index')
                ->with('error', 'Cannot delete a category that has items.');
        }

        $category->delete();

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category deleted successfully.');
    }

    private function syncPrimaryImage(Category $category, Request $request): void
    {
        if ($request->filled('media_id')) {
            $category->syncMedia((int) $request->input('media_id'), 'primary_image');

            return;
        }

        $category->detachMediaTags('primary_image');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Option;
use App\Models\OptionValue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OptionController extends Controller
{
    public function index(): View
    {
        $options = Option::query()
            ->with('optionValues')
            ->withCount('optionValues')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.options.index', compact('options'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:options,name',
        ]);

        Option::create($validated);

        return redirect()->route('admin.options.index')
            ->with('success', 'Option created successfully.');
    }

    public function update(Request $request, Option $option): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:options,name,' . $option->id,
        ]);

        $option->update($validated);

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
        ]);

        $validated['option_id'] = $option->id;
        OptionValue::create($validated);

        return redirect()->route('admin.options.index')
            ->with('success', 'Option value added successfully.');
    }

    public function updateValue(Request $request, Option $option, OptionValue $optionValue): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'nullable|numeric|min:0',
        ]);

        if ($optionValue->option_id !== $option->id) {
            return redirect()->route('admin.options.index')
                ->with('error', 'Option value does not belong to this option.');
        }

        $optionValue->update($validated);

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
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Status;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StatusController extends Controller
{
    public function index(): View
    {
        $statuses = Status::query()
            ->withCount('orders')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.statuses.index', compact('statuses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:statuses,name',
            'description' => 'nullable|string',
        ]);

        Status::query()->create($validated);

        return redirect()->route('admin.statuses.index')
            ->with('success', 'Status created successfully.');
    }

    public function update(Request $request, Status $status): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:statuses,name,'.$status->id,
            'description' => 'nullable|string',
        ]);

        $status->update($validated);

        return redirect()->route('admin.statuses.index')
            ->with('success', 'Status updated successfully.');
    }

    public function destroy(Status $status): RedirectResponse
    {
        if ($status->orders()->exists()) {
            return redirect()->route('admin.statuses.index')
                ->with('error', 'Cannot delete a status that is assigned to orders.');
        }

        $status->delete();

        return redirect()->route('admin.statuses.index')
            ->with('success', 'Status deleted successfully.');
    }
}

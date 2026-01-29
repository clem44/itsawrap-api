<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Download;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class DownloadController extends Controller
{
    public function index(): View
    {
        $downloads = Download::query()
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.downloads.index', compact('downloads'));
    }

    public function create(): View
    {
        return view('admin.downloads.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'file' => 'required|file|max:512000',
            'filetype' => 'nullable|string|max:100',
            'type' => 'nullable|string|max:100',
            'published' => 'nullable|boolean',
        ]);

        $file = $request->file('file');
        $directory = public_path('downloads');

        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $filename = time() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $file->getClientOriginalName());
        $file->move($directory, $filename);

        $download = Download::create([
            'name' => $validated['name'],
            'filename' => $filename,
            'filepath' => 'downloads/' . $filename,
            'size' => $file->getSize(),
            'ext' => $file->getClientOriginalExtension(),
            'meme' => $file->getClientMimeType(),
            'filetype' => $validated['filetype'] ?? null,
            'type' => $validated['type'] ?? null,
            'published' => $request->boolean('published'),
        ]);

        return redirect()->route('admin.downloads.edit', $download)
            ->with('success', 'Download created successfully.');
    }

    public function edit(Download $download): View
    {
        return view('admin.downloads.edit', compact('download'));
    }

    public function update(Request $request, Download $download): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'file' => 'nullable|file|max:512000',
            'filetype' => 'nullable|string|max:100',
            'type' => 'nullable|string|max:100',
            'published' => 'nullable|boolean',
        ]);

        $updates = [
            'name' => $validated['name'],
            'filetype' => $validated['filetype'] ?? null,
            'type' => $validated['type'] ?? null,
            'published' => $request->boolean('published'),
        ];

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $directory = public_path('downloads');

            if (!File::exists($directory)) {
                File::makeDirectory($directory, 0755, true);
            }

            $filename = time() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $file->getClientOriginalName());
            $file->move($directory, $filename);

            $oldPath = public_path($download->filepath);
            if ($download->filepath && File::exists($oldPath)) {
                File::delete($oldPath);
            }

            $updates = array_merge($updates, [
                'filename' => $filename,
                'filepath' => 'downloads/' . $filename,
                'size' => $file->getSize(),
                'ext' => $file->getClientOriginalExtension(),
                'meme' => $file->getClientMimeType(),
            ]);
        }

        $download->update($updates);

        return redirect()->route('admin.downloads.edit', $download)
            ->with('success', 'Download updated successfully.');
    }
}

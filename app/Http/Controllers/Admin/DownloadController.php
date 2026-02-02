<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Download;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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
        $file = $request->file('file');
        $isIpa = $this->isIpaUpload($file);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'file' => 'required|file|max:512000',
            'filetype' => 'nullable|string|max:100',
            'type' => 'nullable|string|max:100',
            'published' => 'nullable|boolean',
            'bundle_identifier' => [Rule::requiredIf($isIpa), 'string', 'max:255'],
            'bundle_version' => [Rule::requiredIf($isIpa), 'string', 'max:50'],
            'title' => 'nullable|string|max:255',
        ]);

        [$relativeDirectory, $directory] = $this->resolveUploadDirectory($validated['name'], $isIpa);

        $fileSize = $file->getSize();
        $filename = time() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $file->getClientOriginalName());
        $file->move($directory, $filename);
        $filepath = $relativeDirectory . '/' . $filename;

        if ($isIpa) {
            $manifestTitle = $validated['title'] ?? $validated['name'];
            $this->writeManifestPlist(
                $directory,
                $filepath,
                $validated['bundle_identifier'],
                $validated['bundle_version'],
                $manifestTitle
            );
        }

        $download = Download::create([
            'name' => $validated['name'],
            'filename' => $filename,
            'filepath' => $filepath,
            'size' => $fileSize ?? 0,
            'ext' => $file->getClientOriginalExtension(),
            'meme' => $file->getClientMimeType(),
            'filetype' => $validated['filetype'] ?? null,
            'type' => $validated['type'] ?? null,
            'bundle_identifier' => $validated['bundle_identifier'] ?? null,
            'bundle_version' => $validated['bundle_version'] ?? null,
            'title' => $validated['title'] ?? null,
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
        $file = $request->file('file');
        $isNewIpa = $this->isIpaUpload($file);
        $isExistingIpa = strtolower($download->ext ?? '') === 'ipa';
        $requiresManifest = $isNewIpa || (!$request->hasFile('file') && $isExistingIpa);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'file' => 'nullable|file|max:512000',
            'filetype' => 'nullable|string|max:100',
            'type' => 'nullable|string|max:100',
            'published' => 'nullable|boolean',
            'bundle_identifier' => [Rule::requiredIf($requiresManifest), 'string', 'max:255'],
            'bundle_version' => [Rule::requiredIf($requiresManifest), 'string', 'max:50'],
            'title' => 'nullable|string|max:255',
        ]);

        $updates = [
            'name' => $validated['name'],
            'filetype' => $validated['filetype'] ?? null,
            'type' => $validated['type'] ?? null,
            'bundle_identifier' => $validated['bundle_identifier'] ?? null,
            'bundle_version' => $validated['bundle_version'] ?? null,
            'title' => $validated['title'] ?? null,
            'published' => $request->boolean('published'),
        ];

        if ($request->hasFile('file')) {
            [$relativeDirectory, $directory] = $this->resolveUploadDirectory($validated['name'], $isNewIpa);

            $fileSize = $file->getSize();
            $filename = time() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $file->getClientOriginalName());
            $file->move($directory, $filename);
            $filepath = $relativeDirectory . '/' . $filename;

            $oldPath = public_path($download->filepath);
            if ($download->filepath && File::exists($oldPath)) {
                File::delete($oldPath);
            }
            if ($download->ext === 'ipa') {
                $oldManifest = public_path(rtrim(dirname($download->filepath), '.') . '/manifest.plist');
                if (File::exists($oldManifest)) {
                    File::delete($oldManifest);
                }
            }

            if ($isNewIpa) {
                $manifestTitle = $validated['title'] ?? $validated['name'];
                $this->writeManifestPlist(
                    $directory,
                    $filepath,
                    $validated['bundle_identifier'],
                    $validated['bundle_version'],
                    $manifestTitle
                );
            }

            $updates = array_merge($updates, [
                'filename' => $filename,
                'filepath' => $filepath,
                'size' => $fileSize ?? 0,
                'ext' => $file->getClientOriginalExtension(),
                'meme' => $file->getClientMimeType(),
            ]);
        }

        if (!$request->hasFile('file') && $isExistingIpa) {
            $directory = public_path(dirname($download->filepath));
            $manifestTitle = $validated['title'] ?? $validated['name'];
            $this->writeManifestPlist(
                $directory,
                $download->filepath,
                $validated['bundle_identifier'],
                $validated['bundle_version'],
                $manifestTitle
            );
        }

        $download->update($updates);

        return redirect()->route('admin.downloads.edit', $download)
            ->with('success', 'Download updated successfully.');
    }

    private function isIpaUpload(?UploadedFile $file): bool
    {
        if (!$file) {
            return false;
        }

        return strtolower($file->getClientOriginalExtension()) === 'ipa';
    }

    private function resolveUploadDirectory(string $name, bool $isIpa): array
    {
        if ($isIpa) {
            $folder = Str::slug($name);
            if ($folder === '') {
                $folder = 'app-' . time();
            }
            $relativeDirectory = 'downloads/' . $folder;
        } else {
            $relativeDirectory = 'downloads';
        }

        $directory = public_path($relativeDirectory);
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        return [$relativeDirectory, $directory];
    }

    private function writeManifestPlist(
        string $directory,
        string $relativeIpaPath,
        string $bundleIdentifier,
        string $bundleVersion,
        string $title
    ): void {
        $manifestPath = $directory . DIRECTORY_SEPARATOR . 'manifest.plist';
        $manifest = $this->buildManifestPlist(
            url($relativeIpaPath),
            $bundleIdentifier,
            $bundleVersion,
            $title
        );

        File::put($manifestPath, $manifest);
    }

    private function buildManifestPlist(
        string $ipaUrl,
        string $bundleIdentifier,
        string $bundleVersion,
        string $title
    ): string {
        $ipaUrl = $this->escapePlistValue($ipaUrl);
        $bundleIdentifier = $this->escapePlistValue($bundleIdentifier);
        $bundleVersion = $this->escapePlistValue($bundleVersion);
        $title = $this->escapePlistValue($title);

        return <<<PLIST
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN"
  "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
  <key>items</key>
  <array>
    <dict>
      <key>assets</key>
      <array>
        <dict>
          <key>kind</key>
          <string>software-package</string>
          <key>url</key>
          <string>{$ipaUrl}</string>
        </dict>
      </array>

      <key>metadata</key>
      <dict>
        <key>bundle-identifier</key>
        <string>{$bundleIdentifier}</string>
        <key>bundle-version</key>
        <string>{$bundleVersion}</string>
        <key>kind</key>
        <string>software</string>
        <key>title</key>
        <string>{$title}</string>
      </dict>
    </dict>
  </array>
</dict>
</plist>
PLIST;
    }

    private function escapePlistValue(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}

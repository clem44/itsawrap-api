<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(): View
    {
        return view('admin.settings.index', [
            'settings' => Setting::query()->orderBy('key')->paginate(20),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'key' => ['required', 'string', 'max:255', 'unique:settings,key'],
            'value' => ['nullable', 'string'],
        ]);

        Setting::query()->create($validated);

        return to_route('admin.settings.index')->with('success', 'Setting created.');
    }

    public function update(Request $request, Setting $setting): RedirectResponse
    {
        $validated = $request->validate([
            'key' => ['required', 'string', 'max:255', Rule::unique('settings', 'key')->ignore($setting)],
            'value' => ['nullable', 'string'],
        ]);

        $setting->update($validated);

        return to_route('admin.settings.index')->with('success', 'Setting updated.');
    }
}

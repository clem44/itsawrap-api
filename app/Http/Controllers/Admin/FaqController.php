<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FaqController extends Controller
{
    public function index(Request $request): View
    {
        $faqs = Faq::query()
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('admin.faqs.index', compact('faqs'));
    }

    public function create(): View
    {
        return view('admin.faqs.create');
    }
    
    public function edit(Faq $faq): View
    {
        return view('admin.faqs.edit', compact('faq'));
    }

    public function update(Request $request, Faq $faq): RedirectResponse
    {
        $faq->update($request->validate([
            'question' => 'required|string|max:255',
            'answer' => 'required|string',
        ]));

        return redirect()->route('admin.faqs.index')
            ->with('success', 'Faq updated successfully.');
    }

    public function destroy(Faq $faq): RedirectResponse
    {
        $faq->delete();

        return redirect()->route('admin.faqs.index')
            ->with('success', 'Faq deleted successfully.');
    }
}

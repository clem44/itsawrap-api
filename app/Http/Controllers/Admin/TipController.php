<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tip;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TipController extends Controller
{
    public function index(Request $request): View
    {
        $tips = Tip::query()
            ->with(['order.customer', 'order.status'])
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('admin.tips.index', compact('tips'));
    }

    public function show(Tip $tip): View
    {
        $tip->load(['order.customer', 'order.status']);

        return view('admin.tips.show', compact('tip'));
    }
}

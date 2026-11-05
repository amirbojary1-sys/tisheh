<?php

namespace App\Http\Controllers;

use App\Models\ContentPage;

class ContentController extends Controller
{
    public function show(string $slug)
    {
        return view('content', ['page' => ContentPage::where('slug', $slug)->where('published', true)->firstOrFail()]);
    }
}
